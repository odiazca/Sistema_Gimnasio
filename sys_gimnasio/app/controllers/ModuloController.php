<?php
class ModuloController extends Controller
{
    public function index(string $slug = ''): void
    {
        $this->requireAuth();
        $menu = require ROOT_PATH . '/app/menu.php';
        $mod = null;
        foreach ($menu as $items) {
            if (isset($items[$slug]) && isset($items[$slug]['features'])) { $mod = $items[$slug]; break; }
        }
        if (!$mod) { $this->redirect('dashboard'); }
        $this->view('modulos/proximamente', ['title' => $mod[0], 'active' => $slug, 'mod' => $mod]);
    }
}
