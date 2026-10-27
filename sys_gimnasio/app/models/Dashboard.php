<?php
class Dashboard
{
    private static function meses(int $n): array
    {
        $out = [];
        $base = new DateTime('first day of this month');
        for ($i = $n - 1; $i >= 0; $i--) {
            $d = (clone $base)->modify("-{$i} months");
            $out[$d->format('Y-m')] = $d;
        }
        return $out;
    }

    private static function mesLabel(DateTime $d): string
    {
        static $m = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        return $m[(int)$d->format('n') - 1];
    }

    public static function kpis(): array
    {
        $hoy = date('Y-m-d');
        $inicioMes = date('Y-m-01');
        $inicioMesAnt = date('Y-m-01', strtotime('first day of last month'));
        $mismoDiaAnt = date('Y-m-d', strtotime($inicioMesAnt . ' +' . ((int)date('j') - 1) . ' days'));
        $en7 = date('Y-m-d', strtotime('+7 days'));
        $hace2h = date('Y-m-d H:i:s', strtotime('-2 hours'));

        $activos = (int)Database::val('SELECT COUNT(DISTINCT socio_id) FROM membresias WHERE fecha_inicio <= ? AND fecha_fin >= ? AND estado <> \'cancelada\'', [$hoy, $hoy]);
        $activosAnt = (int)Database::val('SELECT COUNT(DISTINCT socio_id) FROM membresias WHERE fecha_inicio <= ? AND fecha_fin >= ? AND estado <> \'cancelada\'', [date('Y-m-d', strtotime('-30 days')), date('Y-m-d', strtotime('-30 days'))]);

        $ingMem = (float)Database::val('SELECT COALESCE(SUM(monto),0) FROM pagos WHERE fecha_pago >= ? AND fecha_pago < ?', [$inicioMes, date('Y-m-d', strtotime('+1 day'))]);
        $ingTienda = (float)Database::val('SELECT COALESCE(SUM(total),0) FROM ventas WHERE fecha >= ? AND fecha < ?', [$inicioMes, date('Y-m-d', strtotime('+1 day'))]);
        $ingAntMem = (float)Database::val('SELECT COALESCE(SUM(monto),0) FROM pagos WHERE fecha_pago >= ? AND fecha_pago < ?', [$inicioMesAnt, date('Y-m-d', strtotime($mismoDiaAnt . ' +1 day'))]);
        $ingAntTienda = (float)Database::val('SELECT COALESCE(SUM(total),0) FROM ventas WHERE fecha >= ? AND fecha < ?', [$inicioMesAnt, date('Y-m-d', strtotime($mismoDiaAnt . ' +1 day'))]);
        $gastosMes = (float)Database::val('SELECT COALESCE(SUM(monto),0) FROM gastos WHERE fecha >= ? AND fecha <= ?', [$inicioMes, $hoy]);

        $asisHoy = (int)Database::val('SELECT COUNT(*) FROM asistencias WHERE ingreso >= ? AND ingreso < ?', [$hoy, date('Y-m-d', strtotime('+1 day'))]);
        $asisAyer = (int)Database::val('SELECT COUNT(*) FROM asistencias WHERE ingreso >= ? AND ingreso < ?', [date('Y-m-d', strtotime('-1 day')), $hoy]);

        $porVencer = (int)Database::val(
            'SELECT COUNT(*) FROM (SELECT socio_id, MAX(fecha_fin) f FROM membresias WHERE estado <> \'cancelada\' AND socio_id NOT IN (SELECT socio_id FROM membresias WHERE estado = \'congelada\') GROUP BY socio_id) t WHERE t.f >= ? AND t.f <= ?',
            [$hoy, $en7]
        );
        $vencidosRecientes = (int)Database::val(
            'SELECT COUNT(*) FROM (SELECT socio_id, MAX(fecha_fin) f FROM membresias WHERE estado <> \'cancelada\' AND socio_id NOT IN (SELECT socio_id FROM membresias WHERE estado = \'congelada\') GROUP BY socio_id) t WHERE t.f < ? AND t.f >= ?',
            [$hoy, date('Y-m-d', strtotime('-30 days'))]
        );

        $nuevosMes = (int)Database::val('SELECT COUNT(*) FROM socios WHERE fecha_registro >= ?', [$inicioMes]);
        $nuevosAnt = (int)Database::val('SELECT COUNT(*) FROM socios WHERE fecha_registro >= ? AND fecha_registro < ?', [$inicioMesAnt, $inicioMes]);
        $stockBajo = (int)Database::val('SELECT COUNT(*) FROM productos WHERE estado = 1 AND stock <= stock_minimo');
        require_once ROOT_PATH . '/app/models/Asistencia.php';
        $enGym = Asistencia::dentroCount();
        $aforo = (int)Database::val("SELECT valor FROM configuracion WHERE clave = 'aforo_maximo'") ?: 100;

        $ingTotal = $ingMem + $ingTienda;
        $ingAntTotal = $ingAntMem + $ingAntTienda;

        return [
            'activos' => $activos, 'activos_delta' => self::delta($activos, $activosAnt),
            'ingresos' => $ingTotal, 'ing_membresias' => $ingMem, 'ing_tienda' => $ingTienda,
            'ingresos_delta' => self::delta($ingTotal, $ingAntTotal),
            'gastos' => $gastosMes, 'utilidad' => $ingTotal - $gastosMes,
            'asis_hoy' => $asisHoy, 'asis_delta' => self::delta($asisHoy, $asisAyer),
            'por_vencer' => $porVencer, 'vencidos' => $vencidosRecientes,
            'nuevos' => $nuevosMes, 'nuevos_delta' => self::delta($nuevosMes, $nuevosAnt),
            'stock_bajo' => $stockBajo, 'en_gym' => $enGym, 'aforo' => $aforo,
        ];
    }

