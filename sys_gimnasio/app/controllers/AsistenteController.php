<?php
require_once ROOT_PATH . '/app/controllers/IaBase.php';

class AsistenteController extends IaBase
{
    public function index(): void
    {
        $this->soloAdmin();
        Ia::instalar();
        $this->view('asistente/index', ['title' => 'Asistente IA', 'active' => 'asistente']);
    }

    private function cfgPublica(): array
    {
        $c = Ia::cfg();
        return ['proveedor' => $c['proveedor'], 'url' => $c['url'], 'modelo' => $c['modelo'], 'resumen' => $c['resumen'], 'clave_puesta' => $c['clave'] !== '', 'listo' => Ia::listo($c)];
    }

    private function listas(): array
    {
        $u = (int)Auth::user()['id'];
        $f = fn($r) => array_map(fn($x) => ['id' => (int)$x['id'], 'pregunta' => $x['pregunta'], 'titulo' => $x['titulo'], 'favorito' => (int)$x['favorito'], 'veces' => (int)$x['veces']], $r);
        return [
            'favoritas' => $f(Database::all('SELECT id, pregunta, titulo, favorito, veces FROM ia_consultas WHERE usuario_id = ? AND favorito = 1 ORDER BY ultimo_uso DESC', [$u])),
            'recientes' => $f(Database::all('SELECT id, pregunta, titulo, favorito, veces FROM ia_consultas WHERE usuario_id = ? ORDER BY ultimo_uso DESC LIMIT 12', [$u])),
        ];
    }

    public function estado(): void
    {
        $this->soloAdmin(true); Ia::instalar();
        $this->json(['ok' => true, 'config' => $this->cfgPublica(), 'presets' => Ia::PRESETS] + $this->listas());
    }

    public function guardarConfig(): void
    {
        $this->jsonPost(); Ia::instalar();
        $p = (string)($_POST['proveedor'] ?? 'gemini'); if (!isset(Ia::PRESETS[$p])) $p = 'gemini';
        $url = trim((string)($_POST['url'] ?? '')); $modelo = trim((string)($_POST['modelo'] ?? ''));
        if ($url === '' || !preg_match('#^https?://#i', $url)) $this->json(['ok' => false, 'error' => 'La URL debe empezar por http:// o https://'], 422);
        if ($modelo === '') $this->json(['ok' => false, 'error' => 'Indica el modelo.'], 422);
        Ia::set('ia_proveedor', $p); Ia::set('ia_url', mb_substr($url, 0, 250)); Ia::set('ia_modelo', mb_substr($modelo, 0, 120));
        Ia::set('ia_resumen', !empty($_POST['resumen']) && $_POST['resumen'] !== '0' ? '1' : '0');
        $k = trim((string)($_POST['clave'] ?? ''));
        if ($k === '__borrar__') Ia::set('ia_clave', '');
        elseif ($k !== '') Ia::set('ia_clave', Ia::cifrar($k));
        Bitacora::log('ia_config', 'Configuró el proveedor de IA (' . $p . ')');
        $this->json(['ok' => true, 'config' => $this->cfgPublica()]);
    }

    public function probar(): void
    {
        $this->jsonPost();
        try {
            $t = Ia::llamar([['role' => 'user', 'content' => 'Responde solo con la palabra OK.']], 20);
            $this->json(['ok' => true, 'mensaje' => 'Conexión correcta. La IA respondió: ' . mb_substr(trim($t), 0, 40)]);
        } catch (Throwable $e) { $this->json(['ok' => false, 'error' => $e->getMessage()]); }
    }

    public function preguntar(): void
    {
        $this->jsonPost(); Ia::instalar(); $this->limite('ia_rl', 30);
        $q = trim((string)($_POST['pregunta'] ?? ''));
        if (mb_strlen($q) < 3) $this->json(['ok' => false, 'error' => 'Escribe una pregunta (mínimo 3 caracteres).'], 422);
        $q = mb_substr($q, 0, 500);
        $previo = null;
        if (!empty($_POST['previo'])) { $pj = json_decode((string)$_POST['previo'], true); if (is_array($pj)) $previo = ['pregunta' => (string)($pj['pregunta'] ?? ''), 'sql' => (string)($pj['sql'] ?? '')]; }
        try { $r = Ia::preguntar($q, $previo); }
        catch (Throwable $e) { $this->json(['ok' => false, 'error' => $e->getMessage()]); }
        if (isset($r['mensaje'])) $this->json(['ok' => true, 'mensaje' => $r['mensaje']]);
        $u = (int)Auth::user()['id'];
        $ex = Database::row('SELECT id FROM ia_consultas WHERE usuario_id = ? AND sql_texto = ?', [$u, $r['sql']]);
        if ($ex) { $id = (int)$ex['id']; Database::exec('UPDATE ia_consultas SET veces = veces + 1, ultimo_uso = NOW(), pregunta = ?, titulo = ? WHERE id = ?', [$q, $r['titulo'], $id]); }
        else {
            Database::exec('INSERT INTO ia_consultas (usuario_id, pregunta, titulo, sql_texto, grafico) VALUES (?,?,?,?,?)', [$u, $q, $r['titulo'], $r['sql'], json_encode($r['grafico'])]);
            $id = (int)Database::conn()->lastInsertId();
            Database::exec('DELETE FROM ia_consultas WHERE usuario_id = ? AND favorito = 0 AND id NOT IN (SELECT id FROM (SELECT id FROM ia_consultas WHERE usuario_id = ? AND favorito = 0 ORDER BY ultimo_uso DESC LIMIT 60) t)', [$u, $u]);
        }
        $fav = (int)Database::val('SELECT favorito FROM ia_consultas WHERE id = ?', [$id]);
        $this->json(['ok' => true, 'id' => $id, 'favorito' => $fav, 'pregunta' => $q] + $r + $this->listas());
    }

