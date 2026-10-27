<?php
require_once ROOT_PATH . '/app/models/Evaluacion.php';

class EvaluacionesController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $rows = Database::all("SELECT e.*, s.nombres, s.apellidos, s.codigo FROM evaluaciones e JOIN socios s ON s.id = e.socio_id ORDER BY e.fecha DESC, e.id DESC LIMIT 25");
        $k = Database::row("SELECT COUNT(*) total, COALESCE(SUM(fecha >= DATE_FORMAT(CURDATE(),'%Y-%m-01')),0) mes, COUNT(DISTINCT socio_id) socios FROM evaluaciones");
        $sin = (int)Database::val("SELECT COUNT(*) FROM socios s WHERE s.estado = 'activo' AND NOT EXISTS (SELECT 1 FROM evaluaciones e WHERE e.socio_id = s.id AND e.fecha >= DATE_SUB(CURDATE(), INTERVAL 90 DAY))");
        $this->view('evaluaciones/index', ['title' => 'Evaluaciones físicas', 'active' => 'evaluaciones', 'rows' => $rows, 'k' => $k, 'sin' => $sin]);
    }

    public function socio($id = 0): void
    {
        $this->requireAuth();
        $s = Database::row('SELECT * FROM socios WHERE id = ?', [(int)$id]);
        if (!$s) { $this->flash('danger', 'El socio no existe.'); $this->redirect('evaluaciones'); }
        $ev = Evaluacion::deSocio((int)$s['id']);
        $this->view('evaluaciones/socio', ['title' => 'Evaluaciones · ' . $s['nombres'], 'active' => 'evaluaciones', 's' => $s, 'ev' => $ev]);
    }

    private function num(string $k, float $min, float $max): float|false|null
    {
        $v = trim((string)($_POST[$k] ?? ''));
        if ($v === '') return null;
        $f = (float)str_replace(',', '.', $v);
        return ($f >= $min && $f <= $max) ? $f : false;
    }

    public function guardar(): void
    {
        $this->requireAuth();
        $sid = (int)($_POST['socio_id'] ?? 0); $back = 'evaluaciones/socio/' . $sid;
        $this->requirePost($sid ? $back : 'evaluaciones');
        if (!Database::val('SELECT COUNT(*) FROM socios WHERE id = ?', [$sid])) { $this->flash('danger', 'Socio no válido.'); $this->redirect('evaluaciones'); }
        $fecha = (string)($_POST['fecha'] ?? ''); $d = DateTime::createFromFormat('Y-m-d', $fecha);
        $v = ['peso' => $this->num('peso', 20, 400), 'talla' => $this->num('talla', 0.8, 2.5), 'grasa' => $this->num('grasa', 2, 70),
              'cintura' => $this->num('cintura', 30, 250), 'pecho' => $this->num('pecho', 30, 250), 'brazo' => $this->num('brazo', 10, 100), 'pierna' => $this->num('pierna', 20, 150)];
        $err = [];
        if (!$d || $d->format('Y-m-d') !== $fecha || $fecha > date('Y-m-d')) $err[] = 'La fecha no es válida (no puede ser futura).';
        foreach ($v as $k => $x) if ($x === false) $err[] = 'El valor de «' . $k . '» está fuera de rango.';
        if (!$err && count(array_filter($v, fn($x) => $x !== null)) === 0) $err[] = 'Ingresa al menos una medida.';
        if ($err) { $this->flash('danger', implode(' ', $err)); $this->redirect($back); }
        // la talla se hereda de la evaluación anterior si no se indica
        if ($v['talla'] === null) { $t = Database::val('SELECT talla FROM evaluaciones WHERE socio_id = ? AND talla IS NOT NULL ORDER BY fecha DESC, id DESC LIMIT 1', [$sid]); $v['talla'] = $t ? (float)$t : null; }
        Database::exec('INSERT INTO evaluaciones (socio_id,fecha,peso,talla,grasa,cintura,pecho,brazo,pierna,notas) VALUES (?,?,?,?,?,?,?,?,?,?)',
            [$sid, $fecha, $v['peso'], $v['talla'], $v['grasa'], $v['cintura'], $v['pecho'], $v['brazo'], $v['pierna'], mb_substr(trim((string)($_POST['notas'] ?? '')), 0, 255) ?: null]);
        $this->flash('success', 'Evaluación registrada.');
        $this->redirect($back);
    }

    public function eliminar($id = 0): void
    {
        $this->requireAuth();
        $e = Database::row('SELECT * FROM evaluaciones WHERE id = ?', [(int)$id]);
        $this->requirePost($e ? 'evaluaciones/socio/' . $e['socio_id'] : 'evaluaciones');
        if ($e) { Database::exec('DELETE FROM evaluaciones WHERE id = ?', [$e['id']]); $this->flash('success', 'Evaluación eliminada.'); }
        $this->redirect($e ? 'evaluaciones/socio/' . $e['socio_id'] : 'evaluaciones');
    }
}
