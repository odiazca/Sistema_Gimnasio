<?php
require_once ROOT_PATH . '/app/models/Pago.php';

/** Cada reporte devuelve ['kpis' => [[etiqueta, valor]], 'charts' => [...], 'tablas' => [titulo => ['cols' => [], 'rows' => [[...]]]]] */
class Reporte
{
    public const TABS = ['financiero' => ['Financiero', 'bi-cash-stack'], 'socios' => ['Socios', 'bi-people-fill'], 'asistencia' => ['Asistencia', 'bi-door-open-fill'], 'ventas' => ['Ventas', 'bi-bag-fill']];

    private static function meses(string $d, string $h): array
    {
        $o = []; $c = new DateTime(substr($d, 0, 7) . '-01'); $e = new DateTime(substr($h, 0, 7) . '-01');
        while ($c <= $e && count($o) < 36) { $o[] = $c->format('Y-m'); $c->modify('+1 month'); }
        return $o;
    }

    private static function etiqueta(string $ym): string
    {
        $m = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        return $m[(int)substr($ym, 5, 2) - 1] . ' ' . substr($ym, 2, 2);
    }

    private static function porMes(string $sql, array $p): array { return array_column(Database::all($sql, $p), 't', 'ym'); }

    public static function financiero(string $d, string $h): array
    {
        $mem = self::porMes("SELECT DATE_FORMAT(fecha_pago,'%Y-%m') ym, SUM(monto) t FROM pagos WHERE DATE(fecha_pago) BETWEEN ? AND ? GROUP BY ym", [$d, $h]);
        $tie = self::porMes("SELECT DATE_FORMAT(fecha,'%Y-%m') ym, SUM(total) t FROM ventas WHERE DATE(fecha) BETWEEN ? AND ? GROUP BY ym", [$d, $h]);
        $gas = self::porMes("SELECT DATE_FORMAT(fecha,'%Y-%m') ym, SUM(monto) t FROM gastos WHERE fecha BETWEEN ? AND ? GROUP BY ym", [$d, $h]);
        $ms = self::meses($d, $h); $lab = []; $a = []; $b = []; $c = []; $rows = [];
        foreach ($ms as $ym) {
            $x = (float)($mem[$ym] ?? 0); $y = (float)($tie[$ym] ?? 0); $z = (float)($gas[$ym] ?? 0);
            $lab[] = self::etiqueta($ym); $a[] = $x; $b[] = $y; $c[] = $z; $rows[] = [self::etiqueta($ym), $x, $y, $x + $y, $z, $x + $y - $z];
        }
        $ing = array_sum($a) + array_sum($b); $g = array_sum($c);
        $npag = (int)Database::val('SELECT COUNT(*) FROM pagos WHERE DATE(fecha_pago) BETWEEN ? AND ?', [$d, $h]);
        $plan = Database::all("SELECT COALESCE(p.nombre,'Otros') plan, COUNT(*) n, SUM(pg.monto) t FROM pagos pg LEFT JOIN membresias m ON m.id = pg.membresia_id LEFT JOIN planes p ON p.id = m.plan_id
            WHERE DATE(pg.fecha_pago) BETWEEN ? AND ? GROUP BY plan ORDER BY t DESC", [$d, $h]);
        $met = Database::all("SELECT metodo, SUM(t) t FROM (SELECT metodo, monto t FROM pagos WHERE DATE(fecha_pago) BETWEEN ? AND ? UNION ALL SELECT metodo, total t FROM ventas WHERE DATE(fecha) BETWEEN ? AND ?) u GROUP BY metodo ORDER BY t DESC", [$d, $h, $d, $h]);
        $cat = Database::all("SELECT COALESCE(categoria,'Sin categoría') c, SUM(monto) t FROM gastos WHERE fecha BETWEEN ? AND ? GROUP BY c ORDER BY t DESC", [$d, $h]);
        $mn = Pago::metodos();
        return [
            'kpis' => [['Ingresos', money($ing)], ['Gastos', money($g)], [$ing >= $g ? 'Utilidad' : 'Pérdida', money(abs($ing - $g))], ['Ticket prom. membresía', money($npag ? array_sum($a) / $npag : 0)]],
            'charts' => [['id' => 'cFin', 'type' => 'bar', 'labels' => $lab, 'series' => [['Membresías', $a, '#ff5a1f'], ['Tienda', $b, '#6366f1'], ['Gastos', $c, '#ef4444']]],
                         ['id' => 'cMet', 'type' => 'doughnut', 'labels' => array_map(fn($r) => $mn[$r['metodo']] ?? $r['metodo'], $met), 'series' => [['Ingresos', array_map(fn($r) => (float)$r['t'], $met), null]]]],
            'tablas' => [
                'Resultado mensual' => ['cols' => ['Mes', 'Membresías', 'Tienda', 'Ingresos', 'Gastos', 'Utilidad'], 'rows' => $rows, 'money' => [1, 2, 3, 4, 5]],
                'Ingresos por plan' => ['cols' => ['Plan', 'Pagos', 'Monto'], 'rows' => array_map(fn($r) => [$r['plan'], (int)$r['n'], (float)$r['t']], $plan), 'money' => [2]],
                'Ingresos por método' => ['cols' => ['Método', 'Monto'], 'rows' => array_map(fn($r) => [$mn[$r['metodo']] ?? $r['metodo'], (float)$r['t']], $met), 'money' => [1]],
                'Gastos por categoría' => ['cols' => ['Categoría', 'Monto'], 'rows' => array_map(fn($r) => [$r['c'], (float)$r['t']], $cat), 'money' => [1]],
            ],
        ];
    }

    public static function socios(string $d, string $h): array
    {
        $hoy = date('Y-m-d');
        $altas = self::porMes("SELECT DATE_FORMAT(fecha_registro,'%Y-%m') ym, COUNT(*) t FROM socios WHERE fecha_registro BETWEEN ? AND ? GROUP BY ym", [$d, $h]);
        $ms = self::meses($d, $h); $lab = array_map([self::class, 'etiqueta'], $ms); $ser = array_map(fn($m) => (int)($altas[$m] ?? 0), $ms);
        $activos = (int)Database::val("SELECT COUNT(DISTINCT socio_id) FROM membresias WHERE estado <> 'cancelada' AND fecha_inicio <= ? AND fecha_fin >= ?", [$hoy, $hoy]);
        $ven = Database::row("SELECT COUNT(*) v, COALESCE(SUM(EXISTS(SELECT 1 FROM membresias m2 WHERE m2.socio_id = m.socio_id AND m2.id <> m.id AND m2.estado <> 'cancelada' AND m2.fecha_inicio > m.fecha_inicio
                AND m2.fecha_inicio <= DATE_ADD(m.fecha_fin, INTERVAL 15 DAY))),0) r FROM membresias m WHERE m.estado <> 'cancelada' AND m.fecha_fin BETWEEN ? AND ? AND m.fecha_fin < ?", [$d, $h, $hoy]);
        $v = (int)$ven['v']; $r = (int)$ven['r']; $ret = $v ? round($r * 100 / $v, 1) : null;
        $gen = Database::all("SELECT genero g, COUNT(*) n FROM socios WHERE estado = 'activo' GROUP BY genero");
        $gl = ['M' => 'Hombres', 'F' => 'Mujeres', 'O' => 'Otro'];
        $edad = Database::all("SELECT CASE WHEN e IS NULL THEN 'Sin dato' WHEN e < 18 THEN 'Menos de 18' WHEN e < 26 THEN '18-25' WHEN e < 36 THEN '26-35' WHEN e < 46 THEN '36-45' WHEN e < 60 THEN '46-59' ELSE '60+' END r, COUNT(*) n
            FROM (SELECT TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) e FROM socios WHERE estado = 'activo') x GROUP BY r");
        $ord = ['Menos de 18', '18-25', '26-35', '36-45', '46-59', '60+', 'Sin dato']; $em = array_column($edad, 'n', 'r');
        $plan = Database::all("SELECT p.nombre, COUNT(*) n FROM membresias m JOIN planes p ON p.id = m.plan_id WHERE m.estado <> 'cancelada' AND m.fecha_inicio <= ? AND m.fecha_fin >= ? GROUP BY p.id, p.nombre ORDER BY n DESC", [$hoy, $hoy]);
        return [
            'kpis' => [['Altas en el periodo', array_sum($ser)], ['Socios con plan vigente', $activos], ['Retención', $ret === null ? '—' : $ret . ' %'], ['Membresías vencidas / renovadas', $v . ' / ' . $r]],
            'charts' => [['id' => 'cAltas', 'type' => 'bar', 'labels' => $lab, 'series' => [['Altas', $ser, '#10b981']]],
                         ['id' => 'cEdad', 'type' => 'doughnut', 'labels' => $ord, 'series' => [['Socios', array_map(fn($k) => (int)($em[$k] ?? 0), $ord), null]]]],
            'tablas' => [
                'Altas por mes' => ['cols' => ['Mes', 'Altas'], 'rows' => array_map(null, $lab, $ser)],
                'Planes vigentes' => ['cols' => ['Plan', 'Socios'], 'rows' => array_map(fn($x) => [$x['nombre'], (int)$x['n']], $plan)],
                'Socios activos por género' => ['cols' => ['Género', 'Socios'], 'rows' => array_map(fn($x) => [$gl[$x['g']] ?? $x['g'], (int)$x['n']], $gen)],
                'Socios activos por edad' => ['cols' => ['Rango', 'Socios'], 'rows' => array_map(fn($k) => [$k, (int)($em[$k] ?? 0)], $ord)],
            ],
            'nota' => 'Retención: de las membresías que vencieron en el periodo, el porcentaje que se renovó dentro de los 15 días posteriores.',
        ];
    }

    public static function asistencia(string $d, string $h): array
    {
        $tot = (int)Database::val('SELECT COUNT(*) FROM asistencias WHERE DATE(ingreso) BETWEEN ? AND ?', [$d, $h]);
        $dias = max(1, (int)((strtotime(min($h, date('Y-m-d'))) - strtotime($d)) / 86400) + 1);
        $uni = (int)Database::val('SELECT COUNT(DISTINCT socio_id) FROM asistencias WHERE DATE(ingreso) BETWEEN ? AND ?', [$d, $h]);
        $dw = array_column(Database::all('SELECT WEEKDAY(ingreso) w, COUNT(*) n FROM asistencias WHERE DATE(ingreso) BETWEEN ? AND ? GROUP BY w', [$d, $h]), 'n', 'w');
        $hr = array_column(Database::all('SELECT HOUR(ingreso) hh, COUNT(*) n FROM asistencias WHERE DATE(ingreso) BETWEEN ? AND ? GROUP BY hh', [$d, $h]), 'n', 'hh');
        $nd = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo']; $sd = []; foreach (array_keys($nd) as $i) $sd[] = (int)($dw[$i] ?? 0);
        $hl = []; $hs = []; for ($i = 5; $i <= 22; $i++) { $hl[] = sprintf('%02d:00', $i); $hs[] = (int)($hr[$i] ?? 0); }
        $top = Database::all('SELECT s.codigo, s.nombres, s.apellidos, COUNT(*) n FROM asistencias a JOIN socios s ON s.id = a.socio_id WHERE DATE(a.ingreso) BETWEEN ? AND ? GROUP BY s.id, s.codigo, s.nombres, s.apellidos ORDER BY n DESC, s.nombres LIMIT 10', [$d, $h]);
        $pico = $tot ? $hl[array_search(max($hs), $hs)] : '—';
        $met = Database::all('SELECT metodo, COUNT(*) n FROM asistencias WHERE DATE(ingreso) BETWEEN ? AND ? GROUP BY metodo ORDER BY n DESC', [$d, $h]);
        return [
            'kpis' => [['Ingresos totales', $tot], ['Promedio diario', round($tot / $dias, 1)], ['Socios distintos', $uni], ['Hora pico', $pico]],
            'charts' => [['id' => 'cDia', 'type' => 'bar', 'labels' => $nd, 'series' => [['Ingresos', $sd, '#6366f1']]], ['id' => 'cHora', 'type' => 'line', 'labels' => $hl, 'series' => [['Ingresos', $hs, '#ff5a1f']]]],
            'tablas' => [
                'Ingresos por día de la semana' => ['cols' => ['Día', 'Ingresos'], 'rows' => array_map(null, $nd, $sd)],
                'Ingresos por hora' => ['cols' => ['Hora', 'Ingresos'], 'rows' => array_map(null, $hl, $hs)],
                'Socios más frecuentes' => ['cols' => ['Código', 'Socio', 'Visitas'], 'rows' => array_map(fn($x) => [$x['codigo'], $x['nombres'] . ' ' . $x['apellidos'], (int)$x['n']], $top)],
                'Por método de registro' => ['cols' => ['Método', 'Ingresos'], 'rows' => array_map(fn($x) => [strtoupper($x['metodo']), (int)$x['n']], $met)],
            ],
        ];
    }

    public static function ventas(string $d, string $h): array
    {
        $t = Database::row('SELECT COUNT(*) n, COALESCE(SUM(total),0) t FROM ventas WHERE DATE(fecha) BETWEEN ? AND ?', [$d, $h]);
        $util = (float)Database::val('SELECT COALESCE(SUM(dt.cantidad * (dt.precio - p.precio_compra)),0) FROM venta_detalle dt JOIN ventas v ON v.id = dt.venta_id JOIN productos p ON p.id = dt.producto_id WHERE DATE(v.fecha) BETWEEN ? AND ?', [$d, $h]);
        $top = Database::all('SELECT p.codigo, p.nombre, SUM(dt.cantidad) u, SUM(dt.cantidad * dt.precio) t, SUM(dt.cantidad * (dt.precio - p.precio_compra)) ut FROM venta_detalle dt JOIN ventas v ON v.id = dt.venta_id JOIN productos p ON p.id = dt.producto_id
            WHERE DATE(v.fecha) BETWEEN ? AND ? GROUP BY p.id, p.codigo, p.nombre ORDER BY u DESC LIMIT 15', [$d, $h]);
        $cat = Database::all("SELECT COALESCE(p.categoria,'Sin categoría') c, SUM(dt.cantidad) u, SUM(dt.cantidad * dt.precio) t FROM venta_detalle dt JOIN ventas v ON v.id = dt.venta_id JOIN productos p ON p.id = dt.producto_id
            WHERE DATE(v.fecha) BETWEEN ? AND ? GROUP BY c ORDER BY t DESC", [$d, $h]);
        $mes = self::porMes("SELECT DATE_FORMAT(fecha,'%Y-%m') ym, SUM(total) t FROM ventas WHERE DATE(fecha) BETWEEN ? AND ? GROUP BY ym", [$d, $h]);
        $ms = self::meses($d, $h); $mn = Pago::metodos();
        $met = Database::all('SELECT metodo, COUNT(*) n, SUM(total) t FROM ventas WHERE DATE(fecha) BETWEEN ? AND ? GROUP BY metodo ORDER BY t DESC', [$d, $h]);
        return [
            'kpis' => [['Ventas', money($t['t'])], ['Transacciones', (int)$t['n']], ['Ticket promedio', money($t['n'] ? $t['t'] / $t['n'] : 0)], ['Utilidad bruta', money($util)]],
            'charts' => [['id' => 'cVen', 'type' => 'bar', 'labels' => array_map([self::class, 'etiqueta'], $ms), 'series' => [['Ventas', array_map(fn($m) => (float)($mes[$m] ?? 0), $ms), '#ec4899']]],
                         ['id' => 'cCat', 'type' => 'doughnut', 'labels' => array_column($cat, 'c'), 'series' => [['Ventas', array_map(fn($x) => (float)$x['t'], $cat), null]]]],
            'tablas' => [
                'Productos más vendidos' => ['cols' => ['Código', 'Producto', 'Unidades', 'Ventas', 'Utilidad'], 'rows' => array_map(fn($x) => [$x['codigo'], $x['nombre'], (int)$x['u'], (float)$x['t'], (float)$x['ut']], $top), 'money' => [3, 4]],
                'Ventas por categoría' => ['cols' => ['Categoría', 'Unidades', 'Ventas'], 'rows' => array_map(fn($x) => [$x['c'], (int)$x['u'], (float)$x['t']], $cat), 'money' => [2]],
                'Ventas por método' => ['cols' => ['Método', 'Transacciones', 'Ventas'], 'rows' => array_map(fn($x) => [$mn[$x['metodo']] ?? $x['metodo'], (int)$x['n'], (float)$x['t']], $met), 'money' => [2]],
            ],
        ];
    }

    public static function generar(string $tab, string $d, string $h): array
    {
        return match ($tab) { 'socios' => self::socios($d, $h), 'asistencia' => self::asistencia($d, $h), 'ventas' => self::ventas($d, $h), default => self::financiero($d, $h) };
    }
}
