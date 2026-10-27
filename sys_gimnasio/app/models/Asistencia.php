<?php
class Asistencia
{
    /** Horas máximas que se considera a un socio "dentro" si no se marcó salida */
    public const HORAS_MAX = 4;

    private static function limiteDentro(): string
    {
        return date('Y-m-d H:i:s', strtotime('-' . self::HORAS_MAX . ' hours'));
    }

    public static function aforoMax(): int
    {
        return (int)Database::val("SELECT valor FROM configuracion WHERE clave = 'aforo_maximo'") ?: 100;
    }

    public static function dentroCount(): int
    {
        return (int)Database::val('SELECT COUNT(*) FROM asistencias WHERE salida IS NULL AND ingreso >= ?', [self::limiteDentro()]);
    }

    public static function dentro(): array
    {
        return Database::all(
            'SELECT a.id, a.ingreso, a.metodo, s.id socio_id, s.codigo, s.nombres, s.apellidos, s.foto
             FROM asistencias a JOIN socios s ON s.id = a.socio_id
             WHERE a.salida IS NULL AND a.ingreso >= ? ORDER BY a.ingreso DESC', [self::limiteDentro()]
        );
    }

    public static function recientes(int $n = 12): array
    {
        return Database::all(
            'SELECT a.id, a.ingreso, a.salida, a.metodo, s.codigo, s.nombres, s.apellidos
             FROM asistencias a JOIN socios s ON s.id = a.socio_id
             WHERE a.ingreso >= ? ORDER BY GREATEST(a.ingreso, COALESCE(a.salida, a.ingreso)) DESC LIMIT ' . (int)$n,
            [date('Y-m-d')]
        );
    }

    public static function hoy(): array
    {
        $r = Database::row(
            'SELECT COUNT(*) ingresos, COUNT(DISTINCT socio_id) unicos, SUM(salida IS NOT NULL) salidas
             FROM asistencias WHERE ingreso >= ? AND ingreso < ?', [date('Y-m-d'), date('Y-m-d', strtotime('+1 day'))]
        ) ?: [];
        return ['ingresos' => (int)($r['ingresos'] ?? 0), 'unicos' => (int)($r['unicos'] ?? 0), 'salidas' => (int)($r['salidas'] ?? 0)];
    }

    /** Busca un socio por código (SOC-0001) o documento exacto */
    public static function buscarSocio(string $cod): ?array
    {
        $cod = trim($cod);
        if ($cod === '') return null;
        if (ctype_digit($cod) && strlen($cod) < 6) $cod = sprintf('SOC-%04d', (int)$cod); // "12" => SOC-0012
        return Database::row('SELECT * FROM socios WHERE codigo = ? OR dni = ? LIMIT 1', [$cod, $cod]);
    }

    /** Membresía vigente hoy (la de mayor fecha de fin) */
    public static function membresiaVigente(int $socioId): ?array
    {
        $hoy = date('Y-m-d');
        return Database::row(
            "SELECT m.*, p.nombre plan, p.color,
                    (m.precio - m.descuento) - (SELECT COALESCE(SUM(pg.monto),0) FROM pagos pg WHERE pg.membresia_id = m.id) saldo
             FROM membresias m JOIN planes p ON p.id = m.plan_id
             WHERE m.socio_id = ? AND m.estado NOT IN ('cancelada','congelada') AND m.fecha_inicio <= ? AND m.fecha_fin >= ?
             ORDER BY m.fecha_fin DESC, m.id DESC LIMIT 1", [$socioId, $hoy, $hoy]
        );
    }

    public static function ultimaMembresia(int $socioId): ?array
    {
        return Database::row(
            "SELECT m.*, p.nombre plan FROM membresias m JOIN planes p ON p.id = m.plan_id
             WHERE m.socio_id = ? AND m.estado <> 'cancelada' ORDER BY m.fecha_fin DESC, m.id DESC LIMIT 1", [$socioId]
        );
    }