    private static function delta($now, $prev): ?float
    {
        if ($prev <= 0) return null;
        return round((($now - $prev) / $prev) * 100, 1);
    }

    /** Ingresos y gastos por mes (12 meses) */
    public static function finanzas(): array
    {
        $meses = self::meses(12);
        $desde = reset($meses)->format('Y-m-01');
        $pag = Database::all("SELECT DATE_FORMAT(fecha_pago,'%Y-%m') ym, SUM(monto) t FROM pagos WHERE fecha_pago >= ? GROUP BY ym", [$desde]);
        $ven = Database::all("SELECT DATE_FORMAT(fecha,'%Y-%m') ym, SUM(total) t FROM ventas WHERE fecha >= ? GROUP BY ym", [$desde]);
        $gas = Database::all("SELECT DATE_FORMAT(fecha,'%Y-%m') ym, SUM(monto) t FROM gastos WHERE fecha >= ? GROUP BY ym", [$desde]);
        $map = fn($rows) => array_column($rows, 't', 'ym');
        $p = $map($pag); $v = $map($ven); $g = $map($gas);
        $o = ['labels' => [], 'membresias' => [], 'tienda' => [], 'gastos' => []];
        foreach ($meses as $ym => $d) {
            $o['labels'][] = self::mesLabel($d);
            $o['membresias'][] = round((float)($p[$ym] ?? 0), 2);
            $o['tienda'][] = round((float)($v[$ym] ?? 0), 2);
            $o['gastos'][] = round((float)($g[$ym] ?? 0), 2);
        }
        return $o;
    }

    /** Asistencias últimos 14 días */
    public static function asistenciaDiaria(): array
    {
        $dias = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
        $desde = date('Y-m-d', strtotime('-13 days'));
        $rows = array_column(Database::all("SELECT DATE(ingreso) d, COUNT(*) c FROM asistencias WHERE ingreso >= ? GROUP BY d", [$desde]), 'c', 'd');
        $o = ['labels' => [], 'data' => []];
        for ($i = 13; $i >= 0; $i--) {
            $d = strtotime("-{$i} days");
            $o['labels'][] = $dias[(int)date('w', $d)] . ' ' . date('d', $d);
            $o['data'][] = (int)($rows[date('Y-m-d', $d)] ?? 0);
        }
        return $o;
    }

    /** Afluencia promedio por hora (últimos 30 días) */
    public static function afluenciaHoras(): array
    {
        $desde = date('Y-m-d', strtotime('-30 days'));
        $rows = array_column(Database::all('SELECT HOUR(ingreso) h, COUNT(*) c FROM asistencias WHERE ingreso >= ? GROUP BY h', [$desde]), 'c', 'h');
        $o = ['labels' => [], 'data' => []];
        for ($h = 5; $h <= 22; $h++) {
            $o['labels'][] = sprintf('%02d h', $h);
            $o['data'][] = (int)round(($rows[$h] ?? 0) / 30);
        }
        return $o;
    }

    public static function distribucionPlanes(): array
    {
        $hoy = date('Y-m-d');
        $rows = Database::all(
            'SELECT p.nombre, p.color, COUNT(*) c FROM membresias m JOIN planes p ON p.id = m.plan_id
             WHERE m.fecha_inicio <= ? AND m.fecha_fin >= ? AND m.estado <> \'cancelada\' GROUP BY p.id, p.nombre, p.color ORDER BY c DESC',
            [$hoy, $hoy]
        );
        return [
            'labels' => array_column($rows, 'nombre'),
            'data'   => array_map('intval', array_column($rows, 'c')),
            'colors' => array_column($rows, 'color'),
        ];
    }

    /** Altas de socios por mes (últimos 8 meses) */
    public static function altas(): array
    {
        $meses = self::meses(8);
        $desde = reset($meses)->format('Y-m-01');
        $rows = array_column(Database::all("SELECT DATE_FORMAT(fecha_registro,'%Y-%m') ym, COUNT(*) c FROM socios WHERE fecha_registro >= ? GROUP BY ym", [$desde]), 'c', 'ym');
        $o = ['labels' => [], 'data' => []];
        foreach ($meses as $ym => $d) { $o['labels'][] = self::mesLabel($d); $o['data'][] = (int)($rows[$ym] ?? 0); }
        return $o;
    }

