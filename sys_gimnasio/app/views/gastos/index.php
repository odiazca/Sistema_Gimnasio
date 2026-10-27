<?php $util = $ing - $tot['t']; ?>
<div class="page-head">
  <div><h1 class="page-title">Gastos</h1><p class="page-sub">Gastos operativos, fijos y de personal; resultado del periodo.</p></div>
  <div class="d-flex gap-2"><a href="<?= url('caja') ?>" class="btn btn-outline-secondary"><i class="bi bi-cash-coin"></i> Caja</a>
    <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#gasModal" data-gas='{}'><i class="bi bi-plus-lg"></i> Registrar gasto</button></div>
</div>
<div class="row g-3 mb-3 kpi-grid">
  <?php foreach ([
    ['Gastos del periodo', money($tot['t']), 'bi-receipt-cutoff', '#ef4444', '#dc2626', (int)$tot['n'] . ' registros'],
    ['Ingresos del periodo', money($ing), 'bi-graph-up-arrow', '#10b981', '#059669', 'membresías + tienda'],
    [$util >= 0 ? 'Utilidad' : 'Pérdida', money(abs($util)), 'bi-piggy-bank-fill', $util >= 0 ? '#6366f1' : '#f59e0b', $util >= 0 ? '#4f46e5' : '#d97706', 'ingresos − gastos'],
  ] as $k): ?>
  <div class="col-12 col-md-4"><div class="stat-card" style="--c1:<?= $k[3] ?>;--c2:<?= $k[4] ?>"><div class="stat-body">
    <div class="stat-val"><?= $k[1] ?></div><div class="stat-lbl"><?= $k[0] ?></div><div class="stat-delta"><span class="delta"><?= $k[5] ?></span></div><i class="bi <?= $k[2] ?> stat-ghost"></i></div>
    <div class="stat-foot"><span><?= fdate($f['desde']) ?> – <?= fdate($f['hasta']) ?></span><i class="bi bi-calendar-range"></i></div></div></div>
  <?php endforeach; ?>
</div>
<div class="row g-3">
  <div class="col-xl-4"><div class="card-x h-100"><h6 class="sec-title"><i class="bi bi-pie-chart"></i> Por categoría</h6>
    <?php if (!$porCat): ?><div class="text-muted small">Sin gastos en el periodo.</div><?php endif; ?>
    <?php foreach ($porCat as $c): $pc = $tot['t'] > 0 ? round($c['t'] / $tot['t'] * 100) : 0; ?>
      <div class="mb-3"><div class="d-flex justify-content-between small"><span class="fw-semibold"><?= e($c['cat']) ?></span><span><?= money($c['t']) ?> · <?= $pc ?>%</span></div>
        <div class="progress mt-1" style="height:7px"><div class="progress-bar" style="width:<?= $pc ?>%"></div></div></div>
    <?php endforeach; ?></div></div>
  <div class="col-xl-8"><div class="card-x p-0">
    <form class="filter-bar" method="get" action="<?= url('gastos') ?>">
      <div><label class="form-lbl mb-1 small">Desde</label><input type="date" name="desde" value="<?= e($f['desde']) ?>" class="form-control form-control-sm"></div>
      <div><label class="form-lbl mb-1 small">Hasta</label><input type="date" name="hasta" value="<?= e($f['hasta']) ?>" class="form-control form-control-sm"></div>
      <div><label class="form-lbl mb-1 small">Categoría</label><select name="cat" class="form-select form-select-sm"><option value="">Todas</option>
        <?php foreach ($cats as $c): ?><option <?= $f['cat'] === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
      <div class="flex-fill" style="min-width:140px"><label class="form-lbl mb-1 small">Concepto</label><input type="search" name="q" value="<?= e($f['q']) ?>" class="form-control form-control-sm"></div>
      <div class="align-self-end d-flex gap-2"><button class="btn btn-soft btn-sm"><i class="bi bi-funnel"></i> Filtrar</button><a href="<?= url('gastos') ?>" class="btn btn-outline-secondary btn-sm">Este mes</a></div>
    </form>
    <div class="table-responsive"><table class="table tbl align-middle mb-0 tbl-pad">
      <thead><tr><th>Fecha</th><th>Concepto</th><th class="d-none d-md-table-cell">Categoría</th><th class="text-end">Monto</th><th></th></tr></thead><tbody>
      <?php if (!$rows): ?><tr><td colspan="5" class="text-center text-muted py-5">No hay gastos en este periodo.</td></tr><?php endif; ?>
      <?php foreach ($rows as $g): ?>
        <tr><td><?= fdate($g['fecha']) ?></td><td class="fw-semibold"><?= e($g['concepto']) ?></td><td class="d-none d-md-table-cell"><span class="badge-soft secondary"><?= e($g['categoria'] ?: '—') ?></span></td>
          <td class="text-end fw-bold text-danger"><?= money($g['monto']) ?></td>
          <td class="text-end text-nowrap">
            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#gasModal" data-gas='<?= e(json_encode(['id' => $g['id'], 'concepto' => $g['concepto'], 'categoria' => $g['categoria'], 'monto' => $g['monto'], 'fecha' => $g['fecha']])) ?>'><i class="bi bi-pencil"></i></button>
            <form method="post" action="<?= url('gastos/eliminar/' . $g['id']) ?>" class="d-inline" data-confirm="¿Eliminar este gasto?"><?= csrf_field() ?><button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button></form></td></tr>
      <?php endforeach; ?></tbody></table></div>
    <div class="p-3 d-flex justify-content-between align-items-center flex-wrap gap-2"><span class="small text-muted"><?= $pg['total'] ?> gasto<?= $pg['total'] == 1 ? '' : 's' ?></span><?= pager($pg) ?></div>
  </div></div>
