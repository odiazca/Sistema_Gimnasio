<div class="page-head"><div><a href="<?= url('caja') ?>" class="small text-muted text-decoration-none"><i class="bi bi-arrow-left"></i> Volver a caja</a><h1 class="page-title mt-1">Historial de cierres</h1><p class="page-sub">Arqueos y diferencias por turno.</p></div></div>
<div class="card-x p-0"><?php require __DIR__ . '/_tabla.php'; ?>
  <div class="p-3 d-flex justify-content-between align-items-center"><span class="small text-muted"><?= $pg['total'] ?> cierre<?= $pg['total'] == 1 ? '' : 's' ?></span><?= pager($pg) ?></div></div>
