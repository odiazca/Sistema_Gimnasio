<?php
class Producto
{
    public static function ensure(): void
    {
        static $done = false;
        if ($done) return;
        Database::conn()->exec("CREATE TABLE IF NOT EXISTS movimientos_stock (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            producto_id INT UNSIGNED NOT NULL,
            tipo ENUM('entrada','salida','ajuste','venta','inicial') NOT NULL,
            cantidad INT NOT NULL,
            stock_final INT NOT NULL,
            motivo VARCHAR(160) NULL,
            usuario_id INT UNSIGNED NULL,
            fecha DATETIME NOT NULL,
            INDEX idx_prod (producto_id, fecha),
            FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $done = true;
    }

    public static function categorias(): array
    {
        return array_column(Database::all("SELECT DISTINCT categoria FROM productos WHERE categoria IS NOT NULL AND categoria <> '' ORDER BY categoria"), 'categoria');
    }

    private static function where(array $f, array &$p): string
    {
        $w = ['1=1'];
        if (($f['q'] ?? '') !== '') { $w[] = '(nombre LIKE ? OR codigo LIKE ?)'; $p[] = '%' . $f['q'] . '%'; $p[] = '%' . $f['q'] . '%'; }
        if (($f['cat'] ?? '') !== '') { $w[] = 'categoria = ?'; $p[] = $f['cat']; }
        if (($f['stock'] ?? '') === 'bajo') $w[] = 'estado = 1 AND stock > 0 AND stock <= stock_minimo';
        if (($f['stock'] ?? '') === 'agotado') $w[] = 'estado = 1 AND stock <= 0';
        return implode(' AND ', $w);
    }

    public static function listar(array $f, int $limit, int $off): array
    {
        $p = []; $w = self::where($f, $p);
        return Database::all("SELECT * FROM productos WHERE $w ORDER BY estado DESC, nombre LIMIT $limit OFFSET $off", $p);
    }

    public static function contar(array $f): int
    {
        $p = []; $w = self::where($f, $p);
        return (int)Database::val("SELECT COUNT(*) FROM productos WHERE $w", $p);
    }

    public static function resumen(): array
    {
        return Database::row(
            "SELECT COUNT(*) total, COALESCE(SUM(stock * precio_compra),0) valor_costo, COALESCE(SUM(stock * precio_venta),0) valor_venta,
                    COALESCE(SUM(estado = 1 AND stock > 0 AND stock <= stock_minimo),0) bajo, COALESCE(SUM(estado = 1 AND stock <= 0),0) agotado
             FROM productos"
        );
    }

    public static function find(int $id): ?array { return Database::row('SELECT * FROM productos WHERE id = ?', [$id]); }

    public static function siguienteCodigo(): string
    {
        $max = (int)Database::val("SELECT COALESCE(MAX(CAST(SUBSTRING(codigo,2) AS UNSIGNED)),0) FROM productos WHERE codigo REGEXP '^P[0-9]+$'");
        return 'P' . str_pad((string)($max + 1), 3, '0', STR_PAD_LEFT);
    }

    /** Registra un movimiento y actualiza el stock. Debe llamarse dentro de una transacción. */
    public static function mover(int $id, string $tipo, int $cantidad, ?string $motivo): int
    {
        self::ensure();
        $cur = (int)Database::val('SELECT stock FROM productos WHERE id = ? FOR UPDATE', [$id]);
        $nuevo = $tipo === 'ajuste' ? $cantidad : (in_array($tipo, ['entrada', 'inicial'], true) ? $cur + $cantidad : $cur - $cantidad);
        $delta = $nuevo - $cur;
        Database::exec('UPDATE productos SET stock = ? WHERE id = ?', [$nuevo, $id]);
        Database::exec('INSERT INTO movimientos_stock (producto_id,tipo,cantidad,stock_final,motivo,usuario_id,fecha) VALUES (?,?,?,?,?,?,NOW())',
            [$id, $tipo, $tipo === 'ajuste' ? $delta : $cantidad, $nuevo, $motivo, Auth::user()['id'] ?? null]);
        return $nuevo;
    }

    public static function kardex(int $id, int $limit = 60): array
    {
        self::ensure();
        return Database::all("SELECT m.*, u.nombre usuario FROM movimientos_stock m LEFT JOIN usuarios u ON u.id = m.usuario_id
                              WHERE m.producto_id = ? ORDER BY m.id DESC LIMIT $limit", [$id]);
    }
}
