<?php
class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'main'): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = ROOT_PATH . '/app/views/' . $view . '.php';
        if ($layout === 'none') {
            require $viewFile;
            return;
        }
        ob_start();
        require $viewFile;
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/layouts/' . $layout . '.php';
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
        exit;
    }

    protected function requireAuth(): void
    {
        if (!Auth::check()) {
            $this->redirect('login');
        }
    }

    protected function json($data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function flash(string $type, string $msg): void
    {
        $_SESSION['flash'][] = ['t' => $type, 'm' => $msg];
    }

    /** Exige POST con token CSRF válido; si no, vuelve a $back */
    protected function requirePost(string $back): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !Auth::verifyCsrf($_POST['csrf'] ?? null)) {
            $this->flash('danger', 'Solicitud no válida o sesión expirada. Intenta nuevamente.');
            $this->redirect($back);
        }
    }

    protected function in(string $k, string $d = ''): string
    {
        return trim((string)($_POST[$k] ?? $_GET[$k] ?? $d));
    }

    protected function pageNum(): int
    {
        return max(1, (int)($_GET['p'] ?? 1));
    }
}
