<?php
$num = fn($x) => $x !== null ? (float)$x : null;
$first = $ev ? $ev[0] : null; $last = $ev ? end($ev) : null; $n = count($ev);
$imcL = $last ? Evaluacion::imc($num($last['peso']), $num($last['talla'])) : null; $catL = Evaluacion::categoria($imcL);
$dl = function ($f, $l, $unit, $good = 'down') { if ($f === null || $l === null) return '—'; $d = round($l - $f, 1); if ($d == 0) return '<span class="text-muted">sin cambio</span>';
  $ok = ($good === 'down') ? $d < 0 : $d > 0; return '<span class="' . ($ok ? 'text-success' : 'text-danger') . '">' . ($d > 0 ? '+' : '−') . abs($d) . ' ' . $unit . '</span>'; };
$labels = array_map(fn($e) => fdate($e['fecha'], 'd/m/y'), $ev);
$serie = fn($k) => array_map(fn($e) => $e[$k] !== null ? (float)$e[$k] : null, $ev);
$imcSerie = array_map(fn($e) => Evaluacion::imc($num($e['peso']), $num($e['talla'])), $ev);
?>
<div class="page-head">
  <div><a href="<?= url('evaluaciones') ?>" class="small text-muted text-decoration-none"><i class="bi bi-arrow-left"></i> Evaluaciones</a>
    <h1 class="page-title mt-1"><?= e($s['nombres'] . ' ' . $s['apellidos']) ?></h1><p class="page-sub"><?= e($s['codigo']) ?> · <?= $n ?> <?= $n == 1 ? 'evaluación' : 'evaluaciones' ?></p></div>
  <div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="<?= url('socios/ver/' . $s['id']) ?>"><i class="bi bi-person"></i> Ficha</a>
    <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#evModal"><i class="bi bi-plus-lg"></i> Nueva evaluación</button></div>
</div>
<?php if (!$ev): ?>
<div class="card-x text-center py-5"><i class="bi bi-activity fs-1 text-muted"></i><p class="text-muted mt-2 mb-3">Este socio aún no tiene evaluaciones.</p><button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#evModal">Registrar la primera</button></div>
<?php else: ?>
<div class="row g-3 mb-3 kpi-grid">
  <?php foreach ([
    ['Peso actual', $last['peso'] !== null ? number_format($last['peso'], 1) . ' kg' : '—', 'bi-speedometer2', '#6366f1', '#4f46e5', $dl($num($first['peso']), $num($last['peso']), 'kg')],
    ['IMC', $imcL !== null ? $imcL : '—', 'bi-heart-pulse-fill', '#10b981', '#059669', $catL[0]],
    ['% de grasa', $last['grasa'] !== null ? $last['grasa'] . ' %' : '—', 'bi-droplet-half', '#f59e0b', '#d97706', $dl($num($first['grasa']), $num($last['grasa']), 'pts')],
    ['Cintura', $last['cintura'] !== null ? $last['cintura'] . ' cm' : '—', 'bi-rulers', '#ec4899', '#db2777', $dl($num($first['cintura']), $num($last['cintura']), 'cm')],
  ] as $c): ?>
  <div class="col-6 col-xl-3"><div class="stat-card" style="--c1:<?= $c[3] ?>;--c2:<?= $c[4] ?>"><div class="stat-body">
    <div class="stat-val"><?= $c[1] ?></div><div class="stat-lbl"><?= $c[0] ?></div><div class="stat-delta"><span class="delta" style="background:rgba(255,255,255,.2)"><?= $c[5] ?></span></div><i class="bi <?= $c[2] ?> stat-ghost"></i></div></div></div>
  <?php endforeach; ?>
</div>
<div class="row g-3 mb-3">
  <div class="col-lg-6"><div class="card-x"><h6 class="sec-title"><i class="bi bi-graph-up"></i> Peso (kg) e IMC</h6><div style="height:250px"><canvas id="chPeso"></canvas></div></div></div>
  <div class="col-lg-6"><div class="card-x"><h6 class="sec-title"><i class="bi bi-bar-chart-line"></i> % de grasa y cintura</h6><div style="height:250px"><canvas id="chGrasa"></canvas></div></div></div>
</div>
<?php if ($n > 1): ?>
<div class="card-x mb-3"><h6 class="sec-title"><i class="bi bi-arrow-left-right"></i> Comparativo: primera vs. última</h6>
  <div class="table-responsive"><table class="table tbl align-middle mb-0"><thead><tr><th>Medida</th><th class="text-end"><?= fdate($first['fecha']) ?></th><th class="text-end"><?= fdate($last['fecha']) ?></th><th class="text-end">Cambio</th></tr></thead><tbody>
  <?php foreach ([['Peso', 'peso', 'kg', 'down'], ['% grasa', 'grasa', 'pts', 'down'], ['Cintura', 'cintura', 'cm', 'down'], ['Pecho', 'pecho', 'cm', 'up'], ['Brazo', 'brazo', 'cm', 'up'], ['Pierna', 'pierna', 'cm', 'up']] as $m):
    if ($first[$m[1]] === null && $last[$m[1]] === null) continue; ?>
    <tr><td class="fw-semibold"><?= $m[0] ?></td><td class="text-end"><?= $first[$m[1]] !== null ? $first[$m[1]] : '—' ?></td><td class="text-end"><?= $last[$m[1]] !== null ? $last[$m[1]] : '—' ?></td><td class="text-end fw-semibold"><?= $dl($num($first[$m[1]]), $num($last[$m[1]]), $m[2], $m[3]) ?></td></tr>
  <?php endforeach; ?></tbody></table></div></div>
