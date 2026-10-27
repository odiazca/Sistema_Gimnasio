<?php
class Clase
{
    public static function dias(): array
    {
        return [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
    }

    /** Próxima fecha (hoy incluida) que cae en el día de semana dado (1=lun..7=dom) */
    public static function proximaFecha(int $dia, ?string $desde = null): string
    {
        $base = $desde ?: date('Y-m-d');
        $dow = (int)date('N', strtotime($base));
        return date('Y-m-d', strtotime($base . ' +' . (($dia - $dow + 7) % 7) . ' days'));
    }

    public static function entrenadoresActivos(): array
    {
        return Database::all('SELECT id, nombres, apellidos, especialidad FROM entrenadores WHERE estado = 1 ORDER BY nombres');
    }

    public static function catalogo(): array
    {
        return Database::all(
            "SELECT c.*, e.nombres ent_nombres, e.apellidos ent_apellidos,
                    (SELECT COUNT(*) FROM horarios_clase h WHERE h.clase_id = c.id) n_horarios
             FROM clases c LEFT JOIN entrenadores e ON e.id = c.entrenador_id ORDER BY c.estado DESC, c.nombre"
        );
    }

    /** Horarios semanales agrupados por día, con las reservas de su próxima fecha */
    public static function semana(): array
    {
        $rows = Database::all(
            "SELECT h.*, c.nombre, c.color, c.cupo, c.estado clase_estado, e.nombres ent_nombres, e.apellidos ent_apellidos
             FROM horarios_clase h JOIN clases c ON c.id = h.clase_id LEFT JOIN entrenadores e ON e.id = c.entrenador_id
             WHERE c.estado = 1 ORDER BY h.dia_semana, h.hora_inicio"
        );
        $res = array_column(Database::all(
            "SELECT horario_id, fecha, COUNT(*) n FROM reservas WHERE fecha >= ? AND estado <> 'cancelada' GROUP BY horario_id, fecha", [date('Y-m-d')]
        ), null, null);
        $map = [];
        foreach ($res as $r) $map[$r['horario_id'] . '|' . $r['fecha']] = (int)$r['n'];
        $out = array_fill_keys(array_keys(self::dias()), []);
        foreach ($rows as $h) {
            $h['proxima'] = self::proximaFecha((int)$h['dia_semana']);
            $h['reservas'] = $map[$h['id'] . '|' . $h['proxima']] ?? 0;
            $out[(int)$h['dia_semana']][] = $h;
        }
        return $out;
    }

    /** Devuelve el horario en conflicto (misma sala o mismo entrenador a la vez), si lo hay */
    public static function conflicto(int $claseId, int $dia, string $ini, string $fin, string $sala, int $exceptId = 0): ?array
    {
        return Database::row(
            "SELECT h.*, c.nombre FROM horarios_clase h JOIN clases c ON c.id = h.clase_id
             WHERE h.dia_semana = ? AND h.id <> ? AND h.hora_inicio < ? AND h.hora_fin > ?
               AND ( (? <> '' AND h.sala = ?)
                  OR (c.entrenador_id IS NOT NULL AND c.entrenador_id = (SELECT entrenador_id FROM clases WHERE id = ?)) )
             LIMIT 1",
            [$dia, $exceptId, $fin, $ini, $sala, $sala, $claseId]
        );
    }

    public static function horario(int $id): ?array
    {
        return Database::row(
            "SELECT h.*, c.nombre, c.color, c.cupo, c.descripcion, e.nombres ent_nombres, e.apellidos ent_apellidos
             FROM horarios_clase h JOIN clases c ON c.id = h.clase_id LEFT JOIN entrenadores e ON e.id = c.entrenador_id WHERE h.id = ?", [$id]
        );
    }

    public static function reservas(int $horarioId, string $fecha): array
    {
        return Database::all(
            "SELECT r.*, s.codigo, s.nombres, s.apellidos, s.telefono FROM reservas r JOIN socios s ON s.id = r.socio_id
             WHERE r.horario_id = ? AND r.fecha = ? ORDER BY FIELD(r.estado,'asistio','reservada','cancelada'), r.id", [$horarioId, $fecha]
        );
    }

    public static function membresiaEn(int $socioId, string $fecha): ?array
    {
        return Database::row(
            "SELECT m.*, p.nombre plan FROM membresias m JOIN planes p ON p.id = m.plan_id
             WHERE m.socio_id = ? AND m.estado <> 'cancelada' AND m.fecha_inicio <= ? AND m.fecha_fin >= ? ORDER BY m.fecha_fin DESC LIMIT 1",
            [$socioId, $fecha, $fecha]
        );
    }
}
