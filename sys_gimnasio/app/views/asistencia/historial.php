<div class="page-head">
  <div><h1 class="page-title">Historial de asistencias</h1><p class="page-sub">Consulta ingresos y salidas, frecuencia y horas pico.</p></div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="<?= url('asistencia/exportar') . qs(['p' => null]) ?>" class="btn btn-outline-secondary"><i class="bi bi-filetype-csv"></i> Exportar CSV</a>
    <a href="<?= url('asistencia') ?>" class="btn btn-brand"><i class="bi bi-qr-code-scan"></i> Control de acceso</a>
  </div>
</div>

<div class="row g-3 mb-3 kpi-grid">
  <?php foreach ([
    ['Ingresos en el periodo', $res['total'], 'bi-box-arrow-in-right', '#10b981', '#059669'],
    ['Socios distintos', $res['unicos'], 'bi-people-fill', '#6366f1', '#4f46e5'],
    ['Promedio diario', $res['promedio'], 'bi-bar-chart-fill', '#06b6d4', '#0891b2'],
    ['Hora pico', $res['hora_pico'], 'bi-clock-fill', '#ff5a1f', '#ff8a3d'],
  ] as [$l, $val, $ic, $c1, $c2]): ?>
  <div class="col-6 col-xl-3"><div class="stat-card" style="--c1:<?= $c1 ?>;--c2:<?= $c2 ?>"><div class="stat-body">
    <div class="stat-val"><?= $val ?></div><div class="stat-lbl"><?= $l ?></div><i class="bi <?= $ic ?> stat-ghost"></i></div>
    <div class="stat-foot"><span><?= fdate($f['desde'], 'd/m') ?> – <?= fdate($f['hasta'], 'd/m/Y') ?></span><i class="bi bi-calendar-range"></i></div></div></div>
  <?php endforeach; ?>
</div>

<div class="card-x mb-3">
  <div class="card-h"><div><h6>Ingresos por día</h6><small>Periodo seleccionado</small></div></div>
  <div class="chart-box" style="height:230px"><canvas id="chHist"></canvas></div>
</div>

<div class="card-x p-0">
  <form class="filter-bar" method="get" action="<?= url('asistencia/historial') ?>">
    <div><label class="form-lbl mb-1 small">Desde</label><input type="date" name="desde" value="<?= e($f['desde']) ?>" class="form-control form-control-sm"></div>
    <div><label class="form-lbl mb-1 small">Hasta</label><input type="date" name="hasta" value="<?= e($f['hasta']) ?>" class="form-control form-control-sm"></div>
    <div class="flex-fill" style="min-width:200px"><label class="form-lbl mb-1 small">Socio</label><input type="search" name="q" value="<?= e($f['q']) ?>" class="form-control form-control-sm" placeholder="Nombre, DNI o código"></div>
    <div class="align-self-end d-flex gap-2"><button class="btn btn-soft btn-sm"><i class="bi bi-funnel"></i> Filtrar</button>
      <a href="<?= url('asistencia/historial') ?>" class="btn btn-outline-secondary btn-sm">Últimos 7 días</a></div>
  </form>
  <div class="table-responsive">
    <table class="table tbl align-middle mb-0 tbl-pad">
      <thead><tr><th>Fecha</th><th>Socio</th><th>Ingreso</th><th>Salida</th><th class="d-none d-md-table-cell">Duración</th><th class="d-none d-md-table-cell">Método</th></tr></thead>
      <tbody>
      <?php if (!$rows): ?><tr><td colspan="6"><div class="empty-state"><i class="bi bi-door-open"></i><b>No hay asistencias en este periodo</b></div></td></tr><?php endif; ?>
      <?php foreach ($rows as $r): $dur = $r['salida'] ? (int)round((strtotime($r['salida']) - strtotime($r['ingreso'])) / 60) : null; ?>
        <tr>
          <td><?= fdate($r['ingreso']) ?></td>
          <td><a href="<?= url('socios/ver/' . $r['socio_id']) ?>" class="text-reset"><b><?= e($r['nombres'] . ' ' . $r['apellidos']) ?></b><br><small class="text-muted"><?= e($r['codigo']) ?></small></a></td>
          <td><b><?= date('H:i', strtotime($r['ingreso'])) ?></b></td>
          <td><?= $r['salida'] ? date('H:i', strtotime($r['salida'])) : '<span class="text-muted">—</span>' ?></td>
          <td class="d-none d-md-table-cell"><?= $dur === null ? '—' : ($dur >= 60 ? intdiv($dur, 60) . ' h ' . ($dur % 60) . ' min' : $dur . ' min') ?></td>
          <td class="d-none d-md-table-cell"><span class="tag"><?= e(strtoupper($r['metodo'])) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="table-foot"><small class="text-muted"><?= $pg['total'] ?> registros</small><?= pager($pg) ?></div>
</div>

<script src="<?= ASSETS_URL ?>/vendor/chart.umd.min.js"></script>
<script>
window.addEventListener('DOMContentLoaded', function () {
  var S = <?= json_encode($res['serie']) ?>, dark = document.documentElement.getAttribute('data-theme') === 'dark';
  var css = function (v) { return getComputedStyle(document.documentElement).getPropertyValue(v).trim(); };
  var ctx = document.getElementById('chHist').getContext('2d'), g = ctx.createLinearGradient(0, 0, 0, 230);
  g.addColorStop(0, '#ff5a1f'); g.addColorStop(1, '#ff8a3d55');
  new Chart(ctx, { type: 'bar', data: { labels: S.labels, datasets: [{ label: 'Ingresos', data: S.data, backgroundColor: g, borderRadius: 8, borderSkipped: false, maxBarThickness: 34 }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
      scales: { x: { grid: { display: false }, border: { display: false }, ticks: { color: css('--muted'), maxRotation: 0, autoSkipPadding: 10 } },
                y: { beginAtZero: true, grid: { color: dark ? 'rgba(255,255,255,.06)' : 'rgba(15,23,42,.06)' }, border: { display: false }, ticks: { color: css('--muted'), precision: 0 } } } } });
});
</script>
