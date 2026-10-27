<?php
require_once ROOT_PATH . '/app/models/Pago.php';

class Caja
{
    public static function ensure(): void
    {
        static $done = false;
        if ($done) return;
        $pdo = Database::conn();
        $pdo->exec("CREATE TABLE IF NOT EXISTS cajas (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT UNSIGNED NULL,
            apertura DATETIME NOT NULL,
            monto_inicial DECIMAL(10,2) NOT NULL DEFAULT 0,
            cierre DATETIME NULL,
            esperado DECIMAL(10,2) NULL,
            monto_contado DECIMAL(10,2) NULL,
            diferencia DECIMAL(10,2) NULL,
            observacion VARCHAR(255) NULL,
            estado ENUM('abierta','cerrada') NOT NULL DEFAULT 'abierta',
            INDEX idx_estado (estado, apertura)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS caja_movimientos (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            caja_id INT UNSIGNED NOT NULL,
            tipo ENUM('ingreso','egreso') NOT NULL,
            concepto VARCHAR(150) NOT NULL,
            monto DECIMAL(10,2) NOT NULL,
            fecha DATETIME NOT NULL,
            usuario_id INT UNSIGNED NULL,
            INDEX idx_caja (caja_id),
            FOREIGN KEY (caja_id) REFERENCES cajas(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $done = true;
    }

    public static function abierta(): ?array
    {
        self::ensure();
        return Database::row("SELECT c.*, u.nombre usuario FROM cajas c LEFT JOIN usuarios u ON u.id = c.usuario_id WHERE c.estado = 'abierta' ORDER BY c.id DESC LIMIT 1");
    }

    public static function find(int $id): ?array
    {
        self::ensure();
        return Database::row("SELECT c.*, u.nombre usuario FROM cajas c LEFT JOIN usuarios u ON u.id = c.usuario_id WHERE c.id = ?", [$id]);
    }

    /** Totales del turno: cobros y ventas por método + movimientos manuales + efectivo esperado */
    public static function resumen(array $c): array
    {
        $fin = $c['cierre'] ?: date('Y-m-d H:i:s');
        $met = array_fill_keys(array_keys(Pago::metodos()), 0.0);
        $mem = $met; $tie = $met;
        foreach (Database::all('SELECT metodo, SUM(monto) t FROM pagos WHERE fecha_pago >= ? AND fecha_pago <= ? GROUP BY metodo', [$c['apertura'], $fin]) as $r) { $mem[$r['metodo']] = (float)$r['t']; $met[$r['metodo']] += (float)$r['t']; }
        foreach (Database::all('SELECT metodo, SUM(total) t FROM ventas WHERE fecha >= ? AND fecha <= ? GROUP BY metodo', [$c['apertura'], $fin]) as $r) { $tie[$r['metodo']] = (float)$r['t']; $met[$r['metodo']] += (float)$r['t']; }
        $mov = Database::row("SELECT COALESCE(SUM(CASE WHEN tipo='ingreso' THEN monto END),0) ing, COALESCE(SUM(CASE WHEN tipo='egreso' THEN monto END),0) egr FROM caja_movimientos WHERE caja_id = ?", [$c['id']]);
        $ini = (float)$c['monto_inicial'];
        return [
            'metodos' => $met, 'membresias' => array_sum($mem), 'tienda' => array_sum($tie), 'total_cobrado' => array_sum($met),
            'ingresos' => (float)$mov['ing'], 'egresos' => (float)$mov['egr'], 'inicial' => $ini,
            'esperado' => $ini + $met['efectivo'] + (float)$mov['ing'] - (float)$mov['egr'],
        ];
    }

    public static function movimientos(int $cajaId): array
    {
        return Database::all('SELECT m.*, u.nombre usuario FROM caja_movimientos m LEFT JOIN usuarios u ON u.id = m.usuario_id WHERE m.caja_id = ? ORDER BY m.id DESC', [$cajaId]);
    }
}