    /** Vuelve a ejecutar una consulta guardada sin gastar IA */
    public function ejecutar(): void
    {
        $this->jsonPost(); Ia::instalar(); $this->limite('ia_rl', 30);
        $c = Database::row('SELECT * FROM ia_consultas WHERE id = ? AND usuario_id = ?', [(int)($_POST['id'] ?? 0), (int)Auth::user()['id']]);
        if (!$c) $this->json(['ok' => false, 'error' => 'Consulta no encontrada.'], 404);
        try { $r = Ia::ejecutar($c['sql_texto']); } catch (Throwable $e) { $this->json(['ok' => false, 'error' => $e->getMessage()]); }
        Database::exec('UPDATE ia_consultas SET veces = veces + 1, ultimo_uso = NOW() WHERE id = ?', [$c['id']]);
        $g = json_decode((string)$c['grafico'], true) ?: ['tipo' => 'none'];
        $this->json(['ok' => true, 'id' => (int)$c['id'], 'favorito' => (int)$c['favorito'], 'pregunta' => $c['pregunta'], 'titulo' => $c['titulo'] ?: $c['pregunta'], 'grafico' => $g] + $r + $this->listas());
    }

    public function resumir(): void
    {
        $this->jsonPost(); $this->limite('ia_rl', 30);
        $cols = json_decode((string)($_POST['columnas'] ?? '[]'), true); $filas = json_decode((string)($_POST['filas'] ?? '[]'), true);
        if (!is_array($cols) || !is_array($filas)) $this->json(['ok' => false, 'error' => 'Datos inválidos.'], 422);
        try { $this->json(['ok' => true, 'resumen' => Ia::resumir(mb_substr((string)($_POST['pregunta'] ?? ''), 0, 500), array_slice($cols, 0, 30), array_slice($filas, 0, 25))]); }
        catch (Throwable $e) { $this->json(['ok' => false, 'error' => $e->getMessage()]); }
    }

    public function favorito(): void
    {
        $this->jsonPost(); Ia::instalar();
        Database::exec('UPDATE ia_consultas SET favorito = 1 - favorito WHERE id = ? AND usuario_id = ?', [(int)($_POST['id'] ?? 0), (int)Auth::user()['id']]);
        $this->json(['ok' => true] + $this->listas());
    }

    public function borrar(): void
    {
        $this->jsonPost(); Ia::instalar();
        Database::exec('DELETE FROM ia_consultas WHERE id = ? AND usuario_id = ?', [(int)($_POST['id'] ?? 0), (int)Auth::user()['id']]);
        $this->json(['ok' => true] + $this->listas());
    }

    /** Descarga CSV de una consulta guardada (POST con CSRF desde un formulario) */
    public function csv(): void
    {
        $this->jsonPost(); Ia::instalar();
        $c = Database::row('SELECT * FROM ia_consultas WHERE id = ? AND usuario_id = ?', [(int)($_POST['id'] ?? 0), (int)Auth::user()['id']]);
        if (!$c) $this->json(['ok' => false, 'error' => 'Consulta no encontrada.'], 404);
        try { $r = Ia::ejecutar($c['sql_texto']); } catch (Throwable $e) { $this->json(['ok' => false, 'error' => $e->getMessage()]); }
        $nombre = preg_replace('/[^a-z0-9]+/', '_', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string)($c['titulo'] ?: 'consulta')))) ?: 'consulta';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . trim($nombre, '_') . '_' . date('Ymd') . '.csv"');
        $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
        $safe = fn($v) => is_string($v) && $v !== '' && strpbrk($v[0], "=+-@\t\r") !== false ? "'" . $v : $v;
        fputcsv($o, array_map($safe, $r['columnas']), ';');
        foreach ($r['filas'] as $f) fputcsv($o, array_map($safe, $f), ';');
        fclose($o); exit;
    }

    public function esquema(): void
    {
        $this->soloAdmin(true); Ia::instalar();
        $this->json(['ok' => true, 'esquema' => Ia::esquemaTexto()]);
    }
}
