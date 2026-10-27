<?php
class UsuariosController extends Controller
{
    private function admins(int $exceptId = 0): int
    {
        return (int)Database::val('SELECT COUNT(*) FROM usuarios WHERE rol_id = 1 AND estado = 1 AND id <> ?', [$exceptId]);
    }

    public function index(): void
    {
        $this->requireAuth(); Permisos::ensure();
        $vista = in_array($this->in('vista'), ['permisos', 'bitacora'], true) ? $this->in('vista') : 'usuarios';
        $d = ['title' => 'Usuarios y roles', 'active' => 'usuarios', 'vista' => $vista, 'roles' => Database::all('SELECT * FROM roles ORDER BY id')];
        if ($vista === 'usuarios') {
            $d['rows'] = Database::all('SELECT u.*, r.nombre rol FROM usuarios u JOIN roles r ON r.id = u.rol_id ORDER BY u.estado DESC, u.rol_id, u.nombre');
        } elseif ($vista === 'permisos') {
            $menu = require ROOT_PATH . '/app/menu.php'; $mods = [];
            foreach ($menu as $g => $items) foreach ($items as $slug => $it) $mods[$g][$slug] = $it[0];
            $d['mods'] = $mods; $d['mapa'] = Permisos::mapa();
        } else {
            $f = ['q' => $this->in('q'), 'accion' => $this->in('accion')];
            $w = '1=1'; $p = [];
            if ($f['q'] !== '') { $w .= ' AND (usuario LIKE ? OR detalle LIKE ?)'; $p[] = '%' . $f['q'] . '%'; $p[] = '%' . $f['q'] . '%'; }
            if ($f['accion'] !== '') { $w .= ' AND accion = ?'; $p[] = $f['accion']; }
            $pg = paginate((int)Database::val("SELECT COUNT(*) FROM bitacora WHERE $w", $p), 20, $this->pageNum());
            $d += ['f' => $f, 'pg' => $pg, 'rows' => Database::all("SELECT * FROM bitacora WHERE $w ORDER BY id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $p),
                   'acciones' => array_column(Database::all('SELECT DISTINCT accion FROM bitacora ORDER BY accion'), 'accion')];
        }
        $this->view('usuarios/index', $d);
    }

    public function guardar(): void
    {
        $this->requireAuth();
        $this->requirePost('usuarios');
        $id = (int)($_POST['id'] ?? 0); $nom = trim((string)($_POST['nombre'] ?? '')); $usr = strtolower(trim((string)($_POST['usuario'] ?? '')));
        $mail = trim((string)($_POST['email'] ?? '')); $rol = (int)($_POST['rol_id'] ?? 0); $pass = (string)($_POST['password'] ?? '');
        $err = [];
        if ($nom === '') $err[] = 'El nombre es obligatorio.';
        if (!preg_match('/^[a-z0-9._-]{3,40}$/', $usr)) $err[] = 'El usuario debe tener 3-40 caracteres (letras, números, punto, guion).';
        if ($mail !== '' && !filter_var($mail, FILTER_VALIDATE_EMAIL)) $err[] = 'El correo no es válido.';
        if (!Database::val('SELECT COUNT(*) FROM roles WHERE id = ?', [$rol])) $err[] = 'Selecciona un rol válido.';
        if (!$err && Database::val('SELECT COUNT(*) FROM usuarios WHERE usuario = ? AND id <> ?', [$usr, $id])) $err[] = 'Ese nombre de usuario ya existe.';
        if (!$err && $mail !== '' && Database::val('SELECT COUNT(*) FROM usuarios WHERE email = ? AND id <> ?', [$mail, $id])) $err[] = 'Ese correo ya está en uso.';
        if (!$id && (strlen($pass) < 8 || $pass === $usr)) $err[] = 'La contraseña debe tener al menos 8 caracteres.';
        if ($id && $rol !== 1 && Database::val('SELECT rol_id FROM usuarios WHERE id = ?', [$id]) == 1 && $this->admins($id) < 1) $err[] = 'Debe quedar al menos un administrador activo.';
        if ($err) { $this->flash('danger', implode(' ', $err)); $this->redirect('usuarios'); }
        if ($id) {
            Database::exec('UPDATE usuarios SET nombre=?, usuario=?, email=?, rol_id=? WHERE id=?', [$nom, $usr, $mail ?: null, $rol, $id]);
            if ($id === (int)Auth::user()['id']) { $_SESSION['user']['nombre'] = $nom; $_SESSION['user']['usuario'] = $usr; $_SESSION['user']['rol_id'] = $rol; $_SESSION['user']['rol'] = Database::val('SELECT nombre FROM roles WHERE id = ?', [$rol]); }
            Bitacora::log('usuario_editado', "Usuario $usr (rol $rol)");
            $this->flash('success', 'Usuario actualizado.');
        } else {
            Database::exec('INSERT INTO usuarios (rol_id,nombre,usuario,email,password) VALUES (?,?,?,?,?)', [$rol, $nom, $usr, $mail ?: null, password_hash($pass, PASSWORD_DEFAULT)]);
            Bitacora::log('usuario_creado', "Usuario $usr (rol $rol)");
            $this->flash('success', 'Usuario creado. Comparte la contraseña inicial de forma segura.');
        }
        $this->redirect('usuarios');
    }

    public function password($id = 0): void
    {
        $this->requireAuth();
        $this->requirePost('usuarios');
        $u = Database::row('SELECT * FROM usuarios WHERE id = ?', [(int)$id]);
        $p = (string)($_POST['password'] ?? '');
        if (!$u) $this->redirect('usuarios');
        if (strlen($p) < 8 || $p === $u['usuario']) { $this->flash('danger', 'La nueva contraseña debe tener al menos 8 caracteres.'); $this->redirect('usuarios'); }
        Database::exec('UPDATE usuarios SET password = ? WHERE id = ?', [password_hash($p, PASSWORD_DEFAULT), $u['id']]);
        Bitacora::log('password_restablecida', 'Usuario ' . $u['usuario']);
        $this->flash('success', 'Contraseña de ' . $u['usuario'] . ' restablecida.');
        $this->redirect('usuarios');
    }

    public function estado($id = 0): void
    {
        $this->requireAuth();
        $this->requirePost('usuarios');
        $u = Database::row('SELECT * FROM usuarios WHERE id = ?', [(int)$id]);
        if (!$u) $this->redirect('usuarios');
        if ((int)$u['id'] === (int)Auth::user()['id']) { $this->flash('warning', 'No puedes desactivar tu propia cuenta.'); $this->redirect('usuarios'); }
        if ($u['estado'] && (int)$u['rol_id'] === 1 && $this->admins((int)$u['id']) < 1) { $this->flash('warning', 'Debe quedar al menos un administrador activo.'); $this->redirect('usuarios'); }
        Database::exec('UPDATE usuarios SET estado = 1 - estado WHERE id = ?', [$u['id']]);
        Bitacora::log($u['estado'] ? 'usuario_desactivado' : 'usuario_activado', 'Usuario ' . $u['usuario']);
        $this->flash('success', 'Estado del usuario actualizado.');
        $this->redirect('usuarios');
    }

    public function guardarPermisos(): void
    {
        $this->requireAuth();
        $this->requirePost('usuarios?vista=permisos');
        Permisos::guardar(is_array($_POST['perm'] ?? null) ? $_POST['perm'] : []);
        Bitacora::log('permisos_actualizados', 'Matriz de permisos por rol');
        $this->flash('success', 'Permisos guardados. Se aplican en la siguiente página que abra cada usuario.');
        $this->redirect('usuarios?vista=permisos');
    }
}
