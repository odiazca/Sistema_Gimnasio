<div class="page-head">
  <div><h1 class="page-title">Caja</h1><p class="page-sub">Apertura y cierre por turno, movimientos y arqueo.</p></div>
  <div class="d-flex gap-2 flex-wrap"><a href="<?= url('caja/historial') ?>" class="btn btn-outline-secondary"><i class="bi bi-clock-history"></i> Historial de cierres</a>
    <a href="<?= url('gastos') ?>" class="btn btn-outline-secondary"><i class="bi bi-receipt-cutoff"></i> Gastos</a></div>
</div>
<?php if (!$c): ?>
<div class="row g-3 g-xl-4">
  <div class="col-lg-5"><form class="card-x" method="post" action="<?= url('caja/abrir') ?>"><?= csrf_field() ?>
    <div class="text-center mb-3"><div class="avatar-lg mx-auto mb-2" style="width:64px;height:64px"><i class="bi bi-lock-fill"></i></div><h5 class="fw-bold mb-1">La caja está cerrada</h5><div class="text-muted small">Abre un turno para registrar el efectivo inicial y controlar el arqueo.</div></div>
    <label class="form-lbl">Efectivo inicial (<?= APP_CURRENCY ?>)</label><input type="number" step="0.01" min="0" name="monto_inicial" class="form-control form-control-lg mb-3" value="0.00" required>
    <button class="btn btn-brand btn-lg w-100"><i class="bi bi-unlock-fill"></i> Abrir caja</button></form></div>
  <div class="col-lg-7"><div class="card-x p-0"><div class="p-3 pb-0"><h6 class="sec-title"><i class="bi bi-clock-history"></i> Últimos cierres</h6></div>
    <?php $rows = $hist; require __DIR__ . '/_tabla.php'; ?></div></div>
</div>
<?php else: ?>
<div class="card-x mb-3 d-flex flex-wrap align-items-center gap-3 justify-content-between">
  <div><span class="badge-soft success me-2"><i class="bi bi-circle-fill" style="font-size:.5rem"></i> Caja abierta</span>
    <b>Turno #<?= (int)$c['id'] ?></b> · desde <?= fdate($c['apertura'], 'd/m/Y H:i') ?> · <?= e($c['usuario'] ?: '—') ?></div>
  <div class="d-flex gap-2"><button class="btn btn-soft" data-bs-toggle="modal" data-bs-target="#movModal"><i class="bi bi-arrow-left-right"></i> Ingreso / egreso</button>
    <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#cierreModal"><i class="bi bi-lock"></i> Cerrar caja</button></div>
</div>
<?php require __DIR__ . '/_resumen.php'; ?>
<div class="card-x p-0"><div class="p-3 pb-0"><h6 class="sec-title"><i class="bi bi-list-ul"></i> Movimientos manuales del turno</h6></div><?php require __DIR__ . '/_movs.php'; ?></div>

<div class="modal fade" id="movModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content modal-x" method="post" action="<?= url('caja/movimiento') ?>"><?= csrf_field() ?>
  <div class="modal-header border-0"><h5 class="modal-title fw-bold">Movimiento de caja</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><div class="row g-3">
    <div class="col-12"><label class="form-lbl">Tipo</label><select name="tipo" class="form-select"><option value="egreso">Egreso (sale efectivo)</option><option value="ingreso">Ingreso (entra efectivo)</option></select></div>
    <div class="col-12"><label class="form-lbl">Concepto *</label><input name="concepto" class="form-control" required maxlength="150" placeholder="Ej: Pago a proveedor de agua, sencillo"></div>
    <div class="col-12"><label class="form-lbl">Monto (<?= APP_CURRENCY ?>) *</label><input type="number" step="0.01" min="0.01" name="monto" class="form-control" required></div>
  </div></div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand">Registrar</button></div></form></div></div>

<div class="modal fade" id="cierreModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content modal-x" method="post" action="<?= url('caja/cerrar') ?>"><?= csrf_field() ?>
  <div class="modal-header border-0"><h5 class="modal-title fw-bold">Cerrar caja · Arqueo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><div class="row g-3">
    <div class="col-12"><div class="pos-total" style="display:flex;justify-content:space-between;align-items:center;background:var(--brand-soft);border-radius:12px;padding:.8rem 1rem"><span>Efectivo esperado</span><b style="font-size:1.4rem;color:var(--brand)"><?= money($r['esperado']) ?></b></div></div>
    <div class="col-12"><label class="form-lbl">Efectivo contado (<?= APP_CURRENCY ?>) *</label><input type="number" step="0.01" min="0" name="contado" id="cContado" class="form-control form-control-lg" required></div>
    <div class="col-12"><div id="cDif" class="small fw-semibold"></div></div>
    <div class="col-12"><label class="form-lbl">Observación <span class="text-muted fw-normal">(obligatoria si hay diferencia)</span></label><input name="observacion" id="cObs" class="form-control" maxlength="255"></div>
  </div></div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand">Cerrar caja</button></div></form></div></div>
<script>
window.addEventListener('DOMContentLoaded', function () {
  var esp = <?= json_encode(round($r['esperado'], 2)) ?>, cur = '<?= APP_CURRENCY ?>', i = document.getElementById('cContado'), o = document.getElementById('cDif');
  i.addEventListener('input', function () {
    var v = parseFloat(i.value); if (isNaN(v)) { o.textContent = ''; return; } var d = Math.round((v - esp) * 100) / 100;
    o.className = 'small fw-semibold ' + (d === 0 ? 'text-success' : 'text-danger');
    o.textContent = d === 0 ? 'Cuadre perfecto ✓' : (d > 0 ? 'Sobrante de ' : 'Faltante de ') + window.fmtMoney(Math.abs(d));
  });
});
</script>
<?php endif; ?>
