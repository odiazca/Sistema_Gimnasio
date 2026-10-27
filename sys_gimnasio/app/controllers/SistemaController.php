<?php
require_once ROOT_PATH . '/app/models/Backup.php';

class SistemaController extends Controller
{
    private function admin(): void { $this->requireAuth(); }

    private function claveOk(): bool
    {
        $h = Database::val('SELECT password FROM usuarios WHERE id = ?', [(int)Auth::user()['id']]);
        return is_string($h) && $h !== '' && password_verify((string)($_POST['password'] ?? ''), $h);
    }

    private function frase(string $esperada): bool { return strtoupper(trim((string)($_POST['frase'] ?? ''))) === $esperada; }

    private function set(string $k, string $v): void
    {
        Database::exec('INSERT INTO configuracion (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)', [$k, $v]);
    }

    public function index(): void
    {
        $this->admin();
        $lista = Backup::listar();
        $up = min($this->iniBytes('upload_max_filesize'), $this->iniBytes('post_max_size'));
        $this->view('sistema/index', [
            'title' => 'Respaldo y sistema', 'active' => 'sistema', 'lista' => $lista, 'info' => Backup::info(),
            'zip' => class_exists('ZipArchive'), 'maxSubida' => $up, 'dirOk' => is_writable(Backup::dir()),
            'auto' => cfg('backup_auto', 'off'), 'ret' => (int)cfg('backup_retencion', '10'), 'ultimoAuto' => cfg('backup_ultimo_auto', ''),
            'ultimo' => $lista[0] ?? null, 'php' => PHP_VERSION,
            'uploadsBytes' => $this->dirBytes(ROOT_PATH . '/public/uploads'),
        ]);
    }

    private function iniBytes(string $k): int
    {
        $v = trim((string)ini_get($k)); $n = (int)$v; $u = strtolower(substr($v, -1));
        return $u === 'g' ? $n * 1073741824 : ($u === 'm' ? $n * 1048576 : ($u === 'k' ? $n * 1024 : $n));
    }

    private function dirBytes(string $d): int
    {
        $t = 0; if (!is_dir($d)) return 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS)) as $f) if ($f->isFile()) $t += $f->getSize();
        return $t;
    }

    public function crear(): void
    {
        $this->admin(); $this->requirePost('sistema');
        try {
            $n = Backup::crear('manual', !empty($_POST['imagenes']));
            Bitacora::log('respaldo_creado', $n);
            $this->flash('success', 'Copia de seguridad creada: ' . $n);
        } catch (Throwable $e) { $this->flash('danger', 'No se pudo crear la copia: ' . $e->getMessage()); }
        $this->redirect('sistema');
    }

    public function descargar($n = ''): void
    {
        $this->admin();
        $p = Backup::ruta((string)$n);
        if (!$p) { $this->flash('danger', 'El archivo no existe.'); $this->redirect('sistema'); }
        Bitacora::log('respaldo_descargado', basename($p));
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($p) . '"');
        header('Content-Length: ' . filesize($p));
        header('X-Content-Type-Options: nosniff');
        readfile($p); exit;
    }

    public function eliminar(): void
    {
        $this->admin(); $this->requirePost('sistema');
        $n = (string)($_POST['archivo'] ?? '');
        if (Backup::eliminar($n)) { Bitacora::log('respaldo_eliminado', $n); $this->flash('success', 'Copia eliminada.'); }
        else $this->flash('danger', 'No se pudo eliminar la copia.');
        $this->redirect('sistema');
    }

    public function auto(): void
    {
        $this->admin(); $this->requirePost('sistema');
        $m = in_array($_POST['backup_auto'] ?? '', ['off', 'diario', 'semanal'], true) ? $_POST['backup_auto'] : 'off';
        $r = max(1, min(60, (int)($_POST['backup_retencion'] ?? 10)));
        $this->set('backup_auto', $m); $this->set('backup_retencion', (string)$r);
        Bitacora::log('respaldo_auto', $m . ', conservar ' . $r);
        $this->flash('success', $m === 'off' ? 'Respaldo automático desactivado.' : 'Respaldo automático ' . $m . ' activado (se conservan ' . $r . ').');
        $this->redirect('sistema');
    }

    public function restaurar(): void
    {
        $this->admin(); $this->requirePost('sistema');
        if (!$this->claveOk()) { $this->flash('danger', 'Tu contraseña no es correcta. No se restauró nada.'); $this->redirect('sistema'); }
        if (!$this->frase('RESTAURAR')) { $this->flash('danger', 'Debes escribir RESTAURAR para confirmar.'); $this->redirect('sistema'); }
        $ruta = null; $ext = ''; $tmp = null;
        try {
            if (!empty($_FILES['archivo']['name'])) {
                $f = $_FILES['archivo'];
                if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE ? 'El archivo supera el límite de subida del servidor (' . Backup::bytes(min($this->iniBytes('upload_max_filesize'), $this->iniBytes('post_max_size'))) . ').' : 'No se pudo subir el archivo.');
                $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['zip', 'sql'], true)) throw new RuntimeException('Sube un respaldo .zip o .sql.');
                $tmp = Backup::dir() . '/subida_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (!move_uploaded_file($f['tmp_name'], $tmp)) throw new RuntimeException('No se pudo guardar el archivo subido.');
                $ruta = $tmp;
            } else {
                $ruta = Backup::ruta((string)($_POST['archivo_guardado'] ?? ''));
                if (!$ruta) throw new RuntimeException('Elige una copia guardada o sube un archivo.');
                $ext = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
            }
            $uid = (int)Auth::user()['id']; $nom = basename((string)$ruta);
            $seg = Backup::restaurar($ruta, $ext);
            Permisos::ensure();
            Bitacora::log('respaldo_restaurado', mb_substr($nom, 0, 120));
            if (!Database::val('SELECT COUNT(*) FROM usuarios WHERE id = ? AND estado = 1', [$uid])) {
                Auth::logout(); Auth::start();
                $this->flash('success', 'Respaldo restaurado. Inicia sesión nuevamente.'); $this->redirect('login');
            }
            unset($_SESSION['user']['rol_id']);
            $this->flash('success', 'Respaldo restaurado correctamente. Antes se guardó una copia de seguridad del estado anterior (' . $seg . ').');
        } catch (Throwable $e) { $this->flash('danger', 'No se pudo restaurar: ' . $e->getMessage()); }
        finally { if ($tmp && is_file($tmp)) @unlink($tmp); }
        $this->redirect('sistema');
    }

    public function resetear(): void
    {
        $this->admin(); $this->requirePost('sistema');
        if (!$this->claveOk()) { $this->flash('danger', 'Tu contraseña no es correcta. No se borró nada.'); $this->redirect('sistema'); }
        if (!$this->frase('RESETEAR')) { $this->flash('danger', 'Debes escribir RESETEAR para confirmar.'); $this->redirect('sistema'); }
        try {
            $seg = Backup::resetear(['usuarios' => !empty($_POST['k_usuarios']), 'config' => !empty($_POST['k_config']), 'catalogos' => !empty($_POST['k_catalogos'])], (int)Auth::user()['id']);
            Permisos::ensure();
            Bitacora::log('sistema_reseteado', 'Reinicio para empresa nueva');
            $this->flash('success', 'Sistema reiniciado. Se guardó una copia previa (' . $seg . ') por si necesitas recuperar los datos.');
        } catch (Throwable $e) { $this->flash('danger', 'No se pudo resetear: ' . $e->getMessage()); }
        $this->redirect(!empty($_POST['k_config']) ? 'sistema' : 'configuracion');
    }
}
