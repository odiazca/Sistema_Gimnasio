<?php
require_once ROOT_PATH . '/app/models/Ia.php';

/** Base común del Asistente IA y del Chat IA: solo administrador, JSON con CSRF y límite de uso. */
abstract class IaBase extends Controller
{
    protected function soloAdmin(bool $json = false): void
    {
        $this->requireAuth();
        if (!Permisos::esAdmin()) {
            if ($json) $this->json(['ok' => false, 'error' => 'Solo el administrador puede usar la IA.'], 403);
            http_response_code(403); $this->view('errors/403', ['title' => 'Acceso denegado']); exit;
        }
    }
    protected function jsonPost(): void
    {
        $this->soloAdmin(true);
        $tok = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !Auth::verifyCsrf($tok)) $this->json(['ok' => false, 'error' => 'Sesión expirada. Recarga la página.'], 419);
    }
    /** Límite de peticiones por ventana de 10 min en la sesión. */
    protected function limite(string $k, int $max): void
    {
        $now = time(); $_SESSION[$k] = array_values(array_filter($_SESSION[$k] ?? [], fn($t) => $t > $now - 600));
        if (count($_SESSION[$k]) >= $max) $this->json(['ok' => false, 'error' => 'Hiciste muchas consultas seguidas. Espera unos minutos.'], 429);
        $_SESSION[$k][] = $now;
    }
}
