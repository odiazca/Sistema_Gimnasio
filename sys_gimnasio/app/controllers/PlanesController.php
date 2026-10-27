<?php
require_once ROOT_PATH . '/app/models/Plan.php';

class PlanesController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $this->view('planes/index', ['title' => 'Planes', 'active' => 'membresias', 'planes' => Plan::conEstadisticas()]);
    }

    public function guardar(): void
    {
        $this->requireAuth();
        $this->requirePost('planes');
        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $desc = trim((string)($_POST['descripcion'] ?? ''));
        $dias = (int)($_POST['duracion_dias'] ?? 0);
        $precio = (float)str_replace(',', '.', (string)($_POST['precio'] ?? 0));
        $color = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['color'] ?? '') ? $_POST['color'] : '#ff5a1f';

        $err = [];
        if ($nombre === '') $err[] = 'El nombre del plan es obligatorio.';
        if ($dias < 1 || $dias > 1095) $err[] = 'La duración debe estar entre 1 y 1095 días.';
        if ($precio < 0 || $precio > 99999) $err[] = 'El precio no es válido.';
        if ($err) { $this->flash('danger', implode(' ', $err)); $this->redirect('planes'); }

        if ($id) {
            Database::exec('UPDATE planes SET nombre=?, descripcion=?, duracion_dias=?, precio=?, color=? WHERE id=?', [$nombre, $desc ?: null, $dias, $precio, $color, $id]);
            $this->flash('success', 'Plan actualizado. Las membresías ya vendidas conservan su precio original.');
        } else {
            Database::exec('INSERT INTO planes (nombre, descripcion, duracion_dias, precio, color) VALUES (?,?,?,?,?)', [$nombre, $desc ?: null, $dias, $precio, $color]);
            $this->flash('success', 'Plan creado correctamente.');
        }
        $this->redirect('planes');
    }

    public function estado($id = 0): void
    {
        $this->requireAuth();
        $this->requirePost('planes');
        Database::exec('UPDATE planes SET estado = 1 - estado WHERE id = ?', [(int)$id]);
        $this->flash('success', 'Estado del plan actualizado.');
        $this->redirect('planes');
    }

    public function eliminar($id = 0): void
    {
        $this->requireAuth();
        $this->requirePost('planes');
        $id = (int)$id;
        if ((int)Database::val('SELECT COUNT(*) FROM membresias WHERE plan_id = ?', [$id]) > 0) {
            $this->flash('warning', 'El plan tiene membresías vendidas y no se puede eliminar. Puedes desactivarlo.');
        } else {
            Database::exec('DELETE FROM planes WHERE id = ?', [$id]);
            $this->flash('success', 'Plan eliminado.');
        }
        $this->redirect('planes');
    }
}
