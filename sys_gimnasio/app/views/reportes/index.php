<?php
$g = ['#ff5a1f', '#6366f1', '#10b981', '#f59e0b', '#ec4899', '#06b6d4', '#8b5cf6'];
$ranges = ['Este mes' => [date('Y-m-01'), date('Y-m-d')], 'Mes anterior' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('first day of last month'))],
           'Últimos 90 días' => [date('Y-m-d', strtotime('-90 days')), date('Y-m-d')], 'Este año' => [date('Y-01-01'), date('Y-m-d')]];
$grad = ['#6366f1,#4f46e5', '#10b981,#059669', '#f59e0b,#d97706', '#ec4899,#db2777'];
?>
<div class="page-head no-print">
  <div><h1 class="page-title">Reportes</h1><p class="page-sub">Análisis del negocio con exportación a CSV y PDF.</p></div>
  <div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="<?= url('reportes/exportar' . qs()) ?>"><i class="bi bi-filetype-csv"></i> Exportar CSV</a>
    <button class="btn btn-brand" onclick="window.print()"><i class="bi bi-file-earmark-pdf"></i> Imprimir / PDF</button></div>
</div>
<div class="d-none d-print-block mb-3"><h2><?= APP_NAME ?> · Reporte <?= e(Reporte::TABS[$tab][0]) ?></h2><div>Del <?= fdate($desde) ?> al <?= fdate($hasta) ?></div></div>
<div class="seg mb-3 no-print"><?php foreach (Reporte::TABS as $k => $t): ?><a href="<?= url('reportes' . qs(['tab' => $k])) ?>" class="<?= $tab === $k ? 'on' : '' ?>"><i class="bi <?= $t[1] ?>"></i> <?= $t[0] ?></a><?php endforeach; ?></div>
<div class="card-x p-0 mb-3 no-print"><form class="filter-bar" method="get" action="<?= url('reportes') ?>"><input type="hidden" name="tab" value="<?= e($tab) ?>">
  <div><label class="form-lbl mb-1 small">Desde</label><input type="date" name="desde" value="<?= e($desde) ?>" class="form-control form-control-sm"></div>
  <div><label class="form-lbl mb-1 small">Hasta</label><input type="date" name="hasta" value="<?= e($hasta) ?>" class="form-control form-control-sm"></div>
  <div class="align-self-end"><button class="btn btn-soft btn-sm"><i class="bi bi-funnel"></i> Aplicar</button></div>
  <div class="align-self-end d-flex gap-1 flex-wrap"><?php foreach ($ranges as $l => $x): ?><a class="btn btn-outline-secondary btn-sm" href="<?= url('reportes' . qs(['desde' => $x[0], 'hasta' => $x[1]])) ?>"><?= $l ?></a><?php endforeach; ?></div>
</form></div>

<div class="row g-3 mb-3 kpi-grid">
  <?php foreach ($r['kpis'] as $i => $k): [$c1, $c2] = explode(',', $grad[$i % 4]); ?>
  <div class="col-6 col-xl-3"><div class="stat-card" style="--c1:<?= $c1 ?>;--c2:<?= $c2 ?>"><div class="stat-body"><div class="stat-val" style="font-size:1.6rem"><?= e((string)$k[1]) ?></div><div class="stat-lbl"><?= e($k[0]) ?></div></div></div></div>
  <?php endforeach; ?>
</div>
<div class="row g-3 mb-3">
  <?php foreach ($r['charts'] as $c): ?>
  <div class="col-lg-<?= $c['type'] === 'doughnut' ? 5 : 7 ?>"><div class="card-x"><div style="height:280px"><canvas id="<?= $c['id'] ?>"></canvas></div></div></div>
  <?php endforeach; ?>
</div>
<div class="row g-3">
  <?php foreach ($r['tablas'] as $titulo => $t): $mon = $t['money'] ?? []; ?>
  <div class="col-lg-6"><div class="card-x p-0 h-100"><div class="p-3 pb-2"><h6 class="sec-title mb-0"><?= e($titulo) ?></h6></div>
    <div class="table-responsive"><table class="table tbl align-middle mb-0 tbl-pad"><thead><tr><?php foreach ($t['cols'] as $i => $cn): ?><th class="<?= $i > 0 && (is_numeric($t['rows'][0][$i] ?? null)) ? 'text-end' : '' ?>"><?= e($cn) ?></th><?php endforeach; ?></tr></thead><tbody>
    <?php if (!$t['rows']): ?><tr><td colspan="<?= count($t['cols']) ?>" class="text-center text-muted py-4">Sin datos en el periodo.</td></tr><?php endif; ?>
    <?php foreach ($t['rows'] as $row): ?><tr><?php foreach ($row as $i => $cell): ?><td class="<?= is_numeric($cell) && $i > 0 ? 'text-end text-nowrap' : '' ?>"><?= in_array($i, $mon, true) ? money($cell) : e((string)$cell) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
    </tbody></table></div></div></div>
  <?php endforeach; ?>
</div>
<?php if (!empty($r['nota'])): ?><p class="small text-muted mt-3"><i class="bi bi-info-circle"></i> <?= e($r['nota']) ?></p><?php endif; ?>
<style>@media print{.no-print,.sidebar,.topbar,.flash-stack,footer{display:none!important}.main-wrap{margin:0!important}.content{padding:0!important}body{background:#fff!important}.card-x,.stat-card{box-shadow:none;break-inside:avoid}.stat-card{-webkit-print-color-adjust:exact;print-color-adjust:exact}}</style>
<script src="<?= ASSETS_URL ?>/vendor/chart.umd.min.js"></script>
<script>
window.addEventListener('DOMContentLoaded', function () {
  if (!window.Chart) return;
  var C = <?= json_encode($r['charts'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>, PAL = <?= json_encode($g) ?>, CUR = '<?= APP_CURRENCY ?>';
  var dark = document.documentElement.getAttribute('data-theme') === 'dark', txt = dark ? '#94a3b8' : '#64748b', grid = dark ? 'rgba(255,255,255,.06)' : 'rgba(15,23,42,.06)';
  C.forEach(function (c) {
    var el = document.getElementById(c.id); if (!el) return;
    var pie = c.type === 'doughnut', multi = c.series.length > 1;
    var ds = c.series.map(function (s, i) {
      if (pie) return { label: s[0], data: s[1], backgroundColor: PAL, borderWidth: 0 };
      return { label: s[0], data: s[1], backgroundColor: s[2], borderColor: s[2], borderRadius: 6, tension: .35, borderWidth: c.type === 'line' ? 3 : 0, pointRadius: c.type === 'line' ? 3 : 0, fill: false };
    });
    new Chart(el, { type: c.type, data: { labels: c.labels, datasets: ds }, options: {
      responsive: true, maintainAspectRatio: false, cutout: pie ? '62%' : undefined,
      plugins: { legend: { display: pie || multi, position: pie ? 'bottom' : 'top', labels: { color: txt, usePointStyle: true, boxWidth: 8 } } },
      scales: pie ? {} : { x: { ticks: { color: txt }, grid: { display: false } }, y: { ticks: { color: txt }, grid: { color: grid }, beginAtZero: true } } } });
  });
});
</script>
