<?php
require_once ROOT_PATH . '/app/models/Socio.php';
require_once ROOT_PATH . '/app/models/Plan.php';
require_once ROOT_PATH . '/app/models/Membresia.php';
require_once ROOT_PATH . '/app/models/Pago.php';

class MembresiasController extends Controller
{
    private const POR_PAGINA = 12;

    public function index(): void
    {
        $this->requireAuth();
        $vista = in_array($this->in('vista'), ['vigentes', 'porvencer', 'vencidas', 'canceladas', 'todas'], true) ? $this->in('vista') : 'vigentes';
        $f = ['vista' => $vista, 'q' => $this->in('q'), 'plan' => $this->in('plan')];
        $pg = paginate(Membresia::contar($f), self::POR_PAGINA, $this->pageNum());
        $this->view('membresias/index', [
            'title' => 'Membresías', 'active' => 'membresias', 'f' => $f, 'pg' => $pg,
            'rows' => Membresia::listar($f, $pg['per'], $pg['offset']),
            'cont' => Membresia::conteos(), 'planes' => Plan::activos(),
        ]);
    }

    /** Formulario de inscripción / renovación. ?socio=ID precarga el socio */
    public function nueva(): void
    {
        $this->requireAuth();
        $socio = null; $inicio = date('Y-m-d'); $renueva = false;
        if (!empty($_GET['socio'])) {
            $socio = Socio::find((int)$_GET['socio']);
            if ($socio) {
                $fin = Membresia::ultimoFin((int)$socio['id']);
                if ($fin && $fin >= date('Y-m-d')) { $inicio = date('Y-m-d', strtotime($fin . ' +1 day')); $renueva = true; }
            }
        }
        $this->view('membresias/nueva', [
            'title' => 'Nueva membresía', 'active' => 'membresias',
            'socio' => $socio, 'inicio' => $inicio, 'renueva' => $renueva, 'planes' => Plan::activos(),
        ]);
    }

    public function guardar(): void
    {
        $this->requireAuth();
        $this->requirePost('membresias');
        $socio = Socio::find((int)($_POST['socio_id'] ?? 0));
        $plan = Plan::find((int)($_POST['plan_id'] ?? 0));
        $back = 'membresias/nueva' . ($socio ? '?socio=' . $socio['id'] : '');
        if (!$socio) { $this->flash('danger', 'Selecciona un socio de la lista.'); $this->redirect($back); }
        if (!$plan || !$plan['estado']) { $this->flash('danger', 'Selecciona un plan válido.'); $this->redirect($back); }

        $d = DateTime::createFromFormat('Y-m-d', (string)($_POST['fecha_inicio'] ?? ''));
        if (!$d) { $this->flash('danger', 'La fecha de inicio no es válida.'); $this->redirect($back); }
        $inicio = $d->format('Y-m-d');
        $desc = max(0, min((float)($_POST['descuento'] ?? 0), (float)$plan['precio']));
        $total = (float)$plan['precio'] - $desc;
        $pago = max(0, min((float)($_POST['monto_pago'] ?? 0), $total));
        $metodo = isset(Pago::metodos()[$_POST['metodo'] ?? '']) ? $_POST['metodo'] : 'efectivo';

        $pdo = Database::conn();
        $pdo->beginTransaction();
        try {
            $uid = Auth::user()['id'];
            $mid = Membresia::crear((int)$socio['id'], $plan, $inicio, $desc, $uid);
            $pid = null;
            if ($pago > 0) $pid = Pago::crear($mid, (int)$socio['id'], $pago, $metodo, 'Membresía ' . $plan['nombre'], $uid);
            if ($socio['estado'] !== 'activo') Database::exec("UPDATE socios SET estado='activo' WHERE id=?", [$socio['id']]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            $this->flash('danger', 'No se pudo registrar la membresía: ' . $e->getMessage());
            $this->redirect($back);
        }
        $saldo = $total - $pago;
        $this->flash($saldo > 0.009 ? 'warning' : 'success',
            'Membresía ' . $plan['nombre'] . ' registrada para ' . $socio['nombres'] . '.' . ($saldo > 0.009 ? ' Saldo pendiente: ' . money($saldo) . '.' : ''));
        $this->redirect($pid ? 'pagos/recibo/' . $pid : 'socios/ver/' . $socio['id']);
    }

    public function cancelar($id = 0): void
    {
        $this->requireAuth();
        $this->requirePost('membresias');
        $m = Membresia::find((int)$id);
        if ($m && $m['estado'] !== 'cancelada') {
            Database::exec("UPDATE membresias SET estado='cancelada' WHERE id=?", [$m['id']]);
            $this->flash('success', 'Membresía cancelada. Los pagos registrados se conservan en el historial.');
        }
        $this->redirect($_POST['volver'] ?? 'membresias');
    }

    public function congelar($id = 0): void
    {
        $this->requireAuth();
        $this->requirePost('membresias');
        $motivo = trim((string)($_POST['motivo'] ?? ''));
        $back = $_POST['volver'] ?? 'membresias';
        if (mb_strlen($motivo) < 3) { $this->flash('danger', 'Indica el motivo del congelamiento (viaje, lesión, etc.).'); $this->redirect($back); }
        require_once ROOT_PATH . '/app/models/Anulacion.php'; Anulacion::ensure();
        try { $msg = Anulacion::congelar((int)$id, mb_substr($motivo, 0, 200), (int)Auth::user()['id']); Bitacora::log('congelar', 'Membresía #' . (int)$id . ': ' . mb_substr($motivo, 0, 120)); $this->flash('success', $msg); }
        catch (RuntimeException $e) { $this->flash('danger', $e->getMessage()); }
        $this->redirect($back);
    }

    public function descongelar($id = 0): void
    {
        $this->requireAuth();
        $this->requirePost('membresias');
        require_once ROOT_PATH . '/app/models/Anulacion.php'; Anulacion::ensure();
        try { $msg = Anulacion::descongelar((int)$id, (int)Auth::user()['id']); Bitacora::log('descongelar', 'Membresía #' . (int)$id); $this->flash('success', $msg); }
        catch (RuntimeException $e) { $this->flash('danger', $e->getMessage()); }
        $this->redirect($_POST['volver'] ?? 'membresias');
    }
}
