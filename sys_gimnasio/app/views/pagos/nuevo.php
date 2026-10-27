<div class="page-head">
  <div><h1 class="page-title">Registrar pago</h1><p class="page-sub">Cobra una membresía (total o parcial) u otro concepto y emite el recibo.</p></div>
  <a href="<?= url('pagos') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
</div>
<form method="post" action="<?= url('pagos/guardar') ?>" id="pagoForm" novalidate>
  <?= csrf_field() ?>
  <div class="row g-4">
    <div class="col-xl-8">
      <div class="card-x mb-4">
        <h6 class="sec-title"><i class="bi bi-person"></i> 1. Socio</h6>
        <div class="ac" id="acSocio" data-url="<?= url('socios/buscar') ?>" data-onpick="loadPend">
          <input type="hidden" name="socio_id" value="<?= $socio['id'] ?? '' ?>">
          <div class="input-ico"><i class="bi bi-search"></i>
            <input type="text" class="form-control" autocomplete="off" placeholder="Escribe nombre, DNI o código del socio…"
              value="<?= $socio ? e($socio['nombres'] . ' ' . $socio['apellidos'] . ' · ' . $socio['codigo']) : '' ?>"></div>
          <div class="ac-list d-none"></div>
        </div>
      </div>
      <div class="card-x mb-4">
        <h6 class="sec-title"><i class="bi bi-card-checklist"></i> 2. ¿Qué se está pagando?</h6>
        <div id="pendBox" data-url="<?= url('pagos/pendientes') ?>">
          <label class="pend-opt"><input type="radio" name="membresia_id" value="" <?= empty($pend) ? 'checked' : '' ?>>
            <span><b>Otro concepto</b><small>Pase diario, producto u otro cobro sin membresía</small></span></label>
          <?php foreach ($pend as $m): $saldo = $m['total'] - $m['pagado']; ?>
            <label class="pend-opt"><input type="radio" name="membresia_id" value="<?= $m['id'] ?>" data-saldo="<?= round($saldo, 2) ?>" <?= $sel == $m['id'] ? 'checked' : '' ?>>
              <span><b>Membresía <?= e($m['plan']) ?></b><small><?= fdate($m['fecha_inicio']) ?> – <?= fdate($m['fecha_fin']) ?> · Total <?= money($m['total']) ?> · Pagado <?= money($m['pagado']) ?></small></span>
              <em class="text-danger">Saldo <?= money($saldo) ?></em></label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="card-x">
        <h6 class="sec-title"><i class="bi bi-cash-stack"></i> 3. Detalle del cobro</h6>
        <div class="row g-3">
          <div class="col-md-4"><label class="form-lbl">Monto (<?= APP_CURRENCY ?>) *</label><input type="number" step="0.01" min="0.01" name="monto" id="pMonto" class="form-control form-control-lg" required></div>
          <div class="col-md-4"><label class="form-lbl">Método de pago</label>
            <select name="metodo" class="form-select form-select-lg"><?php foreach (Pago::metodos() as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
          <div class="col-md-4"><label class="form-lbl">Concepto (opcional)</label><input name="concepto" class="form-control form-control-lg" maxlength="150" placeholder="Ej: Pase diario"></div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-4 align-items-center">
          <button class="btn btn-brand btn-lg"><i class="bi bi-check2-circle"></i> Registrar y emitir recibo</button>
          <button type="button" class="btn btn-soft" id="btnSaldo">Cobrar saldo completo</button>
        </div>
      </div>
    </div>
    <div class="col-xl-4">
      <div class="card-x text-center py-5 text-muted"><i class="bi bi-receipt-cutoff display-4 text-brand"></i>
        <p class="mt-3 mb-0">Al confirmar se genera un recibo imprimible con el detalle del pago y el saldo restante.</p></div>
    </div>
  </div>
</form>
<script>
window.addEventListener('DOMContentLoaded', function () {
  var cur = <?= json_encode(APP_CURRENCY) ?>, box = document.getElementById('pendBox'), monto = document.getElementById('pMonto');
  var fm = function (n) { return window.fmtMoney(n); };
  function selSaldo() { var r = document.querySelector('input[name=membresia_id]:checked'); return r && r.dataset.saldo ? parseFloat(r.dataset.saldo) : null; }
  function applySaldo() { var s = selSaldo(); if (s !== null) { monto.max = s; if (!monto.value || parseFloat(monto.value) > s) monto.value = s.toFixed(2); } else { monto.removeAttribute('max'); } }
  box.addEventListener('change', applySaldo);
  document.getElementById('btnSaldo').addEventListener('click', function () { var s = selSaldo(); if (s !== null) monto.value = s.toFixed(2); else monto.focus(); });
  applySaldo();

  window.loadPend = function (socio) {
    fetch(box.dataset.url + '/' + socio.id, { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (rows) {
      var h = '<label class="pend-opt"><input type="radio" name="membresia_id" value="" ' + (rows.length ? '' : 'checked') + '><span><b>Otro concepto</b><small>Pase diario, producto u otro cobro sin membresía</small></span></label>';
      rows.forEach(function (m, i) {
        h += '<label class="pend-opt"><input type="radio" name="membresia_id" value="' + m.id + '" data-saldo="' + m.saldo + '" ' + (i === 0 ? 'checked' : '') + '><span><b>Membresía ' + m.plan + '</b><small>' + m.inicio + ' – ' + m.fin + ' · Total ' + fm(m.total) + ' · Pagado ' + fm(m.pagado) + '</small></span><em class="text-danger">Saldo ' + fm(m.saldo) + '</em></label>';
      });
      box.innerHTML = h; monto.value = ''; applySaldo();
    });
  };
  document.getElementById('pagoForm').addEventListener('submit', function (e) {
    if (!document.querySelector('input[name=socio_id]').value) { e.preventDefault(); alert('Selecciona un socio de la lista de resultados.'); }
  });
});
</script>
