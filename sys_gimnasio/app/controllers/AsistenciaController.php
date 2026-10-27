<?php
require_once ROOT_PATH . '/app/models/Socio.php';
require_once ROOT_PATH . '/app/models/Asistencia.php';

class AsistenciaController extends Controller
{
    private const POR_PAGINA = 20;

    /** Pantalla de control de acceso (recepción) */
    public function index(): void
    {
        $this->requireAuth();
        $this->view('asistencia/index', ['title' => 'Control de asistencia', 'active' => 'asistencia', 'aforo' => Asistencia::aforoMax()]);
    }

    /** JSON: aforo, quiénes están dentro y últimos movimientos (polling) */
    public function estado(): void
    {
        $this->requireAuth();
        $dentro = Asistencia::dentro();
        $this->json([
            'max' => Asistencia::aforoMax(), 'dentro_n' => count($dentro), 'hoy' => Asistencia::hoy(),
            'dentro' => array_map(fn($d) => [
                'id' => (int)$d['id'], 'socio_id' => (int)$d['socio_id'], 'codigo' => $d['codigo'], 'nombre' => $d['nombres'] . ' ' . $d['apellidos'],
                'ini' => iniciales($d['nombres'], $d['apellidos']), 'foto' => $d['foto'] ? foto_url($d['foto']) : null,
                'hora' => date('H:i', strtotime($d['ingreso'])), 'min' => (int)round((time() - strtotime($d['ingreso'])) / 60),
            ], $dentro),
            'recientes' => array_map(fn($r) => [
                'nombre' => $r['nombres'] . ' ' . $r['apellidos'], 'codigo' => $r['codigo'],
                'tipo' => $r['salida'] ? 'salida' : 'ingreso', 'hora' => date('H:i', strtotime($r['salida'] ?: $r['ingreso'])),
            ], Asistencia::recientes()),
        ]);
    }

    /** POST JSON: registra ingreso o salida */
    public function registrar(): void
    {
        $this->requireAuth();
        $this->checkJsonPost();
        $codigo = trim((string)($_POST['codigo'] ?? ''));
        $metodo = in_array($_POST['metodo'] ?? '', ['dni', 'qr', 'huella', 'manual'], true) ? $_POST['metodo'] : 'dni';
        if ($codigo === '') $this->json(['ok' => false, 'tipo' => 'vacio', 'titulo' => 'Ingresa un código', 'mensaje' => 'Escanea el QR o digita el DNI o código del socio.'], 422);
        $this->json(Asistencia::procesar($codigo, $metodo));
    }

    /** POST JSON: marca salida de un registro abierto */
    public function salida($id = 0): void
    {
        $this->requireAuth();
        $this->checkJsonPost();
        $n = Database::exec('UPDATE asistencias SET salida = ? WHERE id = ? AND salida IS NULL', [date('Y-m-d H:i:s'), (int)$id]);
        $this->json(['ok' => $n > 0]);
    }

    /** POST JSON: cierra la jornada marcando salida a todos los que siguen dentro */
    public function cerrar(): void
    {
        $this->requireAuth();
        $this->checkJsonPost();
        $n = Database::exec('UPDATE asistencias SET salida = ? WHERE salida IS NULL AND ingreso >= ?', [date('Y-m-d H:i:s'), date('Y-m-d')]);
        if (!empty($_POST['redir'])) {
            $this->flash('success', $n > 0 ? 'Jornada cerrada: se marcó la salida de ' . $n . ' socio' . ($n > 1 ? 's' : '') . '.' : 'No había socios dentro del gimnasio.');
            $this->redirect('asistencia');
        }
        $this->json(['ok' => true, 'cerrados' => $n]);
    }

    public function historial(): void
    {
        $this->requireAuth();
        $f = $this->filtros();
        $pg = paginate(Asistencia::contar($f), self::POR_PAGINA, $this->pageNum());
        $this->view('asistencia/historial', [
            'title' => 'Historial de asistencias', 'active' => 'asistencia', 'f' => $f, 'pg' => $pg,
            'rows' => Asistencia::listar($f, $pg['per'], $pg['offset']), 'res' => Asistencia::resumen($f),
        ]);
    }

    public function exportar(): void
    {
        $this->requireAuth();
        $f = $this->filtros();
        $rows = Asistencia::todos($f);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="asistencias_' . $f['desde'] . '_' . $f['hasta'] . '.csv"');
        $o = fopen('php://output', 'w');
        fwrite($o, "\xEF\xBB\xBF"); // BOM para Excel
        fputcsv($o, ['Código', 'Documento', 'Socio', 'Fecha', 'Ingreso', 'Salida', 'Duración (min)', 'Método'], ';');
        foreach ($rows as $r) {
            $dur = $r['salida'] ? (int)round((strtotime($r['salida']) - strtotime($r['ingreso'])) / 60) : '';
            fputcsv($o, [$r['codigo'], $r['dni'], $r['nombres'] . ' ' . $r['apellidos'], fdate($r['ingreso']), date('H:i', strtotime($r['ingreso'])),
                         $r['salida'] ? date('H:i', strtotime($r['salida'])) : '', $dur, $r['metodo']], ';');
        }
        fclose($o);
        exit;
    }

    /** Carnet imprimible con código QR */
    public function carnet($id = 0): void
    {
        $this->requireAuth();
        $s = Socio::find((int)$id);
        if (!$s) { $this->flash('danger', 'El socio no existe.'); $this->redirect('socios'); }
        $cfg = array_column(Database::all('SELECT clave, valor FROM configuracion'), 'valor', 'clave');
        $this->view('asistencia/carnet', ['title' => 'Carnet ' . $s['codigo'], 's' => $s, 'cfg' => $cfg, 'm' => Asistencia::ultimaMembresia((int)$s['id'])], 'none');
    }

    private function filtros(): array
    {
        $ok = function (string $f, string $def) { $d = DateTime::createFromFormat('Y-m-d', $f); return ($d && $d->format('Y-m-d') === $f) ? $f : $def; };
        $desde = $ok($this->in('desde'), date('Y-m-d', strtotime('-6 days')));
        $hasta = $ok($this->in('hasta'), date('Y-m-d'));
        if ($desde > $hasta) [$desde, $hasta] = [$hasta, $desde];
        if ((new DateTime($desde))->diff(new DateTime($hasta))->days > 366) $desde = date('Y-m-d', strtotime($hasta . ' -366 days'));
        return ['desde' => $desde, 'hasta' => $hasta, 'q' => $this->in('q')];
    }

    private function checkJsonPost(): void
    {
        $tok = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !Auth::verifyCsrf($tok)) {
            $this->json(['ok' => false, 'tipo' => 'csrf', 'titulo' => 'Sesión expirada', 'mensaje' => 'Recarga la página e inténtalo de nuevo.'], 419);
        }
    }
}
