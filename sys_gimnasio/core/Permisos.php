<?php
/** Permisos por rol (módulo => rol) y bitácora de acciones. El rol 1 (Administrador) siempre tiene acceso total. */
class Permisos
{
    public const SOLO_ADMIN = ['usuarios', 'configuracion', 'asistente', 'chat', 'anulaciones', 'sistema'];

    /** controlador => módulo de permiso (los no listados son de acceso libre para usuarios autenticados) */
    private const CTRL = [
        'dashboard' => 'dashboard', 'socios' => 'socios', 'planes' => 'membresias', 'membresias' => 'membresias', 'pagos' => 'pagos',
        'asistencia' => 'asistencia', 'clases' => 'clases', 'entrenadores' => 'entrenadores', 'rutinas' => 'rutinas', 'evaluaciones' => 'evaluaciones',
        'ventas' => 'ventas', 'inventario' => 'inventario', 'caja' => 'caja', 'gastos' => 'gastos', 'reportes' => 'reportes',
        'notificaciones' => 'notificaciones', 'crm' => 'crm', 'usuarios' => 'usuarios', 'configuracion' => 'configuracion', 'asistente' => 'asistente', 'chat' => 'chat', 'anulaciones' => 'anulaciones', 'sistema' => 'sistema'
    ];

    private const DEFAULTS = [
        2 => ['dashboard', 'socios', 'membresias', 'pagos', 'asistencia', 'clases', 'ventas', 'inventario', 'caja', 'notificaciones', 'crm'],
        3 => ['dashboard', 'socios', 'asistencia', 'clases', 'entrenadores', 'rutinas', 'evaluaciones'],
        4 => ['dashboard', 'pagos', 'ventas', 'caja', 'gastos', 'reportes'],
    ];

    private static ?array $mapa = null;

    public static function ensure(): void
    {
        static $done = false;
        if ($done) return;
        $pdo = Database::conn();
        $nuevo = !$pdo->query("SHOW TABLES LIKE 'rol_permisos'")->fetchColumn();
        $pdo->exec("CREATE TABLE IF NOT EXISTS rol_permisos (rol_id TINYINT UNSIGNED NOT NULL, modulo VARCHAR(30) NOT NULL, PRIMARY KEY (rol_id, modulo)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS bitacora (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, usuario_id INT UNSIGNED NULL, usuario VARCHAR(80) NULL, accion VARCHAR(40) NOT NULL,
            detalle VARCHAR(255) NULL, ip VARCHAR(45) NULL, fecha DATETIME NOT NULL, INDEX idx_fecha (fecha)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $done = true;
        if (!$pdo->query("SELECT 1 FROM configuracion WHERE clave = 'perm_crm_sembrado'")->fetchColumn()) {
            $pdo->exec("INSERT IGNORE INTO rol_permisos (rol_id, modulo) VALUES (2, 'crm')");
            $pdo->exec("INSERT INTO configuracion (clave, valor) VALUES ('perm_crm_sembrado', '1') ON DUPLICATE KEY UPDATE valor = valor");
        }
        if ($nuevo) {
            $st = $pdo->prepare('INSERT IGNORE INTO rol_permisos (rol_id, modulo) VALUES (?, ?)');
            foreach (self::DEFAULTS as $rol => $mods) foreach ($mods as $m) $st->execute([$rol, $m]);
        }
    }

    public static function moduloDe(string $ctrl, string $metodo): ?string
    {
        $ctrl = strtolower($ctrl);
        if ($ctrl === 'socios' && strtolower($metodo) === 'buscar') return null; // autocompletado compartido
        return self::CTRL[$ctrl] ?? null;
    }

    public static function rolId(): int
    {
        if (!isset($_SESSION['user']['rol_id'])) {
            $_SESSION['user']['rol_id'] = (int)Database::val('SELECT rol_id FROM usuarios WHERE id = ?', [(int)($_SESSION['user']['id'] ?? 0)]);
        }
        return (int)$_SESSION['user']['rol_id'];
    }

    public static function esAdmin(): bool { return Auth::check() && self::rolId() === 1; }

    public static function mapa(): array
    {
        if (self::$mapa === null) {
            self::ensure(); self::$mapa = [];
            foreach (Database::all('SELECT rol_id, modulo FROM rol_permisos') as $r) self::$mapa[(int)$r['rol_id']][$r['modulo']] = true;
        }
        return self::$mapa;
    }

    public static function can(string $modulo): bool
    {
        if (!Auth::check()) return false;
        if (self::rolId() === 1) return true;
        if (in_array($modulo, self::SOLO_ADMIN, true)) return false;
        return !empty(self::mapa()[self::rolId()][$modulo]);
    }

    public static function primerModulo(): ?string
    {
        foreach (['dashboard', 'socios', 'asistencia', 'pagos', 'ventas', 'clases', 'caja'] as $m) if (self::can($m)) return $m;
        foreach (array_keys(self::CTRL) as $m) if (self::can($m)) return $m;
        return null;
    }

    public static function guardar(array $matriz): void
    {
        self::ensure();
        $validos = array_unique(array_values(self::CTRL));
        $pdo = Database::conn(); $pdo->beginTransaction();
        Database::exec('DELETE FROM rol_permisos WHERE rol_id > 1');
        $st = $pdo->prepare('INSERT IGNORE INTO rol_permisos (rol_id, modulo) VALUES (?, ?)');
        foreach ($matriz as $rol => $mods) {
            $rol = (int)$rol; if ($rol < 2 || !is_array($mods)) continue;
            foreach (array_keys($mods) as $m) if (in_array($m, $validos, true) && !in_array($m, self::SOLO_ADMIN, true)) $st->execute([$rol, $m]);
        }
        $pdo->commit(); self::$mapa = null;
    }
}

class Bitacora
{
    public static function log(string $accion, string $detalle = ''): void
    {
        try {
            Permisos::ensure();
            $u = Auth::user();
            Database::exec('INSERT INTO bitacora (usuario_id, usuario, accion, detalle, ip, fecha) VALUES (?,?,?,?,?,NOW())',
                [$u['id'] ?? null, $u['usuario'] ?? null, mb_substr($accion, 0, 40), mb_substr($detalle, 0, 255) ?: null, substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45)]);
        } catch (Throwable $e) { /* la bitácora nunca debe romper la operación */ }
    }
}
