<?php
require_once ROOT_PATH . '/app/models/Socio.php';
require_once ROOT_PATH . '/app/models/Membresia.php';
require_once ROOT_PATH . '/app/models/Pago.php';

class PagosController extends Controller
{
    private const POR_PAGINA = 15;

    public function index(): void
    {
        $this->requireAuth();
        $vista = $this->in('vista') === 'deudas' ? 'deudas' : 'cobros';
        $desde = $this->fecha($this->in('desde'), date('Y-m-01'));
        $hasta = $this->fecha($this->in('hasta'), date('Y-m-d'));
        if ($desde > $hasta) [$desde, $hasta] = [$hasta, $desde];
        $f = ['desde' => $desde, 'hasta' => $hasta, 'metodo' => $this->in('metodo'), 'q' => $this->in('q')];

        $data = ['title' => 'Pagos y cobranzas', 'active' => 'pagos', 'vista' => $vista, 'f' => $f];
        if ($vista === 'deudas') {
            $deudas = Pago::deudas();
            $data += ['deudas' => $deudas, 'tot_deuda' => array_sum(array_map(fn($d) => $d['total'] - $d['pagado'], $deudas))];
        } else {
            $pg = paginate(Pago::contar($f), self::POR_PAGINA, $this->pageNum());
            $data += ['pg' => $pg, 'pagos' => Pago::listar($f, $pg['per'], $pg['offset']), 'tot' => Pago::totales($f)];
        }
        $data['n_deudas'] = count(Pago::deudas());
        $this->view('pagos/index', $data);
    }

    /** ?socio=ID&membresia=ID precargan el formulario */
    public function nuevo(): void
    {
        $this->requireAuth();
        $socio = !empty($_GET['socio']) ? Socio::find((int)$_GET['socio']) : null;
        $pend = $socio ? Membresia::conSaldo((int)$socio['id']) : [];
        $this->view('pagos/nuevo', [
            'title' => 'Registrar pago', 'active' => 'pagos', 'socio' => $socio, 'pend' => $pend,
            'sel' => (int)($_GET['membresia'] ?? ($pend[0]['id'] ?? 0)),
        ]);
    }

    public function guardar(): void
    {
        $this->requireAuth();
        $this->requirePost('pagos');
        $socio = Socio::find((int)($_POST['socio_id'] ?? 0));
        $back = 'pagos/nuevo' . ($socio ? '?socio=' . $socio['id'] : '');
        if (!$socio) { $this->flash('danger', 'Selecciona un socio de la lista.'); $this->redirect($back); }

        $monto = round((float)str_replace(',', '.', (string)($_POST['monto'] ?? 0)), 2);
        if ($monto <= 0) { $this->flash('danger', 'Ingresa un monto mayor a cero.'); $this->redirect($back); }
        $metodo = isset(Pago::metodos()[$_POST['metodo'] ?? '']) ? $_POST['metodo'] : 'efectivo';
        $concepto = trim((string)($_POST['concepto'] ?? ''));

        $mid = (int)($_POST['membresia_id'] ?? 0) ?: null;
        if ($mid) {
            $m = Membresia::find($mid);
            if (!$m || (int)$m['socio_id'] !== (int)$socio['id'] || $m['estado'] === 'cancelada') { $this->flash('danger', 'La membresía seleccionada no es válida.'); $this->redirect($back); }
            $saldo = (float)$m['total'] - (float)$m['pagado'];
            if ($monto > $saldo + 0.009) { $this->flash('danger', 'El monto supera el saldo pendiente (' . money($saldo) . ').'); $this->redirect($back); }
            if ($concepto === '') $concepto = 'Pago de membresía ' . $m['plan'];
        } elseif ($concepto === '') {
            $concepto = 'Pago varios';
        }
        $pid = Pago::crear($mid, (int)$socio['id'], $monto, $metodo, mb_substr($concepto, 0, 150), Auth::user()['id']);
        $this->flash('success', 'Pago de ' . money($monto) . ' registrado.');
        $this->redirect('pagos/recibo/' . $pid);
    }

    public function anular($id = 0): void
    {
        $this->requireAuth();
        $this->requirePost('pagos');
        if (!Permisos::esAdmin()) { $this->flash('danger', 'Solo el administrador puede anular pagos.'); $this->redirect('pagos'); }
        $motivo = trim((string)($_POST['motivo'] ?? ''));
        if (mb_strlen($motivo) < 3) { $this->flash('danger', 'Indica el motivo de la anulación.'); $this->redirect($_POST['volver'] ?? 'pagos'); }
        require_once ROOT_PATH . '/app/models/Anulacion.php'; Anulacion::ensure();
        try { $msg = Anulacion::anularPago((int)$id, mb_substr($motivo, 0, 200), (int)Auth::user()['id']); Bitacora::log('anular_pago', 'Pago N° ' . (int)$id . ': ' . mb_substr($motivo, 0, 120)); $this->flash('success', $msg); }
        catch (RuntimeException $e) { $this->flash('danger', $e->getMessage()); }
        $this->redirect($_POST['volver'] ?? 'pagos');
    }

    public function recibo($id = 0): void
    {
        $this->requireAuth();
        $p = Pago::find((int)$id);
        if (!$p) { $this->flash('danger', 'El pago no existe.'); $this->redirect('pagos'); }
        $cfg = array_column(Database::all('SELECT clave, valor FROM configuracion'), 'valor', 'clave');
        $saldo = null;
        if ($p['membresia_id']) {
            $m = Membresia::find((int)$p['membresia_id']);
            $saldo = max(0, (float)$m['total'] - (float)$m['pagado']);
        }
        $this->view('pagos/recibo', ['title' => 'Recibo ' . sprintf('%06d', $p['id']), 'p' => $p, 'cfg' => $cfg, 'saldo' => $saldo], 'none');
    }

    /** JSON: membresías con saldo pendiente de un socio */
    public function pendientes($socioId = 0): void
    {
        $this->requireAuth();
        $this->json(array_map(fn($m) => [
            'id' => (int)$m['id'], 'plan' => $m['plan'], 'inicio' => fdate($m['fecha_inicio']), 'fin' => fdate($m['fecha_fin']),
            'total' => (float)$m['total'], 'pagado' => (float)$m['pagado'], 'saldo' => round($m['total'] - $m['pagado'], 2),
        ], Membresia::conSaldo((int)$socioId)));
    }

    private function fecha(string $f, string $def): string
    {
        $d = DateTime::createFromFormat('Y-m-d', $f);
        return ($d && $d->format('Y-m-d') === $f) ? $f : $def;
    }
}
