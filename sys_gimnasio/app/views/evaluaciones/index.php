<div class="page-head">
  <div><h1 class="page-title">Evaluaciones físicas</h1><p class="page-sub">Peso, IMC, % de grasa y medidas; seguimiento del progreso de cada socio.</p></div>
</div>
<div class="row g-3 mb-3 kpi-grid">
  <?php foreach ([
    ['Evaluaciones totales', (int)$k['total'], 'bi-clipboard2-data-fill', '#6366f1', '#4f46e5', (int)$k['socios'] . ' socios evaluados'],
    ['Este mes', (int)$k['mes'], 'bi-calendar-check-fill', '#10b981', '#059669', 'realizadas'],
    ['Sin evaluar (90 días)', $sin, 'bi-hourglass-split', '#f59e0b', '#d97706', 'socios activos'],
  ] as $c): ?>
  <div class="col-12 col-md-4"><div class="stat-card" style="--c1:<?= $c[3] ?>;--c2:<?= $c[4] ?>"><div class="stat-body">
    <div class="stat-val"><?= $c[1] ?></div><div class="stat-lbl"><?= $c[0] ?></div><div class="stat-delta"><span class="delta"><?= $c[5] ?></span></div><i class="bi <?= $c[2] ?> stat-ghost"></i></div></div></div>
  <?php endforeach; ?>
</div>
<div class="row g-3 g-xl-4">
  <div class="col-lg-4"><div class="card-x"><h6 class="sec-title"><i class="bi bi-person-plus"></i> Nueva evaluación</h6>
    <p class="small text-muted">Busca al socio para registrar o consultar sus evaluaciones.</p>
    <div class="ac" id="acSocio" data-url="<?= url('socios/buscar') ?>" data-onpick="irSocio"><input type="hidden" name="socio_id">
      <div class="input-ico"><i class="bi bi-search"></i><input type="text" class="form-control" autocomplete="off" placeholder="Nombre, DNI o código…" autofocus></div><div class="ac-list d-none"></div></div></div></div>
  <div class="col-lg-8"><div class="card-x p-0"><div class="p-3 pb-0"><h6 class="sec-title"><i class="bi bi-clock-history"></i> Últimas evaluaciones</h6></div>
    <div class="table-responsive"><table class="table tbl align-middle mb-0 tbl-pad"><thead><tr><th>Fecha</th><th>Socio</th><th class="text-end">Peso</th><th class="text-center">IMC</th><th class="text-end d-none d-md-table-cell">% grasa</th><th></th></tr></thead><tbody>
    <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-5">Aún no hay evaluaciones registradas.</td></tr><?php endif; ?>
    <?php foreach ($rows as $e): $imc = Evaluacion::imc($e['peso'] !== null ? (float)$e['peso'] : null, $e['talla'] !== null ? (float)$e['talla'] : null); $cat = Evaluacion::categoria($imc); ?>
      <tr><td><?= fdate($e['fecha']) ?></td><td><span class="fw-semibold"><?= e($e['nombres'] . ' ' . $e['apellidos']) ?></span><div class="small text-muted"><?= e($e['codigo']) ?></div></td>
        <td class="text-end"><?= $e['peso'] !== null ? number_format($e['peso'], 1) . ' kg' : '—' ?></td>
        <td class="text-center"><?php if ($imc !== null): ?><b><?= $imc ?></b> <span class="badge-soft <?= $cat[1] ?>"><?= $cat[0] ?></span><?php else: ?>—<?php endif; ?></td>
        <td class="text-end d-none d-md-table-cell"><?= $e['grasa'] !== null ? $e['grasa'] . ' %' : '—' ?></td>
        <td class="text-end"><a class="btn btn-soft btn-sm" href="<?= url('evaluaciones/socio/' . $e['socio_id']) ?>"><i class="bi bi-graph-up"></i></a></td></tr>
    <?php endforeach; ?></tbody></table></div></div></div>
</div>
<script>
window.irSocio = function (s) { if (s && s.id) location.href = '<?= url('evaluaciones/socio') ?>/' + s.id; };
</script>
