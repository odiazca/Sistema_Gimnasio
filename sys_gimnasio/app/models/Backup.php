<?php
/** Copias de seguridad, restauración y reseteo del sistema. */
class Backup
{
    public const MAGIC = '-- FITCORE-BACKUP v1';
    /** Tablas de catálogo que se pueden conservar al resetear */
    public const CATALOGOS = ['planes', 'productos', 'clases', 'horarios_clase', 'entrenadores', 'ejercicios', 'rutinas', 'rutina_ejercicios'];
    /** Nunca se vacían */
    public const SIEMPRE = ['roles', 'rol_permisos'];

    public static function dir(): string
    {
        $d = ROOT_PATH . '/storage/backups';
        if (!is_dir($d)) @mkdir($d, 0775, true);
        if (is_dir($d)) {
            if (!is_file($d . '/.htaccess')) @file_put_contents($d . '/.htaccess', "Require all denied\nDeny from all\n");
            if (!is_file($d . '/index.html')) @file_put_contents($d . '/index.html', '');
            $p = ROOT_PATH . '/storage';
            if (!is_file($p . '/.htaccess')) @file_put_contents($p . '/.htaccess', "Require all denied\nDeny from all\n");
        }
        return $d;
    }

    public static function tablas(): array
    {
        return Database::conn()->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function info(): array
    {
        $r = Database::row("SELECT COUNT(*) t, COALESCE(SUM(data_length + index_length),0) b FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'");
        $filas = (int)Database::val("SELECT COALESCE(SUM(table_rows),0) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'");
        return ['tablas' => (int)$r['t'], 'bytes' => (int)$r['b'], 'filas' => $filas, 'version' => (string)Database::val('SELECT VERSION()')];
    }

    /* ---------- Crear ---------- */

    /** Genera un respaldo. $imagenes: incluye public/uploads dentro de un .zip. Devuelve el nombre del archivo. */
    public static function crear(string $tipo = 'manual', bool $imagenes = true): string
    {
        @set_time_limit(0);
        $dir = self::dir();
        if (!is_dir($dir) || !is_writable($dir)) throw new RuntimeException('No se puede escribir en la carpeta de respaldos.');
        $tipo = in_array($tipo, ['manual', 'auto', 'seguridad'], true) ? $tipo : 'manual';
        $base = 'fitcore_' . date('Ymd_His') . '_' . $tipo;
        $tmp = $dir . '/' . $base . '.tmp';
        $fh = fopen($tmp, 'wb');
        if (!$fh) throw new RuntimeException('No se pudo crear el archivo de respaldo.');
        try { self::volcar($fh); } catch (Throwable $e) { fclose($fh); @unlink($tmp); throw $e; }
        fclose($fh);

        $usaZip = class_exists('ZipArchive');
        if ($usaZip) {
            $final = $base . '.zip';
            $z = new ZipArchive();
            if ($z->open($dir . '/' . $final, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) { @unlink($tmp); throw new RuntimeException('No se pudo crear el .zip.'); }
            $z->addFile($tmp, 'database.sql');
            if ($imagenes) {
                $up = ROOT_PATH . '/public/uploads';
                if (is_dir($up)) {
                    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($up, FilesystemIterator::SKIP_DOTS));
                    foreach ($it as $f) if ($f->isFile() && !preg_match('/\.(php\d?|phtml|phar)$/i', $f->getFilename())) $z->addFile($f->getPathname(), 'uploads/' . str_replace('\\', '/', substr($f->getPathname(), strlen($up) + 1)));
                }
            }
            $z->setArchiveComment('FitCore Gym · ' . date('Y-m-d H:i:s'));
            $z->close();
            @unlink($tmp);
        } else {
            $final = $base . '.sql';
            rename($tmp, $dir . '/' . $final);
        }
        self::depurar();
        return $final;
    }

    private static function volcar($fh): void
    {
        $pdo = Database::conn();
        $tablas = self::tablas();
        fwrite($fh, self::MAGIC . "\n-- Base de datos: " . DB_NAME . "\n-- Fecha: " . date('Y-m-d H:i:s') . "\n-- Servidor: " . Database::val('SELECT VERSION()') . "\n-- Tablas: " . count($tablas) . "\n\n");
        fwrite($fh, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\nSET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        try {
            foreach ($tablas as $t) {
                $q = '`' . str_replace('`', '``', $t) . '`';
                $c = $pdo->query('SHOW CREATE TABLE ' . $q)->fetch(PDO::FETCH_NUM);
                fwrite($fh, "-- Tabla $t\nDROP TABLE IF EXISTS $q;\n" . $c[1] . ";\n");
                $st = $pdo->query('SELECT * FROM ' . $q);
                $cols = null; $buf = []; $n = 0;
                $flush = function () use (&$buf, &$cols, $fh, $q) {
                    if ($buf) { fwrite($fh, 'INSERT INTO ' . $q . ' (' . $cols . ') VALUES ' . implode(",\n", $buf) . ";\n"); $buf = []; }
                };
                while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
                    if ($cols === null) $cols = implode(',', array_map(fn($k) => '`' . str_replace('`', '``', $k) . '`', array_keys($row)));
                    $buf[] = '(' . implode(',', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), $row)) . ')';
                    if (++$n % 200 === 0) $flush();
                }
                $st->closeCursor(); $flush();
                fwrite($fh, "\n");
            }
        } finally { $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true); }
        fwrite($fh, "SET FOREIGN_KEY_CHECKS = 1;\n-- FIN\n");
    }

    /** Conserva solo los N últimos respaldos automáticos y 5 de seguridad */
    public static function depurar(): void
    {
        $ret = max(1, (int)cfg('backup_retencion', '10'));
        foreach (['auto' => $ret, 'seguridad' => 5] as $tipo => $max) {
            $l = array_values(array_filter(self::listar(), fn($b) => $b['tipo'] === $tipo));
            foreach (array_slice($l, $max) as $b) @unlink(self::dir() . '/' . $b['archivo']);
        }
    }

    /* ---------- Listar / archivos ---------- */

    public static function nombreValido(string $n): bool { return (bool)preg_match('/^fitcore_\d{8}_\d{6}_(manual|auto|seguridad)\.(zip|sql)$/', $n); }

    public static function ruta(string $n): ?string
    {
        if (!self::nombreValido($n)) return null;
        $p = self::dir() . '/' . $n;
        return is_file($p) ? $p : null;
    }

    public static function listar(): array
    {
        $out = [];
        foreach (glob(self::dir() . '/fitcore_*') ?: [] as $p) {
            $n = basename($p);
            if (!self::nombreValido($n)) continue;
            preg_match('/_(\d{8})_(\d{6})_(\w+)\.(zip|sql)$/', $n, $m);
            $out[] = ['archivo' => $n, 'bytes' => filesize($p), 'fecha' => DateTime::createFromFormat('Ymd His', $m[1] . ' ' . $m[2])->format('Y-m-d H:i:s'), 'tipo' => $m[3], 'zip' => $m[4] === 'zip'];
        }
        usort($out, fn($a, $b) => strcmp($b['fecha'], $a['fecha']) ?: strcmp($b['archivo'], $a['archivo']));
        return $out;
    }

    public static function eliminar(string $n): bool { $p = self::ruta($n); return $p ? @unlink($p) : false; }

    public static function bytes(int $b): string
    {
        if ($b >= 1048576) return number_format($b / 1048576, 1) . ' MB';
        return number_format(max(1, (int)ceil($b / 1024)), 0) . ' KB';
    }

    /* ---------- Restaurar ---------- */

    /** Extrae el .sql de un archivo (.sql o .zip) a un temporal. Devuelve [rutaSql, rutaZipOnull]. */
    private static function prepararSql(string $archivo, string $ext): array
    {
        if ($ext === 'sql') return [$archivo, null];
        if ($ext !== 'zip' || !class_exists('ZipArchive')) throw new RuntimeException('Formato no compatible. Usa un respaldo .zip o .sql de FitCore.');
        $z = new ZipArchive();
        if ($z->open($archivo) !== true) throw new RuntimeException('El archivo .zip está dañado o no es válido.');
        $idx = $z->locateName('database.sql');
        if ($idx === false) { $z->close(); throw new RuntimeException('El .zip no contiene database.sql: no es un respaldo de FitCore.'); }
        $tmp = self::dir() . '/restore_' . bin2hex(random_bytes(4)) . '.tmp';
        $in = $z->getStream('database.sql'); $out = fopen($tmp, 'wb');
        if (!$in || !$out) { $z->close(); throw new RuntimeException('No se pudo leer el respaldo.'); }
        stream_copy_to_stream($in, $out); fclose($in); fclose($out);
        return [$tmp, $z];
    }

    /** Recorre las sentencias del .sql (respeta comillas y comentarios). $cb recibe cada sentencia. */
    private static function sentencias(string $sql, callable $cb): void
    {
        $fh = fopen($sql, 'rb');
        if (!$fh) throw new RuntimeException('No se pudo abrir el respaldo.');
        $buf = ''; $q = null; $esc = false;
        while (($line = fgets($fh)) !== false) {
            if ($q === null && trim($buf) === '') {
                $t = ltrim($line);
                if ($t === '' || strncmp($t, '--', 2) === 0 || $t[0] === '#') continue;
            }
            $len = strlen($line);
            for ($i = 0; $i < $len; $i++) {
                $c = $line[$i];
                if ($q !== null) {
                    if ($esc) { $esc = false; }
                    elseif ($c === '\\' && $q !== '`') { $esc = true; }
                    elseif ($c === $q) { $q = null; }
                } elseif ($c === "'" || $c === '"' || $c === '`') { $q = $c; }
                elseif ($c === ';') {
                    $buf .= substr($line, 0, $i); $line = substr($line, $i + 1); $len = strlen($line); $i = -1;
                    $s = trim($buf); $buf = '';
                    if ($s !== '') $cb($s);
                }
            }
            $buf .= $line;
        }
        fclose($fh);
        if ($q !== null) throw new RuntimeException('El respaldo está incompleto (comillas sin cerrar).');
        if (trim($buf) !== '') throw new RuntimeException('El respaldo está incompleto (termina a mitad de una sentencia).');
    }

    private static function validarSentencia(string $s, array &$tablas, int &$inserts): void
    {
        if (preg_match('/^SET\s+(NAMES\s+utf8mb4|FOREIGN_KEY_CHECKS\s*=\s*[01]|SQL_MODE\s*=\s*\'[A-Z_,]*\')$/i', $s)) return;
        if (preg_match('/^DROP TABLE IF EXISTS `([A-Za-z0-9_]+)`$/', $s)) return;
        if (preg_match('/^CREATE TABLE `([A-Za-z0-9_]+)` \(/', $s, $m)) {
            if (preg_match('/\b(TRIGGER|GRANT|DELIMITER)\b/i', preg_replace("/'(?:[^'\\\\]|\\\\.)*'/s", "''", $s))) throw new RuntimeException('El respaldo contiene instrucciones no permitidas.');
            $tablas[] = $m[1]; return;
        }
        if (preg_match('/^INSERT INTO `[A-Za-z0-9_]+` \([^)]*\) VALUES \(/', $s)) { $inserts++; return; }
        throw new RuntimeException('El archivo contiene instrucciones no permitidas y no parece un respaldo de FitCore.');
    }

    /** Valida un respaldo sin ejecutarlo. Devuelve ['tablas'=>n,'filas'=>n]. */
    public static function validar(string $ruta, string $ext): array
    {
        [$sql, $z] = self::prepararSql($ruta, $ext);
        try {
            $fh = fopen($sql, 'rb'); $h = trim((string)fgets($fh)); fclose($fh);
            if ($h !== self::MAGIC) throw new RuntimeException('Este archivo no es un respaldo generado por FitCore Gym.');
            $tablas = []; $ins = 0;
            self::sentencias($sql, function ($s) use (&$tablas, &$ins) { self::validarSentencia($s, $tablas, $ins); });
            if (!in_array('usuarios', $tablas, true) || !in_array('configuracion', $tablas, true)) throw new RuntimeException('El respaldo no contiene las tablas esenciales (usuarios, configuración).');
            return ['tablas' => count($tablas), 'inserts' => $ins];
        } finally { if ($z) { $z->close(); @unlink($sql); } }
    }

    /** Restaura. Crea antes una copia de seguridad; si algo falla, vuelve al estado anterior. Devuelve el nombre de la copia de seguridad previa. */
    public static function restaurar(string $ruta, string $ext): string
    {
        @set_time_limit(0);
        self::validar($ruta, $ext);
        $seg = self::crear('seguridad', true);
        try {
            self::aplicar($ruta, $ext, true);
        } catch (Throwable $e) {
            try { $p = self::ruta($seg); self::aplicar($p, pathinfo($p, PATHINFO_EXTENSION), false); }
            catch (Throwable $e2) { throw new RuntimeException($e->getMessage() . ' — Además falló la reversión automática; usa la copia «' . $seg . '».'); }
            throw new RuntimeException($e->getMessage() . ' — Se revirtió al estado anterior.');
        }
        return $seg;
    }

    private static function aplicar(string $ruta, string $ext, bool $verificarAdmin): void
    {
        [$sql, $z] = self::prepararSql($ruta, $ext);
        $pdo = Database::conn();
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            self::sentencias($sql, function ($s) use ($pdo) { $pdo->exec($s); });
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            if ($verificarAdmin && !(int)Database::val('SELECT COUNT(*) FROM usuarios WHERE rol_id = 1 AND estado = 1')) throw new RuntimeException('El respaldo no tiene ningún administrador activo.');
            if ($z) self::restaurarImagenes($z);
        } catch (Throwable $e) {
            try { $pdo->exec('SET FOREIGN_KEY_CHECKS = 1'); } catch (Throwable $x) {}
            throw $e;
        } finally { if ($z) { $z->close(); } if ($sql !== $ruta) @unlink($sql); }
    }

    private static function restaurarImagenes(ZipArchive $z): void
    {
        $up = ROOT_PATH . '/public/uploads';
        for ($i = 0; $i < $z->numFiles; $i++) {
            $n = $z->getNameIndex($i);
            if (strncmp($n, 'uploads/', 8) !== 0 || substr($n, -1) === '/' || str_contains($n, '..') || preg_match('/\.(php\d?|phtml|phar|htaccess)$/i', $n)) continue;
            $dest = $up . '/' . substr($n, 8);
            if (!is_dir(dirname($dest))) @mkdir(dirname($dest), 0775, true);
            $in = $z->getStream($n); if (!$in) continue;
            $out = fopen($dest, 'wb'); stream_copy_to_stream($in, $out); fclose($out); fclose($in);
        }
    }

    /* ---------- Resetear ---------- */

    /** Vacía los datos del negocio. Opciones: usuarios, config, catalogos (bool). Devuelve la copia de seguridad previa. */
    public static function resetear(array $op, int $usuarioActual): string
    {
        @set_time_limit(0);
        $seg = self::crear('seguridad', true);
        $pdo = Database::conn();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        try {
            $keep = self::SIEMPRE;
            if (!empty($op['config'])) $keep[] = 'configuracion';
            if (!empty($op['usuarios'])) $keep[] = 'usuarios';
            if (!empty($op['catalogos'])) $keep = array_merge($keep, self::CATALOGOS);
            foreach (self::tablas() as $t) {
                if (in_array($t, $keep, true) || $t === 'bitacora' || $t === 'usuarios' || $t === 'configuracion') continue;
                $pdo->exec('TRUNCATE TABLE `' . str_replace('`', '``', $t) . '`');
            }
            $pdo->exec('TRUNCATE TABLE bitacora');
            if (empty($op['usuarios'])) Database::exec('DELETE FROM usuarios WHERE id <> ?', [$usuarioActual]);
            if (empty($op['config'])) {
                $logo = (string)Database::val("SELECT valor FROM configuracion WHERE clave = 'logo'");
                if ($logo !== '' && is_file(ROOT_PATH . '/public/' . $logo)) @unlink(ROOT_PATH . '/public/' . $logo);
                $pdo->exec('TRUNCATE TABLE configuracion');
                $pdo->exec("INSERT INTO configuracion (clave, valor) VALUES ('gimnasio_nombre','Mi Gimnasio'),('gimnasio_ruc',''),('gimnasio_direccion',''),('gimnasio_telefono',''),('moneda','S/'),('aforo_maximo','120'),('dias_aviso_vencimiento','7')");
            }
            // Fotos de socios (los datos ya no existen)
            $f = ROOT_PATH . '/public/uploads/socios';
            if (is_dir($f)) foreach (glob($f . '/*') ?: [] as $x) if (is_file($x)) @unlink($x);
        } finally { $pdo->exec('SET FOREIGN_KEY_CHECKS = 1'); }
        return $seg;
    }

    /* ---------- Automático ---------- */

    public static function auto(): void
    {
        try {
            $modo = cfg('backup_auto', 'off');
            if ($modo === 'off') return;
            $dias = $modo === 'semanal' ? 7 : 1;
            $ult = cfg('backup_ultimo_auto', '');
            if ($ult !== '' && strtotime($ult) > time() - $dias * 86400 + 3600) return;
            Database::exec("INSERT INTO configuracion (clave, valor) VALUES ('backup_ultimo_auto', ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)", [date('Y-m-d H:i:s')]);
            self::crear('auto', true);
        } catch (Throwable $e) { /* el respaldo automático nunca debe romper la pantalla */ }
    }
}