<?php endif; ?>
<div class="card-x p-0"><div class="p-3 pb-0"><h6 class="sec-title"><i class="bi bi-list-ul"></i> Historial</h6></div>
  <div class="table-responsive"><table class="table tbl align-middle mb-0 tbl-pad"><thead><tr><th>Fecha</th><th class="text-end">Peso</th><th class="text-end">IMC</th><th class="text-end">% grasa</th><th class="text-end d-none d-md-table-cell">Cint./Pecho/Brazo/Pierna</th><th class="d-none d-lg-table-cell">Notas</th><th></th></tr></thead><tbody>
  <?php foreach (array_reverse($ev) as $e): $imc = Evaluacion::imc($num($e['peso']), $num($e['talla'])); ?>
    <tr><td><?= fdate($e['fecha']) ?></td><td class="text-end"><?= $e['peso'] !== null ? $e['peso'] : '—' ?></td><td class="text-end"><?= $imc ?? '—' ?></td><td class="text-end"><?= $e['grasa'] !== null ? $e['grasa'] : '—' ?></td>
      <td class="text-end d-none d-md-table-cell"><?= implode(' / ', array_map(fn($k) => $e[$k] !== null ? $e[$k] : '—', ['cintura', 'pecho', 'brazo', 'pierna'])) ?></td>
      <td class="d-none d-lg-table-cell small text-muted"><?= e($e['notas'] ?: '') ?></td>
      <td class="text-end"><form method="post" action="<?= url('evaluaciones/eliminar/' . $e['id']) ?>" data-confirm="¿Eliminar esta evaluación?"><?= csrf_field() ?><button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button></form></td></tr>
  <?php endforeach; ?></tbody></table></div></div>
<?php endif; ?>

<div class="modal fade" id="evModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content modal-x" method="post" action="<?= url('evaluaciones/guardar') ?>"><?= csrf_field() ?><input type="hidden" name="socio_id" value="<?= (int)$s['id'] ?>">
  <div class="modal-header border-0"><h5 class="modal-title fw-bold">Nueva evaluación</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><div class="row g-3">
    <div class="col-6"><label class="form-lbl">Fecha *</label><input type="date" name="fecha" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required></div>
    <div class="col-6"><label class="form-lbl">Talla (m)</label><input type="number" step="0.01" name="talla" class="form-control" placeholder="1.70" value="<?= $last && $last['talla'] ? e($last['talla']) : '' ?>"></div>
    <div class="col-6"><label class="form-lbl">Peso (kg)</label><input type="number" step="0.1" name="peso" class="form-control"></div>
    <div class="col-6"><label class="form-lbl">% de grasa</label><input type="number" step="0.1" name="grasa" class="form-control"></div>
    <div class="col-6"><label class="form-lbl">Cintura (cm)</label><input type="number" step="0.1" name="cintura" class="form-control"></div>
    <div class="col-6"><label class="form-lbl">Pecho (cm)</label><input type="number" step="0.1" name="pecho" class="form-control"></div>
    <div class="col-6"><label class="form-lbl">Brazo (cm)</label><input type="number" step="0.1" name="brazo" class="form-control"></div>
    <div class="col-6"><label class="form-lbl">Pierna (cm)</label><input type="number" step="0.1" name="pierna" class="form-control"></div>
    <div class="col-12"><label class="form-lbl">Notas</label><input name="notas" class="form-control" maxlength="255"></div>
  </div></div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand">Guardar</button></div></form></div></div>
<?php if ($ev): ?>
<script src="<?= ASSETS_URL ?>/vendor/chart.umd.min.js"></script>
<script>
window.addEventListener('DOMContentLoaded', function () {
  if (!window.Chart) return;
  var L = <?= json_encode($labels) ?>, P = <?= json_encode($serie('peso')) ?>, I = <?= json_encode($imcSerie) ?>, G = <?= json_encode($serie('grasa')) ?>, C = <?= json_encode($serie('cintura')) ?>;
  var dark = document.documentElement.getAttribute('data-theme') === 'dark', txt = dark ? '#94a3b8' : '#64748b', grid = dark ? 'rgba(255,255,255,.06)' : 'rgba(15,23,42,.06)';
  function ds(label, data, color, axis, dash) { return { label: label, data: data, borderColor: color, backgroundColor: color, tension: .35, spanGaps: true, pointRadius: 4, borderWidth: 3, yAxisID: axis, borderDash: dash || [] }; }
  function opts() { return { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, plugins: { legend: { labels: { color: txt, usePointStyle: true, boxWidth: 8 } } },
    scales: { x: { ticks: { color: txt }, grid: { display: false } }, y: { position: 'left', ticks: { color: txt }, grid: { color: grid } }, y1: { position: 'right', ticks: { color: txt }, grid: { display: false } } } }; }
  new Chart(document.getElementById('chPeso'), { type: 'line', data: { labels: L, datasets: [ds('Peso (kg)', P, '#6366f1', 'y'), ds('IMC', I, '#10b981', 'y1', [6, 4])] }, options: opts() });
  new Chart(document.getElementById('chGrasa'), { type: 'line', data: { labels: L, datasets: [ds('% grasa', G, '#f59e0b', 'y'), ds('Cintura (cm)', C, '#ec4899', 'y1')] }, options: opts() });
});
</script>
<?php endif; ?>
