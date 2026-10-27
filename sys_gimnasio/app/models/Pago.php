<?php
require_once ROOT_PATH . '/app/models/Membresia.php';

class Pago
{
    public static function crear(?int $membresiaId, int $socioId, float $monto, string $metodo, string $concepto, int $usuarioId, ?string $fecha = null): int
    {
        Database::exec(
            'INSERT INTO pagos (membresia_id, socio_id, monto, metodo, concepto, fecha_pago, usuario_id) VALUES (?,?,?,?,?,?,?)',
            [$membresiaId, $socioId, $monto, $metodo, $concepto, $fecha ?: date('Y-m-d H:i:s'), $usuarioId]
        );
        return (int)Database::conn()->lastInsertId();
    }

    public static function metodos(): array
    {
        return ['efectivo' => 'Efectivo', 'yape' => 'Yape', 'plin' => 'Plin', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia'];
    }

    private static function where(array $f, array &$p): string
    {
        $w = ['pg.fecha_pago >= ? AND pg.fecha_pago < ?'];
        $p[] = $f['desde']; $p[] = date('Y-m-d', strtotime($f['hasta'] . ' +1 day'));
        if (!empty($f['metodo']) && isset(self::metodos()[$f['metodo']])) { $w[] = 'pg.metodo = ?'; $p[] = $f['metodo']; }
        if (($f['q'] ?? '') !== '') {
            $like = '%' . $f['q'] . '%';
            $w[] = "(s.nombres LIKE ? OR s.apellidos LIKE ? OR CONCAT(s.nombres,' ',s.apellidos) LIKE ? OR s.dni LIKE ? OR s.codigo LIKE ?)";
            array_push($p, $like, $like, $like, $like, $like);
        }
        return implode(' AND ', $w);
    }

    public static function contar(array $f): int
    {
        $p = []; $w = self::where($f, $p);
        return (int)Database::val('SELECT COUNT(*) FROM pagos pg JOIN socios s ON s.id = pg.socio_id WHERE ' . $w, $p);
    }

    public static function listar(array $f, int $limit, int $offset): array
    {
        $p = []; $w = self::where($f, $p);
        return Database::all(
            'SELECT pg.*, s.codigo, s.nombres, s.apellidos, u.nombre cajero
             FROM pagos pg JOIN socios s ON s.id = pg.socio_id LEFT JOIN usuarios u ON u.id = pg.usuario_id
             WHERE ' . $w . ' ORDER BY pg.fecha_pago DESC, pg.id DESC LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset,
            $p
        );
    }

    public static function totales(array $f): array
    {
        $p = []; $w = self::where($f, $p);
        $rows = Database::all(
            'SELECT pg.metodo, COUNT(*) n, SUM(pg.monto) t FROM pagos pg JOIN socios s ON s.id = pg.socio_id WHERE ' . $w . ' GROUP BY pg.metodo', $p
        );
        $o = ['total' => 0.0, 'n' => 0, 'metodos' => []];
        foreach ($rows as $r) { $o['total'] += (float)$r['t']; $o['n'] += (int)$r['n']; $o['metodos'][$r['metodo']] = (float)$r['t']; }
        return $o;
    }

    public static function deudas(int $limit = 200): array
    {
        return Database::all(
            "SELECT * FROM (
                SELECT m.id, m.fecha_inicio, m.fecha_fin, s.id socio_id, s.codigo, s.nombres, s.apellidos, s.telefono, p.nombre plan,
                       (m.precio - m.descuento) total, " . Membresia::PAGADO . " pagado
                FROM membresias m JOIN socios s ON s.id = m.socio_id JOIN planes p ON p.id = m.plan_id
                WHERE m.estado <> 'cancelada') t
             WHERE t.total - t.pagado > 0.009 ORDER BY t.fecha_fin ASC LIMIT " . (int)$limit
        );
    }

    public static function find(int $id): ?array
    {
        return Database::row(
            'SELECT pg.*, s.codigo, s.nombres, s.apellidos, s.dni, u.nombre cajero, p.nombre plan, m.fecha_inicio, m.fecha_fin,
                    (m.precio - m.descuento) total_mem
             FROM pagos pg JOIN socios s ON s.id = pg.socio_id LEFT JOIN usuarios u ON u.id = pg.usuario_id
             LEFT JOIN membresias m ON m.id = pg.membresia_id LEFT JOIN planes p ON p.id = m.plan_id WHERE pg.id = ?',
            [$id]
        );
    }
}