    /**
     * Procesa un escaneo / digitación: registra ingreso o salida según corresponda.
     * @return array resultado listo para JSON
     */
    public static function procesar(string $codigo, string $metodo): array
    {
        $s = self::buscarSocio($codigo);
        if (!$s) {
            return ['ok' => false, 'tipo' => 'noexiste', 'titulo' => 'Socio no encontrado',
                    'mensaje' => 'No existe un socio con el código o documento "' . mb_substr($codigo, 0, 30) . '".'];
        }
        $socio = [
            'id' => (int)$s['id'], 'codigo' => $s['codigo'], 'nombre' => $s['nombres'] . ' ' . $s['apellidos'],
            'ini' => iniciales($s['nombres'], $s['apellidos']), 'foto' => $s['foto'] ? foto_url($s['foto']) : null,
            'telefono' => $s['telefono'],
        ];
        $now = date('Y-m-d H:i:s');

        // ¿Tiene un ingreso abierto? -> salida (o doble escaneo)
        $abierta = Database::row('SELECT * FROM asistencias WHERE socio_id = ? AND salida IS NULL AND ingreso >= ? ORDER BY ingreso DESC LIMIT 1',
            [$s['id'], self::limiteDentro()]);
        if ($abierta) {
            $seg = time() - strtotime($abierta['ingreso']);
            if ($seg < 120) {
                return ['ok' => true, 'tipo' => 'duplicado', 'titulo' => 'Ya registrado', 'socio' => $socio,
                        'mensaje' => 'El ingreso de ' . $s['nombres'] . ' se registró hace instantes (' . date('H:i', strtotime($abierta['ingreso'])) . ').'];
            }
            Database::exec('UPDATE asistencias SET salida = ? WHERE id = ?', [$now, $abierta['id']]);
            $min = (int)round($seg / 60);
            return ['ok' => true, 'tipo' => 'salida', 'titulo' => '¡Hasta pronto, ' . explode(' ', $s['nombres'])[0] . '!', 'socio' => $socio,
                    'mensaje' => 'Salida registrada a las ' . date('H:i') . '. Tiempo de entrenamiento: ' . ($min >= 60 ? intdiv($min, 60) . ' h ' . ($min % 60) . ' min' : $min . ' min') . '.'];
        }

        // Validaciones de ingreso
        if ($s['estado'] !== 'activo') {
            return ['ok' => false, 'tipo' => 'bloqueado', 'titulo' => 'Acceso denegado', 'socio' => $socio,
                    'mensaje' => 'El socio está ' . $s['estado'] . '. Consulta con administración.'];
        }
        $m = self::membresiaVigente((int)$s['id']);
        if (!$m) {
            $u = self::ultimaMembresia((int)$s['id']);
            if (!$u) return ['ok' => false, 'tipo' => 'sinplan', 'titulo' => 'Acceso denegado', 'socio' => $socio, 'accion' => 'membresias/nueva?socio=' . $s['id'],
                             'mensaje' => 'El socio no tiene ninguna membresía registrada.'];
            if ($u['estado'] === 'congelada') return ['ok' => false, 'tipo' => 'congelada', 'titulo' => 'Membresía congelada', 'socio' => $socio, 'plan' => $u['plan'], 'accion' => 'socios/ver/' . $s['id'],
                             'mensaje' => 'Su plan ' . $u['plan'] . ' está congelado. Debe reactivarse en recepción para ingresar.'];
            if ($u['fecha_inicio'] > date('Y-m-d')) return ['ok' => false, 'tipo' => 'programada', 'titulo' => 'Aún no vigente', 'socio' => $socio, 'accion' => 'socios/ver/' . $s['id'],
                             'mensaje' => 'Su membresía ' . $u['plan'] . ' inicia el ' . fdate($u['fecha_inicio']) . '.'];
            return ['ok' => false, 'tipo' => 'vencida', 'titulo' => 'Membresía vencida', 'socio' => $socio, 'plan' => $u['plan'], 'accion' => 'membresias/nueva?socio=' . $s['id'],
                    'mensaje' => 'Su plan ' . $u['plan'] . ' venció el ' . fdate($u['fecha_fin']) . ' (hace ' . abs(dias_restantes($u['fecha_fin'])) . ' días). Debe renovar.'];
        }

        Database::exec('INSERT INTO asistencias (socio_id, ingreso, metodo) VALUES (?,?,?)', [$s['id'], $now, $metodo]);
        $dias = dias_restantes($m['fecha_fin']);
        $avisos = [];
        if ($dias <= 7) $avisos[] = $dias === 0 ? 'Su membresía vence HOY.' : 'Su membresía vence en ' . $dias . ' día' . ($dias > 1 ? 's' : '') . '.';
        if ((float)$m['saldo'] > 0.009) $avisos[] = 'Tiene un saldo pendiente de ' . money($m['saldo']) . '.';
        $dentro = self::dentroCount(); $max = self::aforoMax();
        if ($dentro > $max) $avisos[] = 'Aforo máximo superado (' . $dentro . '/' . $max . ').';
        $visitas = (int)Database::val('SELECT COUNT(*) FROM asistencias WHERE socio_id = ? AND ingreso >= ?', [$s['id'], date('Y-m-01')]);
        return [
            'ok' => true, 'tipo' => $avisos ? 'aviso' : 'ingreso', 'titulo' => '¡Bienvenido, ' . explode(' ', $s['nombres'])[0] . '!', 'socio' => $socio,
            'mensaje' => 'Ingreso registrado a las ' . date('H:i') . '. Visita n.° ' . $visitas . ' de este mes.',
            'plan' => $m['plan'], 'color' => $m['color'], 'vence' => fdate($m['fecha_fin']), 'dias' => $dias,
            'avisos' => $avisos, 'accion' => ((float)$m['saldo'] > 0.009) ? 'pagos/nuevo?socio=' . $s['id'] . '&membresia=' . $m['id'] : ($dias <= 7 ? 'membresias/nueva?socio=' . $s['id'] : null),
        ];
    }

