<?php
class PerfilController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $u = Database::row('SELECT u.*, r.nombre rol FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE u.id = ?', [(int)Auth::user()['id']]);
        $this->view('perfil/index', ['title' => 'Mi perfil', 'active' => '', 'u' => $u]);
    }

    public function cambiar(): void
    {
        $this->requireAuth();
        $this->requirePost('perfil');
        $u = Database::row('SELECT * FROM usuarios WHERE id = ?', [(int)Auth::user()['id']]);
        $act = (string)($_POST['actual'] ?? ''); $new = (string)($_POST['nueva'] ?? ''); $rep = (string)($_POST['repetir'] ?? '');
        $err = null;
        if (!password_verify($act, $u['password'])) $err = 'La contraseña actual no es correcta.';
        elseif (strlen($new) < 8) $err = 'La nueva contraseña debe tener al menos 8 caracteres.';
        elseif ($new === $u['usuario'] || $new === $act) $err = 'La nueva contraseña debe ser distinta del usuario y de la actual.';
        elseif ($new !== $rep) $err = 'La confirmación no coincide.';
        if ($err) { $this->flash('danger', $err); $this->redirect('perfil'); }
        Database::exec('UPDATE usuarios SET password = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]);
        Bitacora::log('password_cambiada', 'Cambio de contraseña propio');
        $this->flash('success', 'Contraseña actualizada.');
        $this->redirect('perfil');
    }
}
