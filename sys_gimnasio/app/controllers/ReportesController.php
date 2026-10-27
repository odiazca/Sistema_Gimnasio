<?php
require_once ROOT_PATH . '/app/models/Reporte.php';

class ReportesController extends Controller
{
    private function params(): array
    {
        $tab = $this->in('tab', 'financiero'); if (!isset(Reporte::TABS[$tab])) $tab = 'financiero';
        $ok = fn($s) => ($x = DateTime::createFromFormat('Y-m-d', $s)) && $x->format('Y-m-d') === $s;
        $d = $this->in('desde', date('Y-01-01')); $h = $this->in('hasta', date('Y-m-d'));
        if (!$ok($d)) $d = date('Y-01-01'); if (!$ok($h)) $h = date('Y-m-d');
        if ($d > $h) [$d, $h] = [$h, $d];
        if (strtotime($h) - strtotime($d) > 366 * 3 * 86400) $d = date('Y-m-d', strtotime($h . ' -3 years'));
        return [$tab, $d, $h];
    }

    public function index(): void
    {
        $this->requireAuth();
        [$tab, $d, $h] = $this->params();
        $this->view('reportes/index', ['title' => 'Reportes', 'active' => 'reportes', 'tab' => $tab, 'desde' => $d, 'hasta' => $h, 'r' => Reporte::generar($tab, $d, $h)]);
    }

    public function exportar(): void
    {
        $this->requireAuth();
        [$tab, $d, $h] = $this->params();
        $r = Reporte::generar($tab, $d, $h);
        $safe = fn($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte_' . $tab . '_' . $d . '_' . $h . '.csv"');
        $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
        fputcsv($o, ['Reporte ' . Reporte::TABS[$tab][0] . ' - ' . APP_NAME], ';');
        fputcsv($o, ['Periodo', $d, $h], ';');
        foreach ($r['kpis'] as $k) fputcsv($o, [$k[0], $k[1]], ';');
        foreach ($r['tablas'] as $t => $x) {
            fputcsv($o, [], ';'); fputcsv($o, [$t], ';'); fputcsv($o, $x['cols'], ';');
            foreach ($x['rows'] as $row) fputcsv($o, array_map(fn($c, $i) => is_float($c) ? number_format($c, 2, '.', '') : $safe($c), $row, array_keys($row)), ';');
        }
        fclose($o); exit;
    }
}
