<div class="page-head">
  <div><h1 class="page-title">Historial de ventas</h1><p class="page-sub">Ventas de productos del periodo seleccionado.</p></div>
  <a href="<?= url('ventas') ?>" class="btn btn-brand"><i class="bi bi-cart-plus"></i> Nueva venta</a>
</div>
<div class="row g-3 mb-3">
  <div class="col-12 col-xl-4"><div class="stat-card" style="--c1:#10b981;--c2:#059669"><div class="stat-body">
    <div class="stat-val"><?= money($tot['t']) ?></div><div class="stat-lbl">Vendido en el periodo</div><div class="stat-delta"><span class="delta"><?= (int)$tot['n'] ?> ventas</span></div><i class="bi bi-bag-check-fill stat-ghost"></i></div>
    <div class="stat-foot"><span><?= fdate($f['desde']) ?> – <?= fdate($f['hasta']) ?></span><i class="bi bi-calendar-range"></i></div></div></div>
  <div class="col-12 col-xl-8"><div class="card-x h-100 py-3"><div class="small fw-bold mb-2">Más vendidos</div>
    <?php if (!$top): ?><div class="text-muted small">Sin ventas en el periodo.</div><?php endif; ?>
    <?php $mx = max(1, (int)($top[0]['u'] ?? 1)); foreach ($top as $t): ?>
      <div class="d-flex align-items-center gap-2 mb-1 small"><span class="text-truncate" style="width:35%"><?= e($t['nombre']) ?></span>
        <div class="progress flex-grow-1" style="height:8px"><div class="progress-bar" style="width:<?= round($t['u'] / $mx * 100) ?>%"></div></div><b><?= (int)$t['u'] ?> u.</b><span class="text-muted"><?= money($t['t']) ?></span></div>
    <?php endforeach; ?></div></div>
</div>
<div class="card-x p-0">
  <form class="filter-bar" method="get" action="<?= url('ventas/historial') ?>">
    <div><label class="form-lbl mb-1 small">Desde</label><input type="date" name="desde" value="<?= e($f['desde']) ?>" class="form-control form-control-sm"></div>
    <div><label class="form-lbl mb-1 small">Hasta</label><input type="date" name="hasta" value="<?= e($f['hasta']) ?>" class="form-control form-control-sm"></div>
    <div><label class="form-lbl mb-1 small">Método</label><select name="metodo" class="form-select form-select-sm"><option value="">Todos</option>
      <?php foreach (Pago::metodos() as $k => $l): ?><option value="<?= $k ?>" <?= $f['metodo'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
    <div class="align-self-end d-flex gap-2"><button class="btn btn-soft btn-sm"><i class="bi bi-funnel"></i> Filtrar</button><a href="<?= url('ventas/historial') ?>" class="btn btn-outline-secondary btn-sm">Este mes</a></div>
  </form>
  <div class="table-responsive"><table class="table tbl align-middle mb-0 tbl-pad">
    <thead><tr><th>N°</th><th>Fecha</th><th class="d-none d-md-table-cell">Cliente</th><th class="text-center d-none d-md-table-cell">Unid.</th><th>Método</th><th class="text-end">Total</th><th></th></tr></thead><tbody>
    <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-5">No hay ventas en este periodo.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr><td class="fw-semibold"><?= sprintf('%06d', $r['id']) ?></td><td><?= fdate($r['fecha'], 'd/m/Y H:i') ?></td>
        <td class="d-none d-md-table-cell"><?= $r['nombres'] ? e($r['nombres'] . ' ' . $r['apellidos']) : '<span class="text-muted">Cliente libre</span>' ?></td>
        <td class="text-center d-none d-md-table-cell"><?= (int)$r['unidades'] ?></td><td><?= e(ucfirst($r['metodo'])) ?></td>
        <td class="text-end fw-bold"><?= money($r['total']) ?></td>
        <td class="text-end"><a class="btn btn-soft btn-sm" target="_blank" href="<?= url('ventas/ticket/' . $r['id']) ?>"><i class="bi bi-receipt"></i></a>
          <?php if (Permisos::esAdmin()): ?><button type="button" class="btn btn-sm btn-outline-danger" title="Anular venta" data-anular="<?= url('ventas/anular/' . $r['id']) ?>" data-volver="ventas/historial" data-titulo="Anular venta N° <?= sprintf('%06d', $r['id']) ?>" data-info="<?= e(money($r['total']) . ' · ' . (int)$r['unidades'] . ' unidad(es). El stock volverá al inventario.') ?>"><i class="bi bi-slash-circle"></i></button><?php endif; ?></td></tr>
    <?php endforeach; ?></tbody></table></div>
  <div class="p-3 d-flex justify-content-between align-items-center flex-wrap gap-2"><span class="small text-muted"><?= $pg['total'] ?> venta<?= $pg['total'] == 1 ? '' : 's' ?></span><?= pager($pg) ?></div>
</div>
<?php if (Permisos::esAdmin()) include ROOT_PATH . '/app/views/partials/acciones_modal.php'; ?>
