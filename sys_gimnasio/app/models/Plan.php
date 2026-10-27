<?php
class Plan
{
    public static function activos(): array
    {
        return Database::all('SELECT * FROM planes WHERE estado = 1 ORDER BY duracion_dias, precio');
    }

    public static function find(int $id): ?array
    {
        return Database::row('SELECT * FROM planes WHERE id = ?', [$id]);
    }

    public static function conEstadisticas(): array
    {
        $hoy = date('Y-m-d');
        return Database::all(
            "SELECT p.*,
                (SELECT COUNT(*) FROM membresias m WHERE m.plan_id = p.id AND m.estado <> 'cancelada' AND m.fecha_inicio <= ? AND m.fecha_fin >= ?) vigentes,
                (SELECT COUNT(*) FROM membresias m WHERE m.plan_id = p.id) total_mem
             FROM planes p ORDER BY p.estado DESC, p.duracion_dias, p.precio",
            [$hoy, $hoy]
        );
    }
}
