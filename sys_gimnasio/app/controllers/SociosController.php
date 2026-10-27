<?php
require_once ROOT_PATH . '/app/models/Socio.php';
require_once ROOT_PATH . '/app/models/Plan.php';
require_once ROOT_PATH . '/app/models/Membresia.php';
require_once ROOT_PATH . '/app/models/Pago.php';

class SociosController extends Controller
{
    private const POR_PAGINA = 12;
    private const FIELDS = ['dni', 'nombres', 'apellidos', 'fecha_nacimiento', 'genero', 'telefono', 'email', 'direccion',
                            'contacto_emergencia', 'telefono_emergencia', 'objetivo', 'estado', 'observaciones'];

    public function index(): void
    {
        $this->requireAuth();
        $f = ['q' => $this->in('q'), 'estado' => $this->in('estado'), 'mem' => $this->in('mem')];
        $pg = paginate(Socio::contar($f), self::POR_PAGINA, $this->pageNum());
        $this->view('socios/index', [
            'title' => 'Socios', 'active' => 'socios', 'f' => $f, 'pg' => $pg,
            'socios' => Socio::listar($f, $pg['per'], $pg['offset']),
            'res' => Socio::resumen(),
        ]);
    }

    public function crear(): void
    {
        $this->requireAuth();
        $s = ['estado' => 'activo', 'genero' => 'M', 'fecha_inicio' => date('Y-m-d')];
        if ((int)($_GET['lead'] ?? 0) > 0 && Permisos::can('crm')) { // viene del CRM: precargar datos del prospecto
            require_once ROOT_PATH . '/app/models/Crm.php'; Crm::ensure();
            $l = Crm::lead((int)$_GET['lead']);
            if ($l && !$l['socio_id']) $s = array_merge($s, ['nombres' => $l['nombres'], 'apellidos' => $l['apellidos'], 'telefono' => $l['telefono'], 'email' => $l['email'], 'objetivo' => $l['interes'], 'lead_id' => (int)$l['id']]);
        }
        $this->form($s, [], 0);
    }

    public function editar($id = 0): void
    {
        $this->requireAuth();
        $s = Socio::find((int)$id);
        if (!$s) { $this->flash('danger', 'El socio no existe.'); $this->redirect('socios'); }
        $this->form($s, [], (int)$id);
    }

    private function form(array $s, array $errors, int $id): void
    {
        $this->view('socios/form', [
            'title' => $id ? 'Editar socio' : 'Nuevo socio', 'active' => 'socios',
            's' => $s, 'errors' => $errors, 'id' => $id, 'planes' => Plan::activos(),
        ]);
    }

