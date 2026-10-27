<?php
class Membresia
{
    /** Subconsulta de lo pagado por membresía */
    public const PAGADO = '(SELECT COALESCE(SUM(pg.monto),0) FROM pagos pg WHERE pg.membresia_id = m.id)';

    /** Crea una membresía (y opcionalmente su primer pago). Debe llamarse dentro de una transacción. */
    public static function crear(int $socioId, array $plan, string $inicio, float $descuento, int $usuarioId): int
    {
        $fin = date('Y-m-d', strtotime($inicio . ' +' . ((int)$plan['duracion_dias'] - 1) . ' days'));
        Database::exec(
            "INSERT INTO membresias (socio_id, plan_id, fecha_inicio, fecha_fin, precio, descuento, estado, usuario_id)
             VALUES (?,?,?,?,?,?, 'activa', ?)",
            [$socioId, $plan['id'], $inicio, $fin, $plan['precio'], $descuento, $usuarioId]
        );
        return (int)Database::conn()->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        return Database::row(
            'SELECT m.*, p.nombre plan, p.color plan_color, ' . self::PAGADO . ' pagado, (m.precio - m.descuento) total
             FROM membresias m JOIN planes p ON p.id = m.plan_id WHERE m.id = ?',
            [$id]
        );
    }

    /** Fin de la membresía vigente o más reciente (para sugerir la fecha de inicio al renovar) */
    public static function ultimoFin(int $socioId): ?string
    {
        $f = Database::val("SELECT MAX(fecha_fin) FROM membresias WHERE socio_id = ? AND estado <> 'cancelada'", [$socioId]);
        return $f ?: null;
    }

    private static function where(array $f, array &$p): string
    {
        $w = ['1=1'];
        $hoy = date('Y-m-d');
        switch ($f['vista'] ?? 'vigentes') {
            case 'vigentes':  $w[] = "m.estado <> 'cancelada' AND m.fecha_fin >= ?"; $p[] = $hoy; break;
            case 'porvencer': $w[] = "m.estado <> 'cancelada' AND m.fecha_fin >= ? AND m.fecha_fin <= ?"; array_push($p, $hoy, date('Y-m-d', strtotime('+7 days'))); break;
            case 'vencidas':  $w[] = "m.estado <> 'cancelada' AND m.fecha_fin < ?"; $p[] = $hoy; break;
            case 'canceladas':$w[] = "m.estado = 'cancelada'"; break;
        }
        if (($f['q'] ?? '') !== '') {
            $like = '%' . $f['q'] . '%';
            $w[] = "(s.nombres LIKE ? OR s.apellidos LIKE ? OR CONCAT(s.nombres,' ',s.apellidos) LIKE ? OR s.dni LIKE ? OR s.codigo LIKE ?)";
            array_push($p, $like, $like, $like, $like, $like);
        }
        if (!empty($f['plan'])) { $w[] = 'm.plan_id = ?'; $p[] = (int)$f['plan']; }
        return implode(' AND ', $w);
    }

    public static function contar(array $f): int
    {
        $p = []; $w = self::where($f, $p);
        return (int)Database::val('SELECT COUNT(*) FROM membresias m JOIN socios s ON s.id = m.socio_id WHERE ' . $w, $p);
    }

    public static function listar(array $f, int $limit, int $offset): array
    {
        $p = []; $w = self::where($f, $p);
        $ord = in_array($f['vista'] ?? '', ['vigentes', 'porvencer'], true) ? 'm.fecha_fin ASC' : 'm.fecha_fin DESC';
        return Database::all(
            'SELECT m.*, s.codigo, s.nombres, s.apellidos, s.telefono, p.nombre plan, p.color plan_color,
                    ' . self::PAGADO . ' pagado, (m.precio - m.descuento) total
             FROM membresias m JOIN socios s ON s.id = m.socio_id JOIN planes p ON p.id = m.plan_id
             WHERE ' . $w . ' ORDER BY ' . $ord . ', m.id DESC LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset,
            $p
        );
    }

    public static function conteos(): array
    {
        $hoy = date('Y-m-d'); $en7 = date('Y-m-d', strtotime('+7 days'));
        $r = Database::row(
            "SELECT SUM(estado <> 'cancelada' AND fecha_fin >= ?) vigentes,
                    SUM(estado <> 'cancelada' AND fecha_fin >= ? AND fecha_fin <= ?) porvencer,
                    SUM(estado <> 'cancelada' AND fecha_fin < ?) vencidas,
                    SUM(estado = 'cancelada') canceladas, COUNT(*) todas
             FROM membresias", [$hoy, $hoy, $en7, $hoy]
        ) ?? [];
        return array_map('intval', $r);
    }

    /** Membresías con saldo pendiente de un socio */
    public static function conSaldo(int $socioId): array
    {
        return Database::all(
            "SELECT * FROM (
                SELECT m.id, m.fecha_inicio, m.fecha_fin, p.nombre plan, (m.precio - m.descuento) total, " . self::PAGADO . " pagado
                FROM membresias m JOIN planes p ON p.id = m.plan_id
                WHERE m.socio_id = ? AND m.estado <> 'cancelada') t
             WHERE t.total - t.pagado > 0.009 ORDER BY t.fecha_inicio DESC",
            [$socioId]
        );
    }
}
