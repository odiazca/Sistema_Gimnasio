<?php
require_once ROOT_PATH . '/app/models/Rutina.php';

class RutinasController extends Controller
{
    public function index(): void
    {
        $this->requireAuth(); Rutina::ensure();
        $vista = $this->in('vista') === 'ejercicios' ? 'ejercicios' : 'rutinas';
        $rutinas = Database::all("SELECT r.*, (SELECT COUNT(*) FROM rutina_ejercicios re WHERE re.rutina_id = r.id) n_ej,
              (SELECT COUNT(DISTINCT re.dia) FROM rutina_ejercicios re WHERE re.rutina_id = r.id) n_dias,
              (SELECT COUNT(*) FROM rutina_socios rs WHERE rs.rutina_id = r.id AND rs.estado = 'activa') n_socios
              FROM rutinas r ORDER BY r.estado DESC, r.nombre");
        $f = ['q' => $this->in('q'), 'grupo' => $this->in('grupo')];
        $w = '1=1'; $p = [];
        if ($f['q'] !== '') { $w .= ' AND nombre LIKE ?'; $p[] = '%' . $f['q'] . '%'; }
        if ($f['grupo'] !== '') { $w .= ' AND grupo = ?'; $p[] = $f['grupo']; }
        $ejs = $vista === 'ejercicios' ? Database::all("SELECT * FROM ejercicios WHERE $w ORDER BY estado DESC, grupo, nombre", $p) : [];
        $this->view('rutinas/index', ['title' => 'Rutinas', 'active' => 'rutinas', 'vista' => $vista, 'rutinas' => $rutinas, 'ejs' => $ejs, 'f' => $f]);
    }

    public function crear(): void { $this->form(null); }
    public function editar($id = 0): void
    {
        $r = Rutina::find((int)$id);
        if (!$r) { $this->flash('danger', 'La rutina no existe.'); $this->redirect('rutinas'); }
        $this->form($r);
    }

    private function form(?array $r): void
    {
        $this->requireAuth(); Rutina::ensure();
        $this->view('rutinas/form', [
            'title' => $r ? 'Editar rutina' : 'Nueva rutina', 'active' => 'rutinas', 'r' => $r,
            'det' => $r ? Rutina::detalle((int)$r['id']) : [],
            'ejs' => Database::all('SELECT id, nombre, grupo FROM ejercicios WHERE estado = 1 ORDER BY grupo, nombre'),
        ]);
    }

    public function guardar(): void
    {
        $this->requireAuth(); Rutina::ensure();
        $this->requirePost('rutinas');
        $id = (int)($_POST['id'] ?? 0); $back = $id ? 'rutinas/editar/' . $id : 'rutinas/crear';
        $nom = trim((string)($_POST['nombre'] ?? '')); $obj = (string)($_POST['objetivo'] ?? ''); $niv = (string)($_POST['nivel'] ?? ''); $desc = trim((string)($_POST['descripcion'] ?? ''));
        $rows = []; $in = $_POST['ej'] ?? [];
        if (is_array($in)) foreach ($in as $x) {
            $eid = (int)($x['ejercicio_id'] ?? 0); $dia = (int)($x['dia'] ?? 0); $ser = (int)($x['series'] ?? 0);
            $rep = trim((string)($x['reps'] ?? '')); $des = (int)($x['descanso'] ?? 0);
            if (!$eid) continue;
            if ($dia < 1 || $dia > 7 || $ser < 1 || $ser > 20 || $rep === '' || mb_strlen($rep) > 20 || $des < 0 || $des > 900) { $this->flash('danger', 'Revisa los ejercicios: día (1-7), series (1-20) y repeticiones son obligatorios.'); $this->redirect($back); }
            if (!Database::val('SELECT COUNT(*) FROM ejercicios WHERE id = ?', [$eid])) continue;
            $rows[] = [$eid, $dia, $ser, $rep, $des ?: null];
        }
        $err = [];
        if ($nom === '') $err[] = 'El nombre es obligatorio.';
        if (!in_array($obj, Rutina::OBJETIVOS, true)) $obj = '';
        if (!in_array($niv, Rutina::NIVELES, true)) $niv = '';
        if (!$rows) $err[] = 'Agrega al menos un ejercicio.';
        if ($err) { $this->flash('danger', implode(' ', $err)); $this->redirect($back); }
        $pdo = Database::conn(); $pdo->beginTransaction();
        try {
            if ($id) { Database::exec('UPDATE rutinas SET nombre=?, objetivo=?, nivel=?, descripcion=? WHERE id=?', [$nom, $obj ?: null, $niv ?: null, $desc ?: null, $id]); Database::exec('DELETE FROM rutina_ejercicios WHERE rutina_id = ?', [$id]); }
            else { Database::exec('INSERT INTO rutinas (nombre,objetivo,nivel,descripcion) VALUES (?,?,?,?)', [$nom, $obj ?: null, $niv ?: null, $desc ?: null]); $id = (int)$pdo->lastInsertId(); }
            $o = 0; foreach ($rows as $x) Database::exec('INSERT INTO rutina_ejercicios (rutina_id,ejercicio_id,dia,orden,series,repeticiones,descanso_seg) VALUES (?,?,?,?,?,?,?)', [$id, $x[0], $x[1], $o++, $x[2], $x[3], $x[4]]);
            $pdo->commit();
        } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
        $this->flash('success', 'Rutina guardada.');
        $this->redirect('rutinas/ver/' . $id);
    }

    public function ver($id = 0): void
    {
        $this->requireAuth();
        $r = Rutina::find((int)$id);
        if (!$r) { $this->flash('danger', 'La rutina no existe.'); $this->redirect('rutinas'); }
        $det = Rutina::detalle((int)$r['id']);
        $asig = Database::all("SELECT rs.*, s.nombres, s.apellidos, s.codigo, s.telefono FROM rutina_socios rs JOIN socios s ON s.id = rs.socio_id WHERE rs.rutina_id = ? ORDER BY rs.estado, rs.id DESC LIMIT 50", [$r['id']]);
        $this->view('rutinas/ver', ['title' => $r['nombre'], 'active' => 'rutinas', 'r' => $r, 'det' => $det, 'asig' => $asig]);
    }

    public function estado($id = 0): void
    {
        $this->requireAuth(); $this->requirePost('rutinas');
        Database::exec('UPDATE rutinas SET estado = 1 - estado WHERE id = ?', [(int)$id]);
        $this->flash('success', 'Estado de la rutina actualizado.'); $this->redirect('rutinas');
    }

    public function eliminar($id = 0): void
    {
        $this->requireAuth(); $this->requirePost('rutinas'); Rutina::ensure();
        if (Database::val('SELECT COUNT(*) FROM rutina_socios WHERE rutina_id = ?', [(int)$id])) $this->flash('warning', 'La rutina fue asignada a socios. Puedes desactivarla en lugar de eliminarla.');
        else { Database::exec('DELETE FROM rutinas WHERE id = ?', [(int)$id]); $this->flash('success', 'Rutina eliminada.'); }
        $this->redirect('rutinas');
    }

    public function asignar($id = 0): void
    {
        $this->requireAuth(); Rutina::ensure();
        $r = Rutina::find((int)$id);
        $back = 'rutinas/ver/' . (int)$id;
        $this->requirePost($back);
        if (!$r) $this->redirect('rutinas');
        $sid = (int)($_POST['socio_id'] ?? 0);
        if (!Database::val('SELECT COUNT(*) FROM socios WHERE id = ?', [$sid])) { $this->flash('danger', 'Selecciona un socio de la lista.'); $this->redirect($back); }
        if (!$r['estado']) { $this->flash('danger', 'La rutina está inactiva.'); $this->redirect($back); }
        $pdo = Database::conn(); $pdo->beginTransaction();
        Database::exec("UPDATE rutina_socios SET estado = 'finalizada', fecha_fin = CURDATE() WHERE socio_id = ? AND estado = 'activa'", [$sid]);
        Database::exec('INSERT INTO rutina_socios (socio_id, rutina_id, fecha_inicio) VALUES (?,?,CURDATE())', [$sid, $r['id']]);
        $pdo->commit();
        $this->flash('success', 'Rutina asignada. Si el socio tenía otra rutina activa, se finalizó.');
        $this->redirect($back);
    }

    public function finalizar($id = 0): void
    {
        $this->requireAuth(); $this->requirePost('rutinas');
        $a = Database::row('SELECT * FROM rutina_socios WHERE id = ?', [(int)$id]);
        if ($a) Database::exec("UPDATE rutina_socios SET estado = 'finalizada', fecha_fin = CURDATE() WHERE id = ?", [$a['id']]);
        $this->flash('success', 'Asignación finalizada.');
        $this->redirect($a ? 'rutinas/ver/' . $a['rutina_id'] : 'rutinas');
    }

    // ---- ejercicios ----
    public function guardarEjercicio(): void
    {
        $this->requireAuth(); Rutina::ensure();
        $this->requirePost('rutinas?vista=ejercicios');
        $id = (int)($_POST['id'] ?? 0); $n = trim((string)($_POST['nombre'] ?? '')); $g = (string)($_POST['grupo'] ?? '');
        $eq = trim((string)($_POST['equipo'] ?? '')); $vid = trim((string)($_POST['video_url'] ?? ''));
        $err = [];
        if ($n === '') $err[] = 'El nombre es obligatorio.';
        if (!in_array($g, Rutina::GRUPOS, true)) $err[] = 'Selecciona un grupo muscular.';
        if ($vid !== '' && !preg_match('#^https?://#i', $vid)) $err[] = 'El enlace del video debe comenzar con http:// o https://.';
        if ($err) { $this->flash('danger', implode(' ', $err)); $this->redirect('rutinas?vista=ejercicios'); }
        if ($id) Database::exec('UPDATE ejercicios SET nombre=?, grupo=?, equipo=?, video_url=? WHERE id=?', [$n, $g, $eq ?: null, $vid ?: null, $id]);
        else Database::exec('INSERT INTO ejercicios (nombre,grupo,equipo,video_url) VALUES (?,?,?,?)', [$n, $g, $eq ?: null, $vid ?: null]);
        $this->flash('success', 'Ejercicio guardado.');
        $this->redirect('rutinas?vista=ejercicios');
    }

    public function eliminarEjercicio($id = 0): void
    {
        $this->requireAuth(); $this->requirePost('rutinas?vista=ejercicios'); Rutina::ensure();
        if (Database::val('SELECT COUNT(*) FROM rutina_ejercicios WHERE ejercicio_id = ?', [(int)$id])) $this->flash('warning', 'El ejercicio se usa en alguna rutina. Puedes desactivarlo.');
        else { Database::exec('DELETE FROM ejercicios WHERE id = ?', [(int)$id]); $this->flash('success', 'Ejercicio eliminado.'); }
        $this->redirect('rutinas?vista=ejercicios');
    }

    public function estadoEjercicio($id = 0): void
    {
        $this->requireAuth(); $this->requirePost('rutinas?vista=ejercicios');
        Database::exec('UPDATE ejercicios SET estado = 1 - estado WHERE id = ?', [(int)$id]);
        $this->flash('success', 'Estado actualizado.'); $this->redirect('rutinas?vista=ejercicios');
    }
}
