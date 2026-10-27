<div class="page-head"><div><a href="<?= url('caja/historial') ?>" class="small text-muted text-decoration-none"><i class="bi bi-arrow-left"></i> Historial de cierres</a>
  <h1 class="page-title mt-1">Turno #<?= (int)$c['id'] ?></h1>
  <p class="page-sub"><?= fdate($c['apertura'], 'd/m/Y H:i') ?> → <?= $c['cierre'] ? fdate($c['cierre'], 'd/m/Y H:i') : 'en curso' ?> · <?= e($c['usuario'] ?: '—') ?></p></div></div>
<?php if ($c['estado'] === 'cerrada'): $d = (float)$c['diferencia']; ?>
<div class="card-x mb-3"><div class="row text-center g-3">
  <div class="col-4"><div class="text-muted small">Esperado</div><div class="fs-4 fw-bold"><?= money($c['esperado']) ?></div></div>
  <div class="col-4"><div class="text-muted small">Contado</div><div class="fs-4 fw-bold"><?= money($c['monto_contado']) ?></div></div>
  <div class="col-4"><div class="text-muted small">Diferencia</div><div class="fs-4 fw-bold <?= abs($d) < 0.01 ? 'text-success' : 'text-danger' ?>"><?= abs($d) < 0.01 ? 'Cuadra' : ($d > 0 ? '+' : '−') . money(abs($d)) ?></div></div></div>
  <?php if ($c['observacion']): ?><hr><div class="small"><b>Observación:</b> <?= e($c['observacion']) ?></div><?php endif; ?></div>
<?php endif; ?>
<?php require __DIR__ . '/_resumen.php'; ?>
<div class="card-x p-0"><div class="p-3 pb-0"><h6 class="sec-title"><i class="bi bi-list-ul"></i> Movimientos manuales</h6></div><?php require __DIR__ . '/_movs.php'; ?></div>
