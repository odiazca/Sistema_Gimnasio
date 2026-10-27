<?php
require_once ROOT_PATH . '/app/models/Pago.php';

class Notif
{
    public const SEGS = [
        'vencer'    => ['Por vencer', 'bi-hourglass-split', 'warning'],
        'vencidos'  => ['Vencidos', 'bi-x-circle', 'danger'],
        'deudas'    => ['Deudas', 'bi-cash-coin', 'danger'],
        'cumple'    => ['Cumpleaños', 'bi-gift-fill', 'success'],
        'inactivos' => ['Inactivos', 'bi-person-dash', 'info'],
    ];

    public const TPL = [
        'vencer'    => "Hola {nombre} 👋, tu plan {plan} en {gimnasio} vence el {fecha} ({dias}). ¡Renueva y sigue entrenando sin pausas! 💪",
        'vencidos'  => "Hola {nombre}, tu membresía en {gimnasio} venció el {fecha}. Te extrañamos 🏋️ Escríbenos y te ayudamos a reactivar tu plan.",
        'deudas'    => "Hola {nombre}, te recordamos que tienes un saldo pendiente de {saldo} por tu plan {plan} en {gimnasio}. Puedes pagar por Yape, Plin o en recepción. ¡Gracias!",
        'cumple'    => "¡Feliz cumpleaños {nombre}! 🎉🎂 Todo el equipo de {gimnasio} te desea un día increíble. ¡Hoy entrenas con toda la energía!",
        'inactivos' => "Hola {nombre}, hace unos días que no te vemos por {gimnasio} 💙 Tu membresía sigue vigente, ¡te esperamos para retomar tu rutina!",
    ];

    public static function ensure(): void
    {
        static $done = false;
        if ($done) return;
        Database::conn()->exec("CREATE TABLE IF NOT EXISTS notificaciones_log (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, socio_id INT UNSIGNED NOT NULL, tipo VARCHAR(20) NOT NULL,
            fecha DATETIME NOT NULL, usuario_id INT UNSIGNED NULL, INDEX idx_s (socio_id, tipo, fecha),
            FOREIGN KEY (socio_id) REFERENCES socios(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $done = true;
    }

    public static function plantilla(string $seg): string
    {
        $r = Database::row('SELECT valor FROM configuracion WHERE clave = ?', ['tpl_' . $seg]);
        return ($r && trim((string)$r['valor']) !== '') ? (string)$r['valor'] : self::TPL[$seg];
    }

    public static function mensaje(string $tpl, array $r): string
    {
        static $gym = null;
        if ($gym === null) $gym = (string)(Database::val("SELECT valor FROM configuracion WHERE clave = 'gimnasio_nombre'") ?: APP_NAME);
        $dias = isset($r['dias']) ? ((int)$r['dias'] === 0 ? 'hoy' : ((int)$r['dias'] === 1 ? 'mañana' : 'en ' . (int)$r['dias'] . ' días')) : '';
        return strtr($tpl, [
            '{nombre}' => $r['nombres'] ?? '', '{apellido}' => $r['apellidos'] ?? '', '{plan}' => $r['plan'] ?? 'tu plan', '{gimnasio}' => $gym,
            '{fecha}' => !empty($r['fecha']) ? date('d/m/Y', strtotime($r['fecha'])) : '', '{dias}' => $dias, '{saldo}' => isset($r['saldo']) ? money($r['saldo']) : '',
        ]);
    }

    private const ULT = " FROM (SELECT socio_id, MAX(fecha_fin) f FROM membresias WHERE estado <> 'cancelada' GROUP BY socio_id) t
        JOIN socios s ON s.id = t.socio_id
        JOIN membresias m ON m.id = (SELECT MAX(m2.id) FROM membresias m2 WHERE m2.socio_id = t.socio_id AND m2.fecha_fin = t.f AND m2.estado <> 'cancelada')
        JOIN planes p ON p.id = m.plan_id AND m.estado <> 'congelada' ";

    public static function lista(string $seg, ?int $dias = null): array
    {
        $dias = $dias ?? ((int)cfg('dias_aviso_vencimiento', '7') ?: 7);
        $hoy = date('Y-m-d');
        switch ($seg) {
            case 'vencer':
                return Database::all("SELECT s.id, s.codigo, s.nombres, s.apellidos, s.telefono, p.nombre plan, t.f fecha, DATEDIFF(t.f, ?) dias" . self::ULT .
                    "WHERE t.f >= ? AND t.f <= DATE_ADD(?, INTERVAL " . (int)$dias . " DAY) AND s.estado = 'activo' ORDER BY t.f, s.nombres LIMIT 500", [$hoy, $hoy, $hoy]);
            case 'vencidos':
                return Database::all("SELECT s.id, s.codigo, s.nombres, s.apellidos, s.telefono, p.nombre plan, t.f fecha, DATEDIFF(?, t.f) dias" . self::ULT .
                    "WHERE t.f < ? AND t.f >= DATE_SUB(?, INTERVAL 30 DAY) AND s.estado = 'activo' ORDER BY t.f DESC, s.nombres LIMIT 500", [$hoy, $hoy, $hoy]);
            case 'deudas':
                $o = [];
                foreach (Pago::deudas(500) as $d) { $d['saldo'] = $d['total'] - $d['pagado']; $d['fecha'] = $d['fecha_fin']; $d['id'] = $d['socio_id']; $o[] = $d; }
                return $o;
            case 'cumple':
                $md = []; for ($i = 0; $i <= 7; $i++) $md[] = date('m-d', strtotime("+$i days"));
                $in = "'" . implode("','", $md) . "'";
                $rows = Database::all("SELECT s.id, s.codigo, s.nombres, s.apellidos, s.telefono, s.fecha_nacimiento fecha, DATE_FORMAT(s.fecha_nacimiento,'%m-%d') md
                    FROM socios s WHERE s.estado = 'activo' AND s.fecha_nacimiento IS NOT NULL AND DATE_FORMAT(s.fecha_nacimiento,'%m-%d') IN ($in)");
                foreach ($rows as &$r) { $r['dias'] = (int)array_search($r['md'], $md); $r['plan'] = null; } unset($r);
                usort($rows, fn($a, $b) => $a['dias'] <=> $b['dias']);
                return $rows;
            case 'inactivos':
                return Database::all("SELECT s.id, s.codigo, s.nombres, s.apellidos, s.telefono, p.nombre plan, t.f fecha,
                        (SELECT MAX(DATE(a.ingreso)) FROM asistencias a WHERE a.socio_id = s.id) ult" . self::ULT .
                    "WHERE t.f >= ? AND s.estado = 'activo' AND s.fecha_registro <= DATE_SUB(?, INTERVAL 14 DAY)
                       AND COALESCE((SELECT MAX(a.ingreso) FROM asistencias a WHERE a.socio_id = s.id), '2000-01-01') < DATE_SUB(NOW(), INTERVAL 14 DAY)
                     ORDER BY ult IS NULL DESC, ult, s.nombres LIMIT 500", [$hoy, $hoy]);
        }
        return [];
    }

    /** socio_id => fecha del último aviso de ese tipo en los últimos 7 días */
    public static function enviados(string $seg): array
    {
        self::ensure();
        return array_column(Database::all('SELECT socio_id, MAX(fecha) f FROM notificaciones_log WHERE tipo = ? AND fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY socio_id', [$seg]), 'f', 'socio_id');
    }

    public static function conteos(): array
    {
        $o = [];
        foreach (array_keys(self::SEGS) as $k) $o[$k] = count(self::lista($k));
        return $o;
    }
}