    public function guardar(): void
    {
        $this->requireAuth();
        $this->requirePost('socios');
        $id = (int)($_POST['id'] ?? 0);
        $leadId = !$id ? (int)($_POST['lead_id'] ?? 0) : 0;
        if ($leadId) { require_once ROOT_PATH . '/app/models/Crm.php'; Crm::ensure(); } // crea tablas antes de la transacción

        $d = [];
        foreach (self::FIELDS as $k) $d[$k] = trim((string)($_POST[$k] ?? ''));
        $errors = $this->validar($d, $id);
        $foto = null;
        if (empty($errors) && !empty($_FILES['foto']['name'])) {
            [$foto, $err] = $this->subirFoto($_FILES['foto']);
            if ($err) $errors[] = $err;
        }
        $plan = null;
        if (!$id && !empty($_POST['plan_id'])) {
            $plan = Plan::find((int)$_POST['plan_id']);
            if (!$plan) $errors[] = 'El plan seleccionado no existe.';
        }
        if ($errors) {
            $this->form(array_merge($_POST, $d), $errors, $id);
            return;
        }

        $pdo = Database::conn();
        $pdo->beginTransaction();
        try {
            $vals = [
                $d['dni'] ?: null, $d['nombres'], $d['apellidos'], $d['fecha_nacimiento'] ?: null, $d['genero'] ?: 'M',
                $d['telefono'] ?: null, $d['email'] ?: null, $d['direccion'] ?: null, $d['contacto_emergencia'] ?: null,
                $d['telefono_emergencia'] ?: null, $d['objetivo'] ?: null, $d['estado'], $d['observaciones'] ?: null,
            ];
            if ($id) {
                Database::exec('UPDATE socios SET dni=?, nombres=?, apellidos=?, fecha_nacimiento=?, genero=?, telefono=?, email=?, direccion=?,
                    contacto_emergencia=?, telefono_emergencia=?, objetivo=?, estado=?, observaciones=? WHERE id=?', array_merge($vals, [$id]));
                if ($foto) Database::exec('UPDATE socios SET foto=? WHERE id=?', [$foto, $id]);
                $msg = 'Socio actualizado correctamente.';
            } else {
                Database::exec('INSERT INTO socios (codigo, dni, nombres, apellidos, fecha_nacimiento, genero, telefono, email, direccion,
                    contacto_emergencia, telefono_emergencia, objetivo, estado, observaciones, fecha_registro) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    array_merge(['T' . bin2hex(random_bytes(5))], $vals, [date('Y-m-d')]));
                $id = (int)$pdo->lastInsertId();
                Database::exec('UPDATE socios SET codigo=? WHERE id=?', [sprintf('SOC-%04d', $id), $id]);
                if ($foto) Database::exec('UPDATE socios SET foto=? WHERE id=?', [$foto, $id]);
                $msg = 'Socio registrado: ' . sprintf('SOC-%04d', $id) . '.';
                if ($leadId && Crm::lead($leadId)) { Crm::ganar($leadId, $id, (int)Auth::user()['id']); $msg .= ' El prospecto del CRM quedó como «Ganado».'; }

                if ($plan) {
                    $inicio = $this->fechaValida($_POST['fecha_inicio'] ?? '') ?: date('Y-m-d');
                    $desc = max(0, min((float)($_POST['descuento'] ?? 0), (float)$plan['precio']));
                    $mid = Membresia::crear($id, $plan, $inicio, $desc, Auth::user()['id']);
                    $total = (float)$plan['precio'] - $desc;
                    $pago = max(0, min((float)($_POST['monto_pago'] ?? 0), $total));
                    if ($pago > 0) {
                        $met = isset(Pago::metodos()[$_POST['metodo'] ?? '']) ? $_POST['metodo'] : 'efectivo';
                        Pago::crear($mid, $id, $pago, $met, 'Membresía ' . $plan['nombre'], Auth::user()['id']);
                    }
                    $msg .= ' Membresía ' . $plan['nombre'] . ' registrada' . ($pago > 0 ? ' con pago de ' . money($pago) : ' (pago pendiente)') . '.';
                }
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            $this->form(array_merge($_POST, $d), ['No se pudo guardar: ' . $e->getMessage()], (int)($_POST['id'] ?? 0));
            return;
        }
        $this->flash('success', $msg);
        $this->redirect('socios/ver/' . $id);
    }

    public function ver($id = 0): void
    {
        $this->requireAuth();
        $id = (int)$id;
        $s = Socio::find($id);
        if (!$s) { $this->flash('danger', 'El socio no existe.'); $this->redirect('socios'); }

        $mems = Database::all(
            'SELECT m.*, p.nombre plan, p.color plan_color, (m.precio - m.descuento) total, ' . Membresia::PAGADO . ' pagado
             FROM membresias m JOIN planes p ON p.id = m.plan_id WHERE m.socio_id = ? ORDER BY m.fecha_inicio DESC, m.id DESC', [$id]);
        $pagos = Database::all('SELECT pg.*, u.nombre cajero FROM pagos pg LEFT JOIN usuarios u ON u.id = pg.usuario_id WHERE pg.socio_id = ? ORDER BY pg.fecha_pago DESC LIMIT 30', [$id]);
        $asis = Database::all('SELECT * FROM asistencias WHERE socio_id = ? ORDER BY ingreso DESC LIMIT 20', [$id]);
        $stats = [
            'asis_mes' => (int)Database::val('SELECT COUNT(*) FROM asistencias WHERE socio_id = ? AND ingreso >= ?', [$id, date('Y-m-01')]),
            'asis_total' => (int)Database::val('SELECT COUNT(*) FROM asistencias WHERE socio_id = ?', [$id]),
            'pagado' => (float)Database::val('SELECT COALESCE(SUM(monto),0) FROM pagos WHERE socio_id = ?', [$id]),
            'deuda' => array_sum(array_map(fn($m) => $m['estado'] === 'cancelada' ? 0 : max(0, $m['total'] - $m['pagado']), $mems)),
            'ultima' => Database::val('SELECT MAX(ingreso) FROM asistencias WHERE socio_id = ?', [$id]) ?: null,
        ];
        // la "actual" es la de mayor fecha_fin
        $mx = null;
        foreach ($mems as $m) { if ($m['estado'] !== 'cancelada' && ($mx === null || $m['fecha_fin'] > $mx['fecha_fin'])) $mx = $m; }
        $this->view('socios/ver', [
            'title' => $s['nombres'] . ' ' . $s['apellidos'], 'active' => 'socios',
            's' => $s, 'mems' => $mems, 'pagos' => $pagos, 'asis' => $asis, 'stats' => $stats, 'actual' => $mx,
        ]);
    }

    public function estado($id = 0): void
    {
        $this->requireAuth();
        $this->requirePost('socios');
        $nuevo = $this->in('estado');
        if (in_array($nuevo, ['activo', 'inactivo', 'suspendido'], true)) {
            Database::exec('UPDATE socios SET estado = ? WHERE id = ?', [$nuevo, (int)$id]);
            $this->flash('success', 'Estado del socio actualizado a "' . $nuevo . '".');
        }
        $this->redirect('socios/ver/' . (int)$id);
    }

    public function eliminar($id = 0): void
    {
        $this->requireAuth();
        $this->requirePost('socios');
        $id = (int)$id;
        $uso = (int)Database::val('SELECT (SELECT COUNT(*) FROM membresias WHERE socio_id=?) + (SELECT COUNT(*) FROM pagos WHERE socio_id=?)', [$id, $id]);
        if ($uso > 0) {
            $this->flash('warning', 'No se puede eliminar: el socio tiene membresías o pagos registrados. Puedes marcarlo como inactivo.');
            $this->redirect('socios/ver/' . $id);
        }
        Database::exec('DELETE FROM socios WHERE id = ?', [$id]);
        $this->flash('success', 'Socio eliminado.');
        $this->redirect('socios');
    }

    /** Autocompletado (JSON) */
    public function buscar(): void
    {
        $this->requireAuth();
        $q = trim((string)($_GET['q'] ?? ''));
        if (mb_strlen($q) < 2) $this->json([]);
        $rows = Socio::buscar($q);
        $this->json(array_map(fn($r) => [
            'id' => (int)$r['id'], 'codigo' => $r['codigo'], 'nombre' => $r['nombres'] . ' ' . $r['apellidos'],
            'dni' => $r['dni'], 'plan' => $r['plan'], 'vence' => $r['fecha_fin'],
            'ini' => iniciales($r['nombres'], $r['apellidos']),
        ], $rows));
    }

    // ---------- helpers ----------
    private function fechaValida(string $f): ?string
    {
        $d = DateTime::createFromFormat('Y-m-d', $f);
        return ($d && $d->format('Y-m-d') === $f) ? $f : null;
    }

    private function validar(array $d, int $id): array
    {
        $e = [];
        if ($d['nombres'] === '') $e[] = 'Los nombres son obligatorios.';
        if ($d['apellidos'] === '') $e[] = 'Los apellidos son obligatorios.';
        if ($d['dni'] !== '') {
            if (!preg_match('/^[A-Za-z0-9]{8,12}$/', $d['dni'])) $e[] = 'El documento debe tener entre 8 y 12 caracteres alfanuméricos.';
            elseif (Socio::dniExiste($d['dni'], $id)) $e[] = 'Ya existe un socio con ese documento.';
        }
        if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $e[] = 'El correo electrónico no es válido.';
        if ($d['telefono'] !== '' && !preg_match('/^[0-9 +()-]{6,20}$/', $d['telefono'])) $e[] = 'El teléfono no es válido.';
        if ($d['fecha_nacimiento'] !== '') {
            if (!$this->fechaValida($d['fecha_nacimiento']) || $d['fecha_nacimiento'] > date('Y-m-d')) $e[] = 'La fecha de nacimiento no es válida.';
        }
        if (!in_array($d['genero'], ['M', 'F', 'O'], true)) $e[] = 'Selecciona un género válido.';
        if (!in_array($d['estado'], ['activo', 'inactivo', 'suspendido'], true)) $e[] = 'Estado no válido.';
        return $e;
    }

    /** @return array{0:?string,1:?string} [ruta relativa, error] */
    private function subirFoto(array $f): array
    {
        if ($f['error'] !== UPLOAD_ERR_OK) return [null, 'No se pudo subir la foto.'];
        if ($f['size'] > 2 * 1024 * 1024) return [null, 'La foto no debe superar 2 MB.'];
        $info = @getimagesize($f['tmp_name']);
        $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$info[2] ?? 0] ?? null;
        if (!$ext) return [null, 'La foto debe ser JPG, PNG o WEBP.'];
        $dir = ROOT_PATH . '/public/uploads/socios';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $name = bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) return [null, 'No se pudo guardar la foto.'];
        return ['uploads/socios/' . $name, null];
    }
}
