<?php
require_once ROOT_PATH . '/app/models/Notif.php';
require_once ROOT_PATH . '/app/models/Membresia.php';

/** CRM del gimnasio: embudo de prospectos, seguimiento, retención de socios y campañas. */
class Crm
{
    /** etapa => [etiqueta, icono, color] */
    public const ETAPAS = [
        'nuevo'       => ['Nuevo', 'bi-stars', '#3b82f6'],
        'contactado'  => ['Contactado', 'bi-chat-dots-fill', '#8b5cf6'],
        'visita'      => ['Visita agendada', 'bi-calendar-check-fill', '#06b6d4'],
        'prueba'      => ['Clase de prueba', 'bi-lightning-charge-fill', '#f59e0b'],
        'negociacion' => ['Negociación', 'bi-cash-coin', '#ff5a1f'],
        'ganado'      => ['Ganado', 'bi-trophy-fill', '#10b981'],
        'perdido'     => ['Perdido', 'bi-x-octagon-fill', '#94a3b8'],
    ];
    public const FUENTES = [
        'instagram' => ['Instagram', 'bi-instagram'], 'facebook' => ['Facebook', 'bi-facebook'], 'tiktok' => ['TikTok', 'bi-tiktok'],
        'whatsapp' => ['WhatsApp', 'bi-whatsapp'], 'referido' => ['Referido', 'bi-people-fill'], 'pasaba' => ['Pasó por el local', 'bi-geo-alt-fill'],
        'web' => ['Página web', 'bi-globe2'], 'volante' => ['Volantes / publicidad', 'bi-megaphone-fill'], 'otro' => ['Otro', 'bi-three-dots'],
    ];
    public const TIPOS_ACT = [
        'nota' => ['Nota', 'bi-sticky-fill', '#64748b'], 'llamada' => ['Llamada', 'bi-telephone-fill', '#3b82f6'], 'whatsapp' => ['WhatsApp', 'bi-whatsapp', '#10b981'],
        'visita' => ['Visita', 'bi-geo-alt-fill', '#06b6d4'], 'email' => ['Correo', 'bi-envelope-fill', '#8b5cf6'], 'tarea' => ['Tarea', 'bi-check2-square', '#ff5a1f'],
    ];
    public const MOTIVOS = ['Precio', 'Ubicación / lejos', 'Horarios', 'Eligió otro gimnasio', 'Sin respuesta', 'Solo curioseaba', 'Otro'];

    public const SEGS = [
        'riesgo'   => ['Riesgo de abandono', 'bi-heart-pulse-fill', '#ef4444', 'Socios con puntaje de riesgo alto: dejaron de venir o están por irse.',
                       "Hola {nombre} 💙 notamos que hace días no te vemos por {gimnasio}. ¿Todo bien? Cuéntanos si podemos ayudarte con tu rutina o tus horarios. ¡Te esperamos!"],
        'vencer'   => ['Por vencer', 'bi-hourglass-split', '#f59e0b', 'Membresías que vencen pronto: oportunidad de renovar.', null],
        'vencidos' => ['Recuperar vencidos', 'bi-arrow-repeat', '#ef4444', 'Vencieron en los últimos 30 días y no renovaron.', null],
        'inactivos'=> ['Inactivos con plan', 'bi-person-dash-fill', '#3b82f6', 'Plan vigente pero sin asistir hace varios días.', null],
        'deudas'   => ['Con saldo pendiente', 'bi-cash-coin', '#ef4444', 'Socios con pagos por completar.', null],
        'cumple'   => ['Cumpleaños del mes', 'bi-gift-fill', '#10b981', 'Cumplen años este mes: fideliza con un saludo o regalo.',
                       "¡Feliz cumpleaños {nombre}! 🎉🎂 En {gimnasio} queremos celebrarlo contigo: pasa por recepción este mes y te tenemos un detalle especial 💪"],
        'nuevos'   => ['Socios nuevos', 'bi-person-plus-fill', '#06b6d4', 'Se inscribieron en los últimos 30 días: asegura una buena primera experiencia.',
                       "Hola {nombre} 👋 ¿cómo va tu inicio en {gimnasio}? Si quieres, te armamos una rutina para tu objetivo. ¡Cualquier duda, escríbenos!"],
        'estrella' => ['Socios estrella', 'bi-star-fill', '#f59e0b', 'Los más constantes (12+ visitas en 30 días): pídeles referidos.',
                       "¡Hola {nombre}! 💪 Eres de los más constantes de {gimnasio}, ¡gracias! Si traes a un amigo, ambos reciben una promo especial. Pregunta en recepción."],
        'leads'    => ['Prospectos abiertos', 'bi-bullseye', '#ff5a1f', 'Prospectos que aún no se deciden: retoma el contacto.',
                       "Hola {nombre} 👋 te escribimos de {gimnasio}. ¿Sigues interesado en entrenar con nosotros? Tenemos una promo vigente y una clase de prueba para ti 💪"],
    ];

