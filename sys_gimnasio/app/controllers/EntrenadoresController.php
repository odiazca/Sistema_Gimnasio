<?php
class EntrenadoresController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $rows = Database::all(
            "SELECT e.*, (SELECT COUNT(*) FROM clases c WHERE c.entrenador_id = e.id AND c.estado = 1) n_clases,
                    (SELECT COUNT(*) FROM clases c JOIN horarios_clase h ON h.clase_id = c.id WHERE c.entrenador_id = e.id AND c.estado = 1) n_horarios,
                    (SELECT GROUP_CONCAT(c.nombre ORDER BY c.nombre SEPARATOR ', ') FROM clases c WHERE c.entrenador_id = e.id AND c.estado = 1) clases
             FROM entrenadores e ORDER BY e.estado DESC, e.nombres"
        );
        $this->view('entrenadores/index', ['title' => 'Entrenadores', 'active' => 'entrenadores', 'rows' => $rows]);
    }

    public function guardar(): void
    {
        $this->requireAuth();
        $this->requirePost('entrenadores');
        $id = (int)($_POST['id'] ?? 0);
        $n = trim((string)($_POST['nombres'] ?? '')); $a = trim((string)($_POST['apellidos'] ?? ''));
        $esp = trim((string)($_POST['especialidad'] ?? '')); $tel = trim((string)($_POST['telefono'] ?? '')); $mail = trim((string)($_POST['email'] ?? ''));
        $err = [];
        if ($n === '' || $a === '') $err[] = 'Nombres y apellidos son obligatorios.';
        if ($mail !== '' && !filter_var($mail, FILTER_VALIDATE_EMAIL)) $err[] = 'El correo no es válido.';
        if ($tel !== '' && !preg_match('/^[0-9 +()-]{6,20}$/', $tel)) $err[] = 'El teléfono no es válido.';
        if ($err) { $this->flash('danger', implode(' ', $err)); $this->redirect('entrenadores'); }
        if ($id) {
            Database::exec('UPDATE entrenadores SET nombres=?, apellidos=?, especialidad=?, telefono=?, email=? WHERE id=?', [$n, $a, $esp ?: null, $tel ?: null, $mail ?: null, $id]);
            $this->flash('success', 'Entrenador actualizado.');
        } else {
            Database::exec('INSERT INTO entrenadores (nombres, apellidos, especialidad, telefono, email) VALUES (?,?,?,?,?)', [$n, $a, $esp ?: null, $tel ?: null, $mail ?: null]);
            $this->flash('success', 'Entrenador registrado.');
        }
        $this->redirect('entrenadores');
    }

    public function estado($id = 0): void
    {
        $this->requireAuth();
        $this->requirePost('entrenadores');
        Database::exec('UPDATE entrenadores SET estado = 1 - estado WHERE id = ?', [(int)$id]);
        $this->flash('success', 'Estado del entrenador actualizado.');
        $this->redirect('entrenadores');
    }

    public function eliminar($id = 0): void
    {
        $this->requireAuth();
        $this->requirePost('entrenadores');
        $id = (int)$id;
        if ((int)Database::val('SELECT COUNT(*) FROM clases WHERE entrenador_id = ?', [$id]) > 0) {
            $this->flash('warning', 'El entrenador tiene clases asignadas. Reasígnalas o desactívalo.');
        } else {
            Database::exec('DELETE FROM entrenadores WHERE id = ?', [$id]);
            $this->flash('success', 'Entrenador eliminado.');
        }
        $this->redirect('entrenadores');
    }
}
