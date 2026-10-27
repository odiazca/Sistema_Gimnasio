<?php
require_once ROOT_PATH . '/app/models/Usuario.php';

class AuthController extends Controller
{
    private const MAX_INTENTOS = 5;
    private const BLOQUEO_SEG  = 120;

    public function login(): void
    {
        if (Auth::check()) $this->redirect('dashboard');

        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $bloqueado = ($_SESSION['lock_until'] ?? 0) > time();
            if ($bloqueado) {
                $error = 'Demasiados intentos. Espera ' . ($_SESSION['lock_until'] - time()) . ' segundos.';
            } elseif (!Auth::verifyCsrf($_POST['csrf'] ?? null)) {
                $error = 'Sesión expirada. Recarga la página e intenta nuevamente.';
            } else {
                $login = trim($_POST['usuario'] ?? '');
                $pass  = $_POST['password'] ?? '';
                $u = $login !== '' ? Usuario::findByLogin($login) : null;
                if ($u && password_verify($pass, $u['password'])) {
                    unset($_SESSION['intentos'], $_SESSION['lock_until']);
                    Usuario::touchLogin((int)$u['id']);
                    Auth::login($u);
                    Bitacora::log('login', 'Inicio de sesión');
                    $this->redirect('dashboard');
                }
                $_SESSION['intentos'] = ($_SESSION['intentos'] ?? 0) + 1;
                if ($_SESSION['intentos'] >= self::MAX_INTENTOS) {
                    $_SESSION['lock_until'] = time() + self::BLOQUEO_SEG;
                    $_SESSION['intentos'] = 0;
                }
                $error = 'Usuario o contraseña incorrectos.';
            }
        }
        $this->view('auth/login', ['error' => $error, 'old' => $_POST['usuario'] ?? ''], 'none');
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
}