    public static function ensure(): void
    {
        static $done = false;
        if ($done) return;
        $pdo = Database::conn();
        $pdo->exec("CREATE TABLE IF NOT EXISTS crm_leads (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nombres VARCHAR(80) NOT NULL, apellidos VARCHAR(80) NULL, telefono VARCHAR(20) NULL, email VARCHAR(120) NULL,
            fuente VARCHAR(20) NOT NULL DEFAULT 'otro', interes VARCHAR(120) NULL, etapa VARCHAR(15) NOT NULL DEFAULT 'nuevo', valor DECIMAL(10,2) NOT NULL DEFAULT 0,
            responsable_id INT UNSIGNED NULL, notas TEXT NULL, motivo_perdida VARCHAR(60) NULL, socio_id INT UNSIGNED NULL,
            created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, cerrado_en DATETIME NULL, INDEX idx_etapa (etapa), INDEX idx_creado (created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS crm_actividades (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, lead_id INT UNSIGNED NULL, socio_id INT UNSIGNED NULL, tipo VARCHAR(12) NOT NULL DEFAULT 'nota',
            detalle VARCHAR(500) NOT NULL, fecha_programada DATETIME NULL, completada TINYINT(1) NOT NULL DEFAULT 1, completada_en DATETIME NULL,
            usuario_id INT UNSIGNED NULL, created_at DATETIME NOT NULL, INDEX idx_lead (lead_id), INDEX idx_socio (socio_id), INDEX idx_pend (completada, fecha_programada)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS crm_campanas (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(120) NOT NULL, segmento VARCHAR(15) NOT NULL, mensaje TEXT NOT NULL,
            total INT UNSIGNED NOT NULL DEFAULT 0, usuario_id INT UNSIGNED NULL, created_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS crm_envios (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, campana_id INT UNSIGNED NOT NULL, tipo VARCHAR(5) NOT NULL DEFAULT 'socio', ref_id INT UNSIGNED NOT NULL,
            nombre VARCHAR(160) NOT NULL, telefono VARCHAR(20) NOT NULL, mensaje TEXT NOT NULL, enviado_en DATETIME NULL, INDEX idx_c (campana_id),
            FOREIGN KEY (campana_id) REFERENCES crm_campanas(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $done = true;
    }

    /* ---------------- Prospectos ---------------- */
    public static function leads(array $f = []): array
    {
        $w = ['1=1']; $p = [];
        if (($f['q'] ?? '') !== '') { $w[] = "(l.nombres LIKE ? OR l.apellidos LIKE ? OR l.telefono LIKE ?)"; $l = '%' . $f['q'] . '%'; array_push($p, $l, $l, $l); }
        if (!empty($f['fuente'])) { $w[] = 'l.fuente = ?'; $p[] = $f['fuente']; }
        if (!empty($f['resp'])) { $w[] = 'l.responsable_id = ?'; $p[] = (int)$f['resp']; }
        if (!empty($f['cerrados_desde'])) { $w[] = "(l.etapa NOT IN ('ganado','perdido') OR l.cerrado_en >= ?)"; $p[] = $f['cerrados_desde']; }
        return Database::all("SELECT l.*, u.nombre responsable,
            (SELECT MIN(a.fecha_programada) FROM crm_actividades a WHERE a.lead_id = l.id AND a.completada = 0 AND a.fecha_programada IS NOT NULL) proxima,
            (SELECT COUNT(*) FROM crm_actividades a WHERE a.lead_id = l.id) n_act,
            DATEDIFF(NOW(), COALESCE((SELECT MAX(a.created_at) FROM crm_actividades a WHERE a.lead_id = l.id AND a.completada = 1), l.created_at)) dias_sin_contacto
            FROM crm_leads l LEFT JOIN usuarios u ON u.id = l.responsable_id WHERE " . implode(' AND ', $w) . " ORDER BY l.updated_at DESC LIMIT 600", $p);
    }
    public static function lead(int $id): ?array
    {
        return Database::row('SELECT l.*, u.nombre responsable FROM crm_leads l LEFT JOIN usuarios u ON u.id = l.responsable_id WHERE l.id = ?', [$id]) ?: null;
    }
    public static function guardarLead(array $d, int $id = 0): int
    {
        $ahora = date('Y-m-d H:i:s');
        $vals = [$d['nombres'], $d['apellidos'] ?: null, $d['telefono'] ?: null, $d['email'] ?: null, $d['fuente'], $d['interes'] ?: null, $d['valor'], $d['responsable_id'] ?: null, $d['notas'] ?: null];
        if ($id) {
            Database::exec('UPDATE crm_leads SET nombres=?, apellidos=?, telefono=?, email=?, fuente=?, interes=?, valor=?, responsable_id=?, notas=?, updated_at=? WHERE id=?', array_merge($vals, [$ahora, $id]));
            return $id;
        }
        Database::exec('INSERT INTO crm_leads (nombres, apellidos, telefono, email, fuente, interes, valor, responsable_id, notas, etapa, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)', array_merge($vals, ['nuevo', $ahora, $ahora]));
        return (int)Database::conn()->lastInsertId();
    }
    public static function mover(int $id, string $etapa, ?string $motivo = null): bool
    {
        if (!isset(self::ETAPAS[$etapa])) return false;
        $cerrado = in_array($etapa, ['ganado', 'perdido'], true) ? date('Y-m-d H:i:s') : null;
        $n = Database::exec('UPDATE crm_leads SET etapa = ?, motivo_perdida = ?, cerrado_en = ?, updated_at = NOW() WHERE id = ?', [$etapa, $etapa === 'perdido' ? ($motivo ?: 'Otro') : null, $cerrado, $id]);
        return $n > 0;
    }
    /** Marca el prospecto como ganado y lo enlaza con el socio recién creado */
    public static function ganar(int $leadId, int $socioId, ?int $usuario = null): void
    {
        Database::exec("UPDATE crm_leads SET etapa = 'ganado', socio_id = ?, cerrado_en = NOW(), motivo_perdida = NULL, updated_at = NOW() WHERE id = ?", [$socioId, $leadId]);
        Database::exec('UPDATE crm_actividades SET socio_id = ? WHERE lead_id = ?', [$socioId, $leadId]);
        self::actividad('nota', 'Prospecto convertido en socio.', null, $socioId, null, $usuario);
    }

    /* ---------------- Actividades y tareas ---------------- */
    public static function actividad(string $tipo, string $detalle, ?int $leadId, ?int $socioId, ?string $programada, ?int $usuario): int
    {
        if (!isset(self::TIPOS_ACT[$tipo])) $tipo = 'nota';
        $pend = $programada ? 0 : 1;
        Database::exec('INSERT INTO crm_actividades (lead_id, socio_id, tipo, detalle, fecha_programada, completada, completada_en, usuario_id, created_at) VALUES (?,?,?,?,?,?,?,?,NOW())',
            [$leadId, $socioId, $tipo, mb_substr($detalle, 0, 500), $programada, $pend, $pend ? date('Y-m-d H:i:s') : null, $usuario]);
        if ($leadId && $pend && $tipo !== 'nota') Database::exec("UPDATE crm_leads SET etapa = IF(etapa = 'nuevo', 'contactado', etapa), updated_at = NOW() WHERE id = ?", [$leadId]);
        elseif ($leadId) Database::exec('UPDATE crm_leads SET updated_at = NOW() WHERE id = ?', [$leadId]);
        return (int)Database::conn()->lastInsertId();
    }
    public static function actividadesDe(string $campo, int $id, int $lim = 60): array
    {
        $col = $campo === 'socio' ? 'a.socio_id' : 'a.lead_id';
        return Database::all("SELECT a.*, u.nombre usuario FROM crm_actividades a LEFT JOIN usuarios u ON u.id = a.usuario_id WHERE $col = ? ORDER BY COALESCE(a.completada_en, a.fecha_programada, a.created_at) DESC, a.id DESC LIMIT " . (int)$lim, [$id]);
    }
    public static function tareas(string $estado = 'pendientes'): array
    {
        $w = $estado === 'hechas' ? 'a.completada = 1 AND a.fecha_programada IS NOT NULL' : 'a.completada = 0 AND a.fecha_programada IS NOT NULL';
        $ord = $estado === 'hechas' ? 'a.completada_en DESC' : 'a.fecha_programada ASC';
        return Database::all("SELECT a.*, l.nombres l_nom, l.apellidos l_ape, l.telefono l_tel, s.nombres s_nom, s.apellidos s_ape, s.telefono s_tel
            FROM crm_actividades a LEFT JOIN crm_leads l ON l.id = a.lead_id LEFT JOIN socios s ON s.id = a.socio_id WHERE $w ORDER BY $ord LIMIT 300");
    }
    public static function completar(int $id): bool
    {
        return Database::exec('UPDATE crm_actividades SET completada = 1, completada_en = NOW() WHERE id = ? AND completada = 0', [$id]) > 0;
    }
    public static function conteoTareas(): array
    {
        $r = Database::row("SELECT COALESCE(SUM(fecha_programada < NOW()),0) venc, COALESCE(SUM(DATE(fecha_programada) = CURDATE()),0) hoy, COUNT(*) pend FROM crm_actividades WHERE completada = 0 AND fecha_programada IS NOT NULL");
        return ['vencidas' => (int)($r['venc'] ?? 0), 'hoy' => (int)($r['hoy'] ?? 0), 'pendientes' => (int)($r['pend'] ?? 0)];
    }

    /* ---------------- Panel ---------------- */
    public static function resumen(): array
    {
        $ab = Database::row("SELECT COUNT(*) n, COALESCE(SUM(valor),0) valor FROM crm_leads WHERE etapa NOT IN ('ganado','perdido')");
        $mes = date('Y-m-01');
        $g = (int)Database::val("SELECT COUNT(*) FROM crm_leads WHERE etapa = 'ganado' AND cerrado_en >= ?", [$mes]);
        $p = (int)Database::val("SELECT COUNT(*) FROM crm_leads WHERE etapa = 'perdido' AND cerrado_en >= ?", [$mes]);
        $nuevosMes = (int)Database::val('SELECT COUNT(*) FROM crm_leads WHERE created_at >= ?', [$mes]);
        $tot = (int)Database::val("SELECT COUNT(*) FROM crm_leads WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)");
        $gan = (int)Database::val("SELECT COUNT(*) FROM crm_leads WHERE etapa = 'ganado' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)");
        $dias = Database::val("SELECT AVG(DATEDIFF(cerrado_en, created_at)) FROM crm_leads WHERE etapa = 'ganado' AND cerrado_en IS NOT NULL");
        return ['abiertos' => (int)$ab['n'], 'valor' => (float)$ab['valor'], 'ganados_mes' => $g, 'perdidos_mes' => $p, 'nuevos_mes' => $nuevosMes,
                'conversion' => $tot ? round($gan / $tot * 100, 1) : 0.0, 'dias_cierre' => $dias ? round((float)$dias, 1) : 0];
    }
    public static function embudo(): array
    {
        $o = []; foreach (self::ETAPAS as $k => $e) $o[$k] = ['n' => 0, 'valor' => 0.0];
        foreach (Database::all('SELECT etapa, COUNT(*) n, COALESCE(SUM(valor),0) v FROM crm_leads GROUP BY etapa') as $r) if (isset($o[$r['etapa']])) $o[$r['etapa']] = ['n' => (int)$r['n'], 'valor' => (float)$r['v']];
        return $o;
    }
    public static function porFuente(): array
    {
        return Database::all("SELECT fuente, COUNT(*) n, SUM(etapa = 'ganado') ganados FROM crm_leads GROUP BY fuente ORDER BY n DESC");
    }
    public static function tendencia(int $meses = 6): array
    {
        $o = [];
        for ($i = $meses - 1; $i >= 0; $i--) { $k = date('Y-m', strtotime("first day of -$i month")); $o[$k] = ['mes' => $k, 'leads' => 0, 'ganados' => 0]; }
        foreach (Database::all("SELECT DATE_FORMAT(created_at,'%Y-%m') m, COUNT(*) n FROM crm_leads WHERE created_at >= ? GROUP BY m", [date('Y-m-01', strtotime('-' . ($meses - 1) . ' month'))]) as $r) if (isset($o[$r['m']])) $o[$r['m']]['leads'] = (int)$r['n'];
        foreach (Database::all("SELECT DATE_FORMAT(cerrado_en,'%Y-%m') m, COUNT(*) n FROM crm_leads WHERE etapa = 'ganado' AND cerrado_en >= ? GROUP BY m", [date('Y-m-01', strtotime('-' . ($meses - 1) . ' month'))]) as $r) if (isset($o[$r['m']])) $o[$r['m']]['ganados'] = (int)$r['n'];
        return array_values($o);
    }
    public static function motivosPerdida(): array
    {
        return Database::all("SELECT COALESCE(motivo_perdida,'Otro') motivo, COUNT(*) n FROM crm_leads WHERE etapa = 'perdido' GROUP BY motivo ORDER BY n DESC LIMIT 6");
    }

    /* ---------------- Retención ---------------- */
    /** Socios activos con su comportamiento reciente y puntaje de riesgo (0-100). */
    public static function riesgo(int $min = 30): array
    {
        $rows = Database::all("SELECT s.id, s.codigo, s.nombres, s.apellidos, s.telefono, s.fecha_registro,
              pl.nombre plan, ult.fecha_fin, DATEDIFF(ult.fecha_fin, CURDATE()) dias_vence,
              (SELECT MAX(a.ingreso) FROM asistencias a WHERE a.socio_id = s.id) ult_visita,
              (SELECT COUNT(*) FROM asistencias a WHERE a.socio_id = s.id AND a.ingreso >= DATE_SUB(NOW(), INTERVAL 30 DAY)) v30,
              (SELECT COUNT(*) FROM asistencias a WHERE a.socio_id = s.id AND a.ingreso < DATE_SUB(NOW(), INTERVAL 30 DAY) AND a.ingreso >= DATE_SUB(NOW(), INTERVAL 60 DAY)) vprev,
              (SELECT COALESCE(SUM((m.precio - m.descuento) - " . str_replace('m.id', 'm.id', Membresia::PAGADO) . "), 0) FROM membresias m WHERE m.socio_id = s.id AND m.estado <> 'cancelada') saldo
            FROM socios s
            JOIN (SELECT m.socio_id, m.fecha_fin, m.plan_id FROM membresias m WHERE m.estado <> 'cancelada' AND m.id = (SELECT m2.id FROM membresias m2 WHERE m2.socio_id = m.socio_id AND m2.estado <> 'cancelada' ORDER BY m2.fecha_fin DESC, m2.id DESC LIMIT 1)) ult ON ult.socio_id = s.id
            JOIN planes pl ON pl.id = ult.plan_id
            WHERE s.estado = 'activo' AND ult.fecha_fin >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
              AND NOT EXISTS (SELECT 1 FROM membresias mc WHERE mc.socio_id = s.id AND mc.estado = 'congelada')");
        $o = [];
        foreach ($rows as $r) {
            [$sc, $niv, $mot] = self::puntaje($r);
            if ($sc < $min) continue;
            $r['puntaje'] = $sc; $r['nivel'] = $niv; $r['motivos'] = $mot;
            $r['dias_sin_visita'] = $r['ult_visita'] ? (int)floor((time() - strtotime($r['ult_visita'])) / 86400) : null;
            $o[] = $r;
        }
        usort($o, fn($a, $b) => $b['puntaje'] <=> $a['puntaje']);
        return $o;
    }
    public static function puntaje(array $r): array
    {
        $sc = 0; $mot = [];
        $nuevo = !empty($r['fecha_registro']) && strtotime($r['fecha_registro']) >= strtotime('-7 days');
        $ds = !empty($r['ult_visita']) ? (int)floor((time() - strtotime($r['ult_visita'])) / 86400) : null;
        if ($ds === null) { if (!$nuevo) { $sc += 40; $mot[] = 'Nunca ha asistido'; } }
        elseif ($ds >= 30) { $sc += 45; $mot[] = "$ds días sin venir"; }
        elseif ($ds >= 21) { $sc += 38; $mot[] = "$ds días sin venir"; }
        elseif ($ds >= 14) { $sc += 28; $mot[] = "$ds días sin venir"; }
        elseif ($ds >= 7)  { $sc += 14; $mot[] = "$ds días sin venir"; }
        $v30 = (int)$r['v30']; $vp = (int)$r['vprev'];
        if ($vp >= 4 && $v30 <= $vp * 0.5) { $sc += 20; $mot[] = 'Asistencia cayó de ' . $vp . ' a ' . $v30 . ' visitas'; }
        $dv = (int)$r['dias_vence'];
        if ($dv < 0) { $sc += 25; $mot[] = 'Venció hace ' . abs($dv) . ' d'; }
        elseif ($dv <= 7) { $sc += 15; $mot[] = $dv === 0 ? 'Vence hoy' : "Vence en $dv d"; }
        if ((float)$r['saldo'] > 0.009) { $sc += 10; $mot[] = 'Saldo pendiente'; }
        $sc = min(100, $sc);
        return [$sc, $sc >= 60 ? 'alto' : ($sc >= 30 ? 'medio' : 'bajo'), $mot];
    }

    /* ---------------- Segmentos y campañas ---------------- */
    private static function norm(array $r, string $tipo = 'socio'): array
    {
        return ['tipo' => $tipo, 'id' => (int)$r['id'], 'nombres' => $r['nombres'], 'apellidos' => $r['apellidos'] ?? '', 'telefono' => $r['telefono'] ?? '', 'plan' => $r['plan'] ?? null,
                'fecha' => $r['fecha'] ?? null, 'dias' => $r['dias'] ?? null, 'saldo' => $r['saldo'] ?? null];
    }
    public static function segmento(string $k): array
    {
        $o = [];
        switch ($k) {
            case 'riesgo':
                foreach (self::riesgo(60) as $r) $o[] = self::norm($r + ['fecha' => $r['fecha_fin'], 'dias' => $r['dias_vence']]);
                break;
            case 'vencer': case 'vencidos': case 'inactivos': case 'deudas':
                foreach (Notif::lista($k) as $r) $o[] = self::norm($r);
                break;
            case 'cumple':
                foreach (Database::all("SELECT s.id, s.nombres, s.apellidos, s.telefono, s.fecha_nacimiento fecha FROM socios s WHERE s.estado = 'activo' AND s.fecha_nacimiento IS NOT NULL AND MONTH(s.fecha_nacimiento) = MONTH(CURDATE()) ORDER BY DAY(s.fecha_nacimiento) LIMIT 500") as $r) $o[] = self::norm($r);
                break;
            case 'nuevos':
                foreach (Database::all("SELECT s.id, s.nombres, s.apellidos, s.telefono, s.fecha_registro fecha FROM socios s WHERE s.estado = 'activo' AND s.fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ORDER BY s.fecha_registro DESC LIMIT 500") as $r) $o[] = self::norm($r);
                break;
            case 'estrella':
                foreach (Database::all("SELECT s.id, s.nombres, s.apellidos, s.telefono, COUNT(a.id) n FROM socios s JOIN asistencias a ON a.socio_id = s.id AND a.ingreso >= DATE_SUB(NOW(), INTERVAL 30 DAY) WHERE s.estado = 'activo' GROUP BY s.id, s.nombres, s.apellidos, s.telefono HAVING n >= 12 ORDER BY n DESC LIMIT 500") as $r) $o[] = self::norm($r);
                break;
            case 'leads':
                foreach (Database::all("SELECT id, nombres, apellidos, telefono, interes plan FROM crm_leads WHERE etapa NOT IN ('ganado','perdido') ORDER BY updated_at DESC LIMIT 500") as $r) $o[] = self::norm($r, 'lead');
                break;
        }
        return $o;
    }
    public static function plantilla(string $k): string
    {
        $s = self::SEGS[$k][4] ?? null;
        return $s ?: Notif::plantilla($k);
    }
    public static function mensaje(string $tpl, array $r): string
    {
        $r['nombres'] = trim(explode(' ', trim((string)$r['nombres']))[0]);
        return strtr(Notif::mensaje($tpl, $r), ['{vence}' => !empty($r['fecha']) ? fdate($r['fecha']) : '']);
    }
    public static function crearCampana(string $nombre, string $seg, string $tpl, array $dest, ?int $usuario): int
    {
        $pdo = Database::conn(); $pdo->beginTransaction();
        try {
            Database::exec('INSERT INTO crm_campanas (nombre, segmento, mensaje, total, usuario_id, created_at) VALUES (?,?,?,?,?,NOW())', [$nombre, $seg, $tpl, count($dest), $usuario]);
            $cid = (int)$pdo->lastInsertId();
            foreach ($dest as $r) Database::exec('INSERT INTO crm_envios (campana_id, tipo, ref_id, nombre, telefono, mensaje) VALUES (?,?,?,?,?,?)',
                [$cid, $r['tipo'], $r['id'], trim($r['nombres'] . ' ' . $r['apellidos']), $r['telefono'], self::mensaje($tpl, $r)]);
            $pdo->commit(); return $cid;
        } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
    }
    public static function campanas(): array
    {
        return Database::all("SELECT c.*, u.nombre usuario, (SELECT COUNT(*) FROM crm_envios e WHERE e.campana_id = c.id AND e.enviado_en IS NOT NULL) enviados
            FROM crm_campanas c LEFT JOIN usuarios u ON u.id = c.usuario_id ORDER BY c.id DESC LIMIT 30");
    }
    public static function marcarEnvio(int $id, ?int $usuario): bool
    {
        $e = Database::row('SELECT e.*, c.nombre cnom FROM crm_envios e JOIN crm_campanas c ON c.id = e.campana_id WHERE e.id = ?', [$id]);
        if (!$e) return false;
        if ($e['enviado_en']) return true;
        Database::exec('UPDATE crm_envios SET enviado_en = NOW() WHERE id = ?', [$id]);
        $det = 'Campaña «' . $e['cnom'] . '» enviada por WhatsApp.';
        if ($e['tipo'] === 'lead') self::actividad('whatsapp', $det, (int)$e['ref_id'], null, null, $usuario);
        else self::actividad('whatsapp', $det, null, (int)$e['ref_id'], null, $usuario);
        return true;
    }

    /* ---------------- Ficha 360° del socio ---------------- */
    public static function linea(int $socioId): array
    {
        $ev = [];
        foreach (Database::all("SELECT p.fecha_pago f, p.monto, p.metodo, p.concepto FROM pagos p WHERE p.socio_id = ? ORDER BY p.fecha_pago DESC LIMIT 15", [$socioId]) as $r)
            $ev[] = ['f' => $r['f'], 'tipo' => 'pago', 'ico' => 'bi-cash-stack', 'c' => '#10b981', 't' => 'Pago de ' . money($r['monto']), 'd' => ucfirst((string)$r['metodo']) . ($r['concepto'] ? ' · ' . $r['concepto'] : '')];
        foreach (Database::all("SELECT m.created_at f, m.fecha_inicio, m.fecha_fin, p.nombre plan FROM membresias m JOIN planes p ON p.id = m.plan_id WHERE m.socio_id = ? ORDER BY m.id DESC LIMIT 10", [$socioId]) as $r)
            $ev[] = ['f' => $r['f'] ?: $r['fecha_inicio'], 'tipo' => 'membresia', 'ico' => 'bi-award-fill', 'c' => '#ff5a1f', 't' => 'Membresía ' . $r['plan'], 'd' => fdate($r['fecha_inicio']) . ' → ' . fdate($r['fecha_fin'])];
        foreach (Database::all("SELECT ingreso f FROM asistencias WHERE socio_id = ? ORDER BY ingreso DESC LIMIT 8", [$socioId]) as $r)
            $ev[] = ['f' => $r['f'], 'tipo' => 'visita', 'ico' => 'bi-door-open-fill', 'c' => '#3b82f6', 't' => 'Visita al gimnasio', 'd' => ''];
        foreach (Database::all("SELECT fecha f, peso, grasa FROM evaluaciones WHERE socio_id = ? ORDER BY fecha DESC LIMIT 5", [$socioId]) as $r)
            $ev[] = ['f' => $r['f'], 'tipo' => 'eval', 'ico' => 'bi-activity', 'c' => '#8b5cf6', 't' => 'Evaluación física', 'd' => $r['peso'] . ' kg' . ($r['grasa'] ? ' · ' . $r['grasa'] . ' % grasa' : '')];
        foreach (self::actividadesDe('socio', $socioId, 40) as $a) {
            $T = self::TIPOS_ACT[$a['tipo']] ?? self::TIPOS_ACT['nota'];
            $ev[] = ['f' => $a['completada_en'] ?: ($a['fecha_programada'] ?: $a['created_at']), 'tipo' => 'crm', 'ico' => $T[1], 'c' => $T[2], 't' => $T[0] . ($a['completada'] ? '' : ' (pendiente)'), 'd' => $a['detalle'], 'u' => $a['usuario'] ?? null];
        }
        usort($ev, fn($a, $b) => strcmp((string)$b['f'], (string)$a['f']));
        return array_slice($ev, 0, 45);
    }
    public static function rfm(int $socioId): array
    {
        $r = Database::row("SELECT (SELECT COALESCE(SUM(monto),0) FROM pagos WHERE socio_id = ?) pagos, (SELECT COALESCE(SUM(total),0) FROM ventas WHERE socio_id = ?) compras,
            (SELECT COUNT(*) FROM asistencias WHERE socio_id = ?) visitas, (SELECT COUNT(*) FROM asistencias WHERE socio_id = ? AND ingreso >= DATE_SUB(NOW(), INTERVAL 30 DAY)) v30,
            (SELECT COUNT(*) FROM membresias WHERE socio_id = ? AND estado <> 'cancelada') n_mem,
            (SELECT MIN(fecha_registro) FROM socios WHERE id = ?) desde", array_fill(0, 6, $socioId));
        return $r ?: [];
    }
}
