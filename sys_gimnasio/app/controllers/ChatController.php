<?php
require_once ROOT_PATH . '/app/controllers/IaBase.php';

class ChatController extends IaBase
{
    public function index(): void
    {
        $this->soloAdmin();
        $this->view('chat/index', ['title' => 'Chat IA', 'active' => 'chat']);
    }

    private function hist(): array
    {
        return array_map(fn($h) => ['q' => $h['q'], 'a' => $h['a'], 'tabla' => $h['tabla'] ?? null], $_SESSION['ia_chat'] ?? []);
    }

    public function estado(): void
    {
        $this->soloAdmin(true); Ia::instalar();
        $c = Ia::cfg();
        $this->json(['ok' => true, 'listo' => Ia::listo($c), 'historial' => $this->hist()]);
    }

    public function enviar(): void
    {
        $this->jsonPost(); Ia::instalar(); $this->limite('ia_chat_rl', 40);
        $msg = mb_substr(trim((string)($_POST['mensaje'] ?? '')), 0, 500);
        if ($msg === '') $this->json(['ok' => false, 'error' => 'Escribe un mensaje.'], 422);
        $h = $_SESSION['ia_chat'] ?? [];
        try { $r = Ia::chat($msg, $h); }
        catch (Throwable $e) { $this->json(['ok' => false, 'error' => $e->getMessage()]); }
        $h[] = ['q' => $msg, 'a' => $r['texto'], 'sql' => $r['sql'] ?? null, 'tabla' => $r['tabla'] ?? null];
        $_SESSION['ia_chat'] = array_slice($h, -12);
        $this->json(['ok' => true, 'texto' => $r['texto'], 'tabla' => $r['tabla'] ?? null]);
    }

    public function limpiar(): void
    {
        $this->jsonPost(); $_SESSION['ia_chat'] = [];
        $this->json(['ok' => true]);
    }
}