</div>

<div class="modal fade" id="gasModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content modal-x" method="post" action="<?= url('gastos/guardar') ?>"><?= csrf_field() ?><input type="hidden" name="id" id="gId">
  <div class="modal-header border-0"><h5 class="modal-title fw-bold" id="gTitle">Registrar gasto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><div class="row g-3">
    <div class="col-12"><label class="form-lbl">Concepto *</label><input name="concepto" id="gCon" class="form-control" required maxlength="150"></div>
    <div class="col-6"><label class="form-lbl">Categoría</label><input name="categoria" id="gCat" class="form-control" list="gCats" maxlength="60"><datalist id="gCats"><?php foreach ($cats as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist></div>
    <div class="col-6"><label class="form-lbl">Monto (<?= APP_CURRENCY ?>) *</label><input type="number" step="0.01" min="0.01" name="monto" id="gMon" class="form-control" required></div>
    <div class="col-12"><label class="form-lbl">Fecha *</label><input type="date" name="fecha" id="gFec" class="form-control" max="<?= date('Y-m-d') ?>" required></div>
    <?php if ($cajaAbierta): ?><div class="col-12" id="gCajaBox"><div class="form-check"><input class="form-check-input" type="checkbox" name="de_caja" value="1" id="gCaja"><label class="form-check-label small" for="gCaja">Pagado en efectivo desde la caja abierta (se registra como egreso)</label></div></div><?php endif; ?>
  </div></div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand">Guardar</button></div></form></div></div>
<script>
window.addEventListener('DOMContentLoaded', function () {
  var $ = function (i) { return document.getElementById(i); };
  $('gasModal').addEventListener('show.bs.modal', function (ev) {
    var d = {}; try { d = JSON.parse(ev.relatedTarget.getAttribute('data-gas') || '{}'); } catch (e) {}
    $('gTitle').textContent = d.id ? 'Editar gasto' : 'Registrar gasto';
    $('gId').value = d.id || ''; $('gCon').value = d.concepto || ''; $('gCat').value = d.categoria || ''; $('gMon').value = d.monto || ''; $('gFec').value = d.fecha || '<?= date('Y-m-d') ?>';
    if ($('gCajaBox')) { $('gCajaBox').style.display = d.id ? 'none' : ''; $('gCaja').checked = false; }
  });
});
</script>
