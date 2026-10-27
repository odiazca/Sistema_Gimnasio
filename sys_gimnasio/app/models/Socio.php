<?php
class Socio
{
    /** Une cada socio con su última membresía no cancelada */
    private const ULT = " LEFT JOIN (
        SELECT m.socio_id, m.fecha_inicio, m.fecha_fin, m.plan_id FROM membresias m
        WHERE m.estado <> 'cancelada' AND m.id = (
            SELECT m2.id FROM membresias m2 WHERE m2.socio_id = m.socio_id AND m2.estado <> 'cancelada'
            ORDER BY m2.fecha_fin DESC, m2.id DESC LIMIT 1)
      ) ult ON ult.socio_id = s.id
      LEFT JOIN planes pl ON pl.id = ult.plan_id ";

    private static function where(array $f, array &$p): string
    {
        $w = ['1=1'];
        if (($f['q'] ?? '') !== '') {
            $like = '%' . $f['q'] . '%';
            $w[] = "(s.nombres LIKE ? OR s.apellidos LIKE ? OR CONCAT(s.nombres,' ',s.apellidos) LIKE ? OR s.dni LIKE ? OR s.codigo LIKE ? OR s.telefono LIKE ?)";
            array_push($p, $like, $like, $like, $like, $like, $like);
        }
        if (in_array($f['estado'] ?? '', ['activo', 'inactivo', 'suspendido'], true)) {
            $w[] = 's.estado = ?'; $p[] = $f['estado'];
        }
        $hoy = date('Y-m-d');
        switch ($f['mem'] ?? '') {
            case 'vigente':   $w[] = 'ult.fecha_fin >= ?'; $p[] = $hoy; break;
            case 'porvencer': $w[] = 'ult.fecha_fin >= ? AND ult.fecha_fin <= ?'; array_push($p, $hoy, date('Y-m-d', strtotime('+7 days'))); break;
            case 'vencida':   $w[] = 'ult.fecha_fin < ?'; $p[] = $hoy; break;
            case 'sin':       $w[] = 'ult.fecha_fin IS NULL'; break;
        }
        return implode(' AND ', $w);
    }

    public static function contar(array $f): int
    {
        $p = [];
        $w = self::where($f, $p);
        return (int)Database::val('SELECT COUNT(*) FROM socios s' . self::ULT . 'WHERE ' . $w, $p);
    }

    public static function listar(array $f, int $limit, int $offset): array
    {
        $p = [];
        $w = self::where($f, $p);
        return Database::all(
            'SELECT s.*, ult.fecha_inicio, ult.fecha_fin, pl.nombre plan, pl.color plan_color
             FROM socios s' . self::ULT . 'WHERE ' . $w . ' ORDER BY s.id DESC LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset,
            $p
        );
    }

    public static function resumen(): array
    {
        return Database::row(
            "SELECT COUNT(*) total,
                    SUM(s.estado='activo') activos,
                    SUM(ult.fecha_fin >= CURDATE()) vigentes,
                    SUM(ult.fecha_fin < CURDATE()) vencidos,
                    SUM(ult.fecha_fin IS NULL) sin_mem
             FROM socios s" . self::ULT
        ) ?? [];
    }

    public static function find(int $id): ?array
    {
        return Database::row('SELECT * FROM socios WHERE id = ?', [$id]);
    }

    public static function buscar(string $q, int $limit = 8): array
    {
        $like = '%' . $q . '%';
        return Database::all(
            "SELECT s.id, s.codigo, s.dni, s.nombres, s.apellidos, s.telefono, ult.fecha_fin, pl.nombre plan
             FROM socios s" . self::ULT . "
             WHERE s.nombres LIKE ? OR s.apellidos LIKE ? OR CONCAT(s.nombres,' ',s.apellidos) LIKE ? OR s.dni LIKE ? OR s.codigo LIKE ?
             ORDER BY s.nombres LIMIT " . (int)$limit,
            [$like, $like, $like, $like, $like]
        );
    }

    public static function dniExiste(string $dni, int $exceptId = 0): bool
    {
        return (int)Database::val('SELECT COUNT(*) FROM socios WHERE dni = ? AND id <> ?', [$dni, $exceptId]) > 0;
    }
}
