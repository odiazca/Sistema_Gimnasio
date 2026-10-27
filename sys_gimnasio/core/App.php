<?php
/**
 * Router MVC: /controlador/metodo/param1/param2
 */
class App
{
    public function run(): void
    {
        $url = trim($_GET['url'] ?? '', '/');
        $parts = $url === '' ? [] : explode('/', $url);

        $ctrlName = preg_replace('/[^a-z0-9_]/i', '', $parts[0] ?? '') ?: (Auth::check() ? 'dashboard' : 'login');
        $method   = preg_replace('/[^a-z0-9_]/i', '', $parts[1] ?? '') ?: 'index';
        $params   = array_slice($parts, 2);

        // Alias amigables
        $alias = ['login' => ['auth', 'login'], 'logout' => ['auth', 'logout']];
        if (isset($alias[strtolower($ctrlName)]) && ($parts[1] ?? '') === '') {
            [$ctrlName, $method] = $alias[strtolower($ctrlName)];
        }

        $class = ucfirst(strtolower($ctrlName)) . 'Controller';
        $file  = ROOT_PATH . '/app/controllers/' . $class . '.php';

        if (!is_file($file)) {
            $this->notFound();
            return;
        }
        if (Auth::check()) {
            $mod = Permisos::moduloDe($ctrlName, $method);
            if ($mod !== null && !Permisos::can($mod)) { $this->forbidden(); return; }
        }
        require_once $file;
        $ctrl = new $class();
        if (!method_exists($ctrl, $method) || !(new ReflectionMethod($ctrl, $method))->isPublic()) {
            $this->notFound();
            return;
        }
        call_user_func_array([$ctrl, $method], $params);
    }

    private function forbidden(): void
    {
        http_response_code(403);
        $c = new class extends Controller {
            public function show() { $this->view('errors/403', ['title' => 'Acceso restringido', 'active' => '']); }
        };
        $c->show();
    }

    private function notFound(): void
    {
        http_response_code(404);
        $title = 'Página no encontrada';
        if (Auth::check()) {
            $c = new class extends Controller {
                public function show() { $this->view('errors/404', ['title' => 'No encontrado', 'active' => '']); }
            };
            $c->show();
        } else {
            echo '<h1>404</h1>';
        }
    }
}
