<?php
require_once ROOT_PATH . '/app/models/Clase.php';

class ClasesController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $vista = $this->in('vista') === 'catalogo' ? 'catalogo' : 'semana';
        $this->view('clases/index', [
            'title' => 'Clases y horarios', 'active' => 'clases', 'vista' => $vista,
            'semana' => Clase::semana(), 'catalogo' => Clase::catalogo(), 'ents' => Clase::entrenadoresActivos(),
        ]);
    }

    // ---------- catálogo de clases ----------
    public function guardarClase(): void
    {
        $this->requireAuth();
        $this->requirePost('clases?vista=catalogo');
        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim((string)($_POST['nombre'] ?? '')); $desc = trim((string)($_POST['descripcion'] ?? ''));
        $ent = (int)($_POST['entrenador_id'] ?? 0) ?: null; $cupo = (int)($_POST['cupo'] ?? 0);
        $color = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['color'] ?? '') ? $_POST['color'] : '#ff5a1f';
        $err = [];
        if ($nombre === '') $err[] = 'El nombre de la clase es obligatorio.';
        if ($cupo < 1 || $cupo > 300) $err[] = 'El cupo debe estar entre 1 y 300.';
        if ($ent && !Database::val('SELECT COUNT(*) FROM entrenadores WHERE id = ?', [$ent])) $err[] = 'El entrenador no existe.';
        if ($err) { $this->flash('danger', implode(' ', $err)); $this->redirect('clases?vista=catalogo'); }
        if ($id) {
            Database::exec('UPDATE clases SET nombre=?, descripcion=?, entrenador_id=?, cupo=?, color=? WHERE id=?', [$nombre, $desc ?: null, $ent, $cupo, $color, $id]);
            $this->flash('success', 'Clase actualizada.');
        } else {
            Database::exec('INSERT INTO clases (nombre, descripcion, entrenador_id, cupo, color) VALUES (?,?,?,?,?)', [$nombre, $desc ?: null, $ent, $cupo, $color]);
            $this->flash('success', 'Clase creada. Ahora agrega sus horarios en la vista semanal.');
        }
        $this->redirect('clases?vista=catalogo');
    }

    public function estadoClase($id = 0): void
    {
        $this->requireAuth();
        $this->requirePost('clases?vista=catalogo');
        Database::exec('UPDATE clases SET estado = 1 - estado WHERE id = ?', [(int)$id]);
        $this->flash('success', 'Estado de la clase actualizado.');
        $this->redirect('clases?vista=catalogo');
    }

    public function eliminarClase($id = 0): void
    {
        $this->requireAuth();
        $this->requirePost('clases?vista=catalogo');
        $id = (int)$id;
        $res = (int)Database::val('SELECT COUNT(*) FROM reservas r JOIN horarios_clase h ON h.id = r.horario_id WHERE h.clase_id = ?', [$id]);
        if ($res > 0) $this->flash('warning', 'La clase tiene reservas registradas. Puedes desactivarla en lugar de eliminarla.');
        else { Database::exec('DELETE FROM clases WHERE id = ?', [$id]); $this->flash('success', 'Clase eliminada junto con sus horarios.'); }
        $this->redirect('clases?vista=catalogo');
    }

    // ---------- horarios ----------
    public function guardarHorario(): void
    {
        $this->requireAuth();
        $this->requirePost('clases');
        $id = (int)($_POST['id'] ?? 0); $clase = (int)($_POST['clase_id'] ?? 0); $dia = (int)($_POST['dia_semana'] ?? 0);
        $ini = (string)($_POST['hora_inicio'] ?? ''); $fin = (string)($_POST['hora_fin'] ?? ''); $sala = trim((string)($_POST['sala'] ?? ''));
        $okT = fn($t) => (bool)preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t);
        $err = null;
        if (!Database::val('SELECT COUNT(*) FROM clases WHERE id = ?', [$clase])) $err = 'Selecciona una clase válida.';
        elseif ($dia < 1 || $dia > 7) $err = 'Selecciona un día válido.';
        elseif (!$okT($ini) || !$okT($fin)) $err = 'Las horas no son válidas.';
        elseif ($fin <= $ini) $err = 'La hora de fin debe ser posterior a la de inicio.';
        else {
            $c = Clase::conflicto($clase, $dia, $ini . ':00', $fin . ':00', $sala, $id);
            if ($c) $err = 'Conflicto de horario con «' . $c['nombre'] . '» (' . substr($c['hora_inicio'], 0, 5) . '–' . substr($c['hora_fin'], 0, 5) . ', ' . ($c['sala'] ?: 'sin sala') . '): misma sala o mismo entrenador.';
        }
        if ($err) { $this->flash('danger', $err); $this->redirect('clases'); }
        if ($id) { Database::exec('UPDATE horarios_clase SET clase_id=?, dia_semana=?, hora_inicio=?, hora_fin=?, sala=? WHERE id=?', [$clase, $dia, $ini, $fin, $sala ?: null, $id]); $this->flash('success', 'Horario actualizado.'); }
        else { Database::exec('INSERT INTO horarios_clase (clase_id, dia_semana, hora_inicio, hora_fin, sala) VALUES (?,?,?,?,?)', [$clase, $dia, $ini, $fin, $sala ?: null]); $this->flash('success', 'Horario agregado.'); }
        $this->redirect('clases');
    }

    public function eliminarHorario($id = 0): void
    {
        $this->requireAuth();
        $this->requirePost('clases');
        Database::exec('DELETE FROM horarios_clase WHERE id = ?', [(int)$id]);
        $this->flash('success', 'Horario eliminado.');
        $this->redirect('clases');
    }

    // ---------- sesión (una clase en una fecha) ----------
    public function sesion($horarioId = 0): void
    {
        $this->requireAuth();
        $h = Clase::horario((int)$horarioId);
        if (!$h) { $this->flash('danger', 'El horario no existe.'); $this->redirect('clases'); }
        $fecha = $this->fechaSesion($h, $this->in('fecha'));
        $res = Clase::reservas((int)$h['id'], $fecha);
        $activas = count(array_filter($res, fn($r) => $r['estado'] !== 'cancelada'));
        $this->view('clases/sesion', [
            'title' => $h['nombre'], 'active' => 'clases', 'h' => $h, 'fecha' => $fecha, 'res' => $res, 'activas' => $activas,
            'prev' => date('Y-m-d', strtotime($fecha . ' -7 days')), 'next' => date('Y-m-d', strtotime($fecha . ' +7 days')),
        ]);
    }

    public function reservar($horarioId = 0): void
    {
        $this->requireAuth();
        $h = Clase::horario((int)$horarioId);
        $this->requirePost('clases');
        if (!$h) $this->redirect('clases');
        $fecha = $this->fechaSesion($h, (string)($_POST['fecha'] ?? ''));
        $back = 'clases/sesion/' . $h['id'] . '?fecha=' . $fecha;
        $socio = Socio_find((int)($_POST['socio_id'] ?? 0));
        if (!$socio) { $this->flash('danger', 'Selecciona un socio de la lista.'); $this->redirect($back); }
        if ($fecha < date('Y-m-d')) { $this->flash('danger', 'No se pueden crear reservas en fechas pasadas.'); $this->redirect($back); }
        if ($socio['estado'] !== 'activo') { $this->flash('danger', 'El socio está ' . $socio['estado'] . ' y no puede reservar.'); $this->redirect($back); }
        if (!Clase::membresiaEn((int)$socio['id'], $fecha)) { $this->flash('danger', $socio['nombres'] . ' no tiene una membresía vigente para el ' . fdate($fecha) . '.'); $this->redirect($back); }
        $dup = Database::val("SELECT COUNT(*) FROM reservas WHERE horario_id=? AND socio_id=? AND fecha=? AND estado <> 'cancelada'", [$h['id'], $socio['id'], $fecha]);
        if ($dup) { $this->flash('warning', $socio['nombres'] . ' ya tiene una reserva en esta clase.'); $this->redirect($back); }
        $n = (int)Database::val("SELECT COUNT(*) FROM reservas WHERE horario_id=? AND fecha=? AND estado <> 'cancelada'", [$h['id'], $fecha]);
        if ($n >= (int)$h['cupo']) { $this->flash('danger', 'La clase está llena (' . $h['cupo'] . ' cupos).'); $this->redirect($back); }
        Database::exec('INSERT INTO reservas (horario_id, socio_id, fecha) VALUES (?,?,?)', [$h['id'], $socio['id'], $fecha]);
        $this->flash('success', 'Reserva registrada para ' . $socio['nombres'] . ' ' . $socio['apellidos'] . '.');
        $this->redirect($back);
    }

    public function reservaEstado($id = 0): void
    {
        $this->requireAuth();
        $this->requirePost('clases');
        $r = Database::row('SELECT * FROM reservas WHERE id = ?', [(int)$id]);
        if (!$r) $this->redirect('clases');
        $back = 'clases/sesion/' . $r['horario_id'] . '?fecha=' . $r['fecha'];
        $nuevo = (string)($_POST['estado'] ?? '');
        if (!in_array($nuevo, ['reservada', 'asistio', 'cancelada'], true)) $this->redirect($back);
        if ($nuevo === 'asistio' && $r['fecha'] > date('Y-m-d')) { $this->flash('warning', 'No se puede marcar asistencia de una clase futura.'); $this->redirect($back); }
        Database::exec('UPDATE reservas SET estado = ? WHERE id = ?', [$nuevo, $r['id']]);
        $this->flash('success', ['asistio' => 'Asistencia marcada.', 'cancelada' => 'Reserva cancelada.', 'reservada' => 'Reserva restablecida.'][$nuevo]);
        $this->redirect($back);
    }

    private function fechaSesion(array $h, string $f): string
    {
        $d = DateTime::createFromFormat('Y-m-d', $f);
        if (!$d || $d->format('Y-m-d') !== $f || (int)$d->format('N') !== (int)$h['dia_semana']) return Clase::proximaFecha((int)$h['dia_semana']);
        return $f;
    }
}

function Socio_find(int $id): ?array { return Database::row('SELECT * FROM socios WHERE id = ?', [$id]); }