    /** Ingresos de los últimos 7 días (sparkline) */
    public static function spark(): array
    {
        $desde = date('Y-m-d', strtotime('-6 days'));
        $pag = array_column(Database::all('SELECT DATE(fecha_pago) d, SUM(monto) t FROM pagos WHERE fecha_pago >= ? GROUP BY d', [$desde]), 't', 'd');
        $ven = array_column(Database::all('SELECT DATE(fecha) d, SUM(total) t FROM ventas WHERE fecha >= ? GROUP BY d', [$desde]), 't', 'd');
        $asi = array_column(Database::all('SELECT DATE(ingreso) d, COUNT(*) t FROM asistencias WHERE ingreso >= ? GROUP BY d', [$desde]), 't', 'd');
        $soc = array_column(Database::all('SELECT fecha_registro d, COUNT(*) t FROM socios WHERE fecha_registro >= ? GROUP BY d', [$desde]), 't', 'd');
        $o = ['ingresos' => [], 'asistencias' => [], 'altas' => [], 'activos' => []];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $o['ingresos'][] = round((float)(($pag[$d] ?? 0) + ($ven[$d] ?? 0)), 2);
            $o['asistencias'][] = (int)($asi[$d] ?? 0);
            $o['altas'][] = (int)($soc[$d] ?? 0);
            $o['activos'][] = (int)Database::val('SELECT COUNT(DISTINCT socio_id) FROM membresias WHERE fecha_inicio <= ? AND fecha_fin >= ? AND estado <> \'cancelada\'', [$d, $d]);
        }
        return $o;
    }

    public static function proximosVencimientos(int $limit = 8): array
    {
        return Database::all(
            'SELECT s.id, s.codigo, s.nombres, s.apellidos, s.telefono, p.nombre plan, p.color, t.f fecha_fin
             FROM (SELECT socio_id, MAX(fecha_fin) f FROM membresias WHERE estado <> \'cancelada\' AND socio_id NOT IN (SELECT socio_id FROM membresias WHERE estado = \'congelada\') GROUP BY socio_id) t
             JOIN socios s ON s.id = t.socio_id
             JOIN membresias m ON m.id = (SELECT MAX(m2.id) FROM membresias m2 WHERE m2.socio_id = t.socio_id AND m2.fecha_fin = t.f AND m2.estado <> \'cancelada\')
             JOIN planes p ON p.id = m.plan_id
             WHERE t.f >= ? AND t.f <= ? AND s.estado = \'activo\'
             ORDER BY t.f ASC LIMIT ' . (int)$limit,
            [date('Y-m-d'), date('Y-m-d', strtotime('+7 days'))]
        );
    }

    public static function ultimosPagos(int $limit = 7): array
    {
        return Database::all(
            'SELECT pg.monto, pg.metodo, pg.concepto, pg.fecha_pago, s.nombres, s.apellidos
             FROM pagos pg JOIN socios s ON s.id = pg.socio_id ORDER BY pg.fecha_pago DESC LIMIT ' . (int)$limit
        );
    }

    public static function ultimosIngresos(int $limit = 6): array
    {
        return Database::all(
            'SELECT a.ingreso, a.salida, a.metodo, s.nombres, s.apellidos, s.codigo
             FROM asistencias a JOIN socios s ON s.id = a.socio_id ORDER BY a.ingreso DESC LIMIT ' . (int)$limit
        );
    }

    public static function clasesHoy(): array
    {
        $dow = (int)date('N');
        return Database::all(
            'SELECT h.id, h.hora_inicio, h.hora_fin, h.sala, c.nombre, c.color, c.cupo, e.nombres entrenador,
                    (SELECT COUNT(*) FROM reservas r WHERE r.horario_id = h.id AND r.fecha = ? AND r.estado <> \'cancelada\') reservas
             FROM horarios_clase h JOIN clases c ON c.id = h.clase_id LEFT JOIN entrenadores e ON e.id = c.entrenador_id
             WHERE h.dia_semana = ? ORDER BY h.hora_inicio',
            [date('Y-m-d'), $dow]
        );
    }

    public static function cumpleanos(int $limit = 5): array
    {
        $rows = Database::all(
            "SELECT nombres, apellidos, fecha_nacimiento, telefono,
                    DATEDIFF(
                      STR_TO_DATE(CONCAT(YEAR(?), '-', DATE_FORMAT(fecha_nacimiento, '%m-%d')), '%Y-%m-%d'), ?) dias
             FROM socios WHERE estado = 'activo' AND fecha_nacimiento IS NOT NULL
               AND DATE_FORMAT(fecha_nacimiento, '%m-%d') <> '02-29'
             HAVING dias BETWEEN 0 AND 7 ORDER BY dias LIMIT " . (int)$limit,
            [date('Y-m-d'), date('Y-m-d')]
        );
        return $rows;
    }

    public static function stockBajo(int $limit = 5): array
    {
        return Database::all('SELECT nombre, stock, stock_minimo FROM productos WHERE estado = 1 AND stock <= stock_minimo ORDER BY (stock - stock_minimo) LIMIT ' . (int)$limit);
    }
}
