<?php
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
/** Preferencias de formato (moneda, decimales, separadores, fecha) guardadas en Configuración */
function fmt_cfg(): array
{
    static $f = null;
    if ($f === null) {
        $ds = cfg('sep_decimal', '.'); $ts = cfg('sep_miles', ',');
        $ts = ['coma' => ',', 'punto' => '.', 'espacio' => ' ', 'ninguno' => ''][$ts] ?? $ts; $ds = ['coma' => ',', 'punto' => '.'][$ds] ?? $ds;
        $f = ['sym' => cfg('moneda', 'S/'), 'code' => cfg('moneda_codigo', 'PEN'), 'dec' => max(0, min(4, (int)cfg('moneda_decimales', '2'))), 'ds' => $ds, 'ts' => $ts,
              'pos' => cfg('moneda_posicion', 'antes') === 'despues' ? 'despues' : 'antes', 'sp' => cfg('moneda_espacio', '1') === '1'];
        if ($f['ds'] === $f['ts']) $f['ts'] = $f['ds'] === '.' ? ',' : '.';
    }
    return $f;
}
function fmt_num($n, ?int $dec = null): string { $f = fmt_cfg(); return number_format((float)$n, $dec ?? $f['dec'], $f['ds'], $f['ts']); }
function money($n, ?int $dec = null): string
{
    $f = fmt_cfg(); $x = fmt_num($n, $dec); $sp = $f['sp'] ? ' ' : '';
    return $f['pos'] === 'despues' ? $x . $sp . $f['sym'] : $f['sym'] . $sp . $x;
}
function url(string $p = ''): string { return BASE_URL . '/' . ltrim($p, '/'); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(Auth::csrf()) . '">'; }
function fdate($d, string $f = 'd/m/Y'): string
{
    static $fd = null; if ($fd === null) { $fd = cfg('formato_fecha', 'd/m/Y'); if (!in_array($fd, ['d/m/Y', 'd-m-Y', 'Y-m-d', 'm/d/Y'], true)) $fd = 'd/m/Y'; }
    return $d ? date(str_replace('d/m/Y', $fd, $f), strtotime((string)$d)) : '—';
}
function iniciales(string $n, string $a = ''): string
{
    return mb_strtoupper(mb_substr(trim($n), 0, 1) . mb_substr(trim($a), 0, 1));
}
/** Valor de un arreglo para inputs (escapado) */
function v(array $a, string $k, $d = ''): string { return e($a[$k] ?? $d); }

/** Querystring actual, con overrides (null elimina la clave) */
function qs(array $over = []): string
{
    $q = $_GET; unset($q['url']);
    foreach ($over as $k => $val) { if ($val === null) unset($q[$k]); else $q[$k] = $val; }
    return $q ? '?' . http_build_query($q) : '';
}

function paginate(int $total, int $per, int $page): array
{
    $pages = max(1, (int)ceil($total / $per));
    $page = min(max(1, $page), $pages);
    return ['total' => $total, 'per' => $per, 'page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $per];
}

function pager(array $pg): string
{
    if ($pg['pages'] <= 1) return '';
    $h = '<nav class="pager"><ul class="pagination pagination-sm mb-0">';
    $link = fn($p, $t, $dis = false, $act = false) =>
        '<li class="page-item' . ($dis ? ' disabled' : '') . ($act ? ' active' : '') . '"><a class="page-link" href="' . e(qs(['p' => $p])) . '">' . $t . '</a></li>';
    $h .= $link($pg['page'] - 1, '<i class="bi bi-chevron-left"></i>', $pg['page'] <= 1);
    $from = max(1, $pg['page'] - 2); $to = min($pg['pages'], $pg['page'] + 2);
    if ($from > 1) { $h .= $link(1, '1'); if ($from > 2) $h .= '<li class="page-item disabled"><span class="page-link">…</span></li>'; }
    for ($i = $from; $i <= $to; $i++) $h .= $link($i, (string)$i, false, $i === $pg['page']);
    if ($to < $pg['pages']) { if ($to < $pg['pages'] - 1) $h .= '<li class="page-item disabled"><span class="page-link">…</span></li>'; $h .= $link($pg['pages'], (string)$pg['pages']); }
    $h .= $link($pg['page'] + 1, '<i class="bi bi-chevron-right"></i>', $pg['page'] >= $pg['pages']);
    return $h . '</ul></nav>';
}

/** Estado visual de una membresía a partir de sus fechas */
function estado_membresia(?string $inicio, ?string $fin, string $estado = 'activa'): array
{
    if ($estado === 'cancelada') return ['Cancelada', 'secondary'];
    if ($estado === 'congelada') return ['Congelada', 'info'];
    if (!$fin) return ['Sin membresía', 'secondary'];
    $hoy = date('Y-m-d');
    if ($fin < $hoy) return ['Vencida', 'danger'];
    if ($inicio && $inicio > $hoy) return ['Programada', 'info'];
    if ($fin <= date('Y-m-d', strtotime('+7 days'))) return ['Por vencer', 'warning'];
    return ['Vigente', 'success'];
}

function dias_restantes(?string $fin): ?int
{
    if (!$fin) return null;
    return (int)(new DateTime('today'))->diff(new DateTime($fin))->format('%r%a');
}

function wa_link(?string $tel, string $msg = ''): string
{
    $n = preg_replace('/\D/', '', (string)$tel);
    if ($n === '') return '#';
    if (strlen($n) === 9) $n = '51' . $n;
    return 'https://wa.me/' . $n . ($msg !== '' ? '?text=' . rawurlencode($msg) : '');
}

function foto_url(?string $f): string { return $f ? BASE_URL . '/public/' . ltrim($f, '/') : ''; }


/** Valor de la tabla configuracion (cargada una sola vez por petición) */
function cfg(string $k, string $d = ''): string
{
    static $all = null;
    if ($all === null) { try { $all = array_column(Database::all('SELECT clave, valor FROM configuracion'), 'valor', 'clave'); } catch (Throwable $e) { $all = []; } }
    return isset($all[$k]) && $all[$k] !== '' ? (string)$all[$k] : $d;
}

// Moneda y zona horaria configurables (se leen una vez por petición)
if (!defined('APP_CURRENCY')) define('APP_CURRENCY', cfg('moneda', 'S/'));
(function () {
    $tz = cfg('zona_horaria', 'America/Lima');
    if ($tz !== date_default_timezone_get() && in_array($tz, DateTimeZone::listIdentifiers(), true)) {
        date_default_timezone_set($tz);
        try { Database::conn()->exec("SET time_zone = '" . date('P') . "'"); } catch (Throwable $e) {}
    }
})();
