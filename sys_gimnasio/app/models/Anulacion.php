<?php
require_once ROOT_PATH . '/app/models/Producto.php';

/** Anulación de pagos y ventas (con respaldo auditable) y congelamiento de membresías. */
class Anulacion
{
    public static function ensure(): void
    {
        static $done = false;
        if ($done) return;
        $pdo = Database::conn();
        $pdo->exec("CREATE TABLE IF NOT EXISTS anulaciones (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tipo VARCHAR(8) NOT NULL, ref_id INT UNSIGNED NOT NULL, socio_id INT UNSIGNED NULL, monto DECIMAL(10,2) NOT NULL DEFAULT 0,
            metodo VARCHAR(15) NULL, detalle TEXT NULL, motivo VARCHAR(200) NOT NULL, usuario_id INT UNSIGNED NULL, fecha_original DATETIME NULL, fecha DATETIME NOT NULL,
            INDEX idx_tipo (tipo, fecha)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS membresia_congelamientos (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, membresia_id INT UNSIGNED NOT NULL, desde DATE NOT NULL, hasta DATE NULL, dias INT UNSIGNED NULL,
            motivo VARCHAR(200) NOT NULL, usuario_id INT UNSIGNED NULL, created_at DATETIME NOT NULL, INDEX idx_m (membresia_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $done = true;
    }

    public static function anularPago(int $id, string $motivo, int $usuario): string
    {
        self::ensure();
        $p = Database::row('SELECT pg.*, s.nombres, s.apellidos FROM pagos pg JOIN socios s ON s.id = pg.socio_id WHERE pg.id = ?', [$id]);
        if (!$p) throw new RuntimeException('El pago no existe o ya fue anulado.');
        $pdo = Database::conn(); $pdo->beginTransaction();
        try {
            Database::exec('INSERT INTO anulaciones (tipo, ref_id, socio_id, monto, metodo, detalle, motivo, usuario_id, fecha_original, fecha) VALUES (?,?,?,?,?,?,?,?,?,NOW())',
                ['pago', $id, $p['socio_id'], $p['monto'], $p['metodo'], json_encode(['socio' => trim($p['nombres'] . ' ' . $p['apellidos']), 'concepto' => $p['concepto'], 'membresia_id' => $p['membresia_id']], JSON_UNESCAPED_UNICODE), $motivo, $usuario, $p['fecha_pago']]);
            Database::exec('DELETE FROM pagos WHERE id = ?', [$id]);
            $pdo->commit();
        } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
        return 'Pago N° ' . sprintf('%06d', $id) . ' de ' . money($p['monto']) . ' anulado. El saldo de la membresía se recalculó.';
    }

    public static function anularVenta(int $id, string $motivo, int $usuario): string
    {
        self::ensure(); Producto::ensure();
        $pdo = Database::conn(); $pdo->beginTransaction();
        try {
            $v = Database::row('SELECT v.*, s.nombres, s.apellidos FROM ventas v LEFT JOIN socios s ON s.id = v.socio_id WHERE v.id = ? FOR UPDATE', [$id]);
            if (!$v) throw new RuntimeException('La venta no existe o ya fue anulada.');
            $items = Database::all('SELECT d.producto_id, d.cantidad, d.precio, p.nombre FROM venta_detalle d JOIN productos p ON p.id = d.producto_id WHERE d.venta_id = ?', [$id]);
            foreach ($items as $it) Producto::mover((int)$it['producto_id'], 'entrada', (int)$it['cantidad'], 'Anulación de venta N° ' . sprintf('%06d', $id));
            Database::exec('INSERT INTO anulaciones (tipo, ref_id, socio_id, monto, metodo, detalle, motivo, usuario_id, fecha_original, fecha) VALUES (?,?,?,?,?,?,?,?,?,NOW())',
                ['venta', $id, $v['socio_id'], $v['total'], $v['metodo'], json_encode(['cliente' => $v['nombres'] ? trim($v['nombres'] . ' ' . $v['apellidos']) : 'Cliente libre', 'items' => $items], JSON_UNESCAPED_UNICODE), $motivo, $usuario, $v['fecha']]);
            Database::exec('DELETE FROM venta_detalle WHERE venta_id = ?', [$id]);
            Database::exec('DELETE FROM ventas WHERE id = ?', [$id]);
            $pdo->commit();
        } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
        return 'Venta N° ' . sprintf('%06d', $id) . ' anulada. El stock de ' . count($items) . ' producto(s) fue devuelto al inventario.';
    }

    public static function congelar(int $mid, string $motivo, int $usuario): string
    {
        self::ensure();
        $pdo = Database::conn(); $pdo->beginTransaction();
        try {
            $m = Database::row('SELECT * FROM membresias WHERE id = ? FOR UPDATE', [$mid]);
            if (!$m) throw new RuntimeException('La membresía no existe.');
            if ($m['estado'] === 'congelada') throw new RuntimeException('La membresía ya está congelada.');
            if ($m['estado'] === 'cancelada') throw new RuntimeException('No se puede congelar una membresía cancelada.');
            if ($m['fecha_fin'] < date('Y-m-d')) throw new RuntimeException('La membresía ya venció: renuévala en lugar de congelarla.');
            if ($m['fecha_inicio'] > date('Y-m-d')) throw new RuntimeException('La membresía aún no inicia.');
            Database::exec("UPDATE membresias SET estado = 'congelada' WHERE id = ?", [$mid]);
            Database::exec('INSERT INTO membresia_congelamientos (membresia_id, desde, motivo, usuario_id, created_at) VALUES (?,?,?,?,NOW())', [$mid, date('Y-m-d'), $motivo, $usuario]);
            $pdo->commit();
        } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
        return 'Membresía congelada. Al reactivarla se sumarán los días congelados a su fecha de fin.';
    }

    public static function descongelar(int $mid, int $usuario): string
    {
        self::ensure();
        $pdo = Database::conn(); $pdo->beginTransaction();
        try {
            $m = Database::row('SELECT * FROM membresias WHERE id = ? FOR UPDATE', [$mid]);
            if (!$m || $m['estado'] !== 'congelada') throw new RuntimeException('La membresía no está congelada.');
            $c = Database::row('SELECT * FROM membresia_congelamientos WHERE membresia_id = ? AND hasta IS NULL ORDER BY id DESC LIMIT 1', [$mid]);
            $hoy = date('Y-m-d'); $dias = $c ? max(0, (int)((strtotime($hoy) - strtotime($c['desde'])) / 86400)) : 0;
            $fin = date('Y-m-d', strtotime($m['fecha_fin'] . " +$dias days"));
            Database::exec("UPDATE membresias SET estado = 'activa', fecha_fin = ? WHERE id = ?", [$fin, $mid]);
            if ($c) Database::exec('UPDATE membresia_congelamientos SET hasta = ?, dias = ? WHERE id = ?', [$hoy, $dias, $c['id']]);
            $pdo->commit();
        } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
        return 'Membresía reactivada: se sumaron ' . $dias . ' día' . ($dias === 1 ? '' : 's') . ' y ahora vence el ' . fdate($fin) . '.';
    }

    public static function listar(string $tipo = ''): array
    {
        self::ensure();
        $w = in_array($tipo, ['pago', 'venta'], true) ? 'WHERE a.tipo = ' . Database::conn()->quote($tipo) : '';
        return Database::all("SELECT a.*, u.nombre usuario, s.nombres, s.apellidos FROM anulaciones a LEFT JOIN usuarios u ON u.id = a.usuario_id LEFT JOIN socios s ON s.id = a.socio_id $w ORDER BY a.id DESC LIMIT 300");
    }
}