    // ---------- historial ----------
    private static function where(array $f, array &$p): string
    {
        $w = ['a.ingreso >= ? AND a.ingreso < ?'];
        $p[] = $f['desde']; $p[] = date('Y-m-d', strtotime($f['hasta'] . ' +1 day'));
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
        return (int)Database::val('SELECT COUNT(*) FROM asistencias a JOIN socios s ON s.id = a.socio_id WHERE ' . $w, $p);
    }

    public static function listar(array $f, int $limit, int $offset): array
    {
        $p = []; $w = self::where($f, $p);
        return Database::all(
            'SELECT a.*, s.codigo, s.nombres, s.apellidos FROM asistencias a JOIN socios s ON s.id = a.socio_id
             WHERE ' . $w . ' ORDER BY a.ingreso DESC, a.id DESC LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset, $p
        );
    }

    public static function todos(array $f, int $max = 20000): array
    {
        $p = []; $w = self::where($f, $p);
        return Database::all(
            'SELECT a.ingreso, a.salida, a.metodo, s.codigo, s.dni, s.nombres, s.apellidos FROM asistencias a JOIN socios s ON s.id = a.socio_id
             WHERE ' . $w . ' ORDER BY a.ingreso DESC LIMIT ' . (int)$max, $p
        );
    }

    public static function resumen(array $f): array
    {
        $p = []; $w = self::where($f, $p);
        $base = Database::row('SELECT COUNT(*) total, COUNT(DISTINCT a.socio_id) unicos FROM asistencias a JOIN socios s ON s.id = a.socio_id WHERE ' . $w, $p) ?: [];
        $p = []; $w = self::where($f, $p);
        $dias = Database::all("SELECT DATE(a.ingreso) d, COUNT(*) c FROM asistencias a JOIN socios s ON s.id = a.socio_id WHERE $w GROUP BY d ORDER BY d", $p);
        $p = []; $w = self::where($f, $p);
        $horas = Database::all("SELECT HOUR(a.ingreso) h, COUNT(*) c FROM asistencias a JOIN socios s ON s.id = a.socio_id WHERE $w GROUP BY h ORDER BY c DESC", $p);
        $nd = max(1, (int)(new DateTime($f['desde']))->diff(new DateTime($f['hasta']))->days + 1);
        $porDia = array_column($dias, 'c', 'd');
        $serie = ['labels' => [], 'data' => []];
        for ($i = 0; $i < $nd && $i < 92; $i++) {
            $d = date('Y-m-d', strtotime($f['desde'] . " +$i days"));
            $serie['labels'][] = date('d/m', strtotime($d)); $serie['data'][] = (int)($porDia[$d] ?? 0);
        }
        return [
            'total' => (int)($base['total'] ?? 0), 'unicos' => (int)($base['unicos'] ?? 0),
            'promedio' => round(($base['total'] ?? 0) / $nd, 1), 'hora_pico' => $horas ? sprintf('%02d:00', $horas[0]['h']) : '—', 'serie' => $serie,
        ];
    }
}
