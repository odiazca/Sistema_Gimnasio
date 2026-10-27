<div class="page-head">
  <div><h1 class="page-title"><?= $renueva ? 'Renovar membresía' : 'Nueva membresía' ?></h1>
    <p class="page-sub">Inscribe o renueva a un socio y registra su pago en el mismo paso.</p></div>
  <a href="<?= url('membresias') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
</div>
<form method="post" action="<?= url('membresias/guardar') ?>" id="memForm" novalidate>
  <?= csrf_field() ?>
  <div class="row g-4">
    <div class="col-xl-8">
      <div class="card-x mb-4">
        <h6 class="sec-title"><i class="bi bi-person"></i> 1. Socio</h6>
        <div class="ac" id="acSocio" data-url="<?= url('socios/buscar') ?>">
          <input type="hidden" name="socio_id" value="<?= $socio['id'] ?? '' ?>">
          <div class="input-ico"><i class="bi bi-search"></i>
            <input type="text" class="form-control" autocomplete="off" placeholder="Escribe nombre, DNI o código del socio…"
              value="<?= $socio ? e($socio['nombres'] . ' ' . $socio['apellidos'] . ' · ' . $socio['codigo']) : '' ?>"></div>
          <div class="ac-list d-none"></div>
        </div>
        <?php if ($renueva): ?><div class="alert alert-info mt-3 mb-0 py-2"><i class="bi bi-info-circle"></i> El socio tiene una membresía vigente; la nueva inicia el día siguiente a su vencimiento.</div><?php endif; ?>
        <div class="mt-2"><a href="<?= url('socios/crear') ?>" class="small link-brand"><i class="bi bi-person-plus"></i> ¿Socio nuevo? Regístralo aquí</a></div>
      </div>
      <div class="card-x mb-4">
        <h6 class="sec-title"><i class="bi bi-award"></i> 2. Plan</h6>
        <div class="plan-pick">
          <?php foreach ($planes as $p): ?>
            <label class="plan-opt" style="--c:<?= e($p['color']) ?>">
              <input type="radio" name="plan_id" value="<?= $p['id'] ?>" data-precio="<?= $p['precio'] ?>" data-dias="<?= (int)$p['duracion_dias'] ?>" data-nombre="<?= e($p['nombre']) ?>">
              <span><b><?= e($p['nombre']) ?></b><small><?= (int)$p['duracion_dias'] ?> días</small><em><?= money($p['precio']) ?></em></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="card-x">
        <h6 class="sec-title"><i class="bi bi-cash-stack"></i> 3. Fechas y pago</h6>
        <div class="row g-3">
          <div class="col-md-4"><label class="form-lbl">Fecha de inicio</label><input type="date" name="fecha_inicio" id="fIni" class="form-control" value="<?= e($inicio) ?>"></div>
          <div class="col-md-4"><label class="form-lbl">Descuento (<?= APP_CURRENCY ?>)</label><input type="number" step="0.01" min="0" name="descuento" id="fDesc" class="form-control" value="0"></div>
          <div class="col-md-4"><label class="form-lbl">Método de pago</label>
            <select name="metodo" class="form-select"><?php foreach (Pago::metodos() as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
          <div class="col-md-6"><label class="form-lbl">Monto que paga ahora (<?= APP_CURRENCY ?>)</label>
            <input type="number" step="0.01" min="0" name="monto_pago" id="fPago" class="form-control" value="0">
            <small class="text-muted">Si paga menos del total, el saldo queda como deuda pendiente.</small></div>
        </div>
      </div>
    </div>
    <div class="col-xl-4">
      <div class="card-x sticky-card">
        <h6 class="sec-title"><i class="bi bi-receipt"></i> Resumen</h6>
        <dl class="sum">
          <dt>Plan</dt><dd id="sPlan">—</dd>
          <dt>Inicio</dt><dd id="sIni">—</dd>
          <dt>Vence</dt><dd id="sFin">—</dd>
          <dt>Precio</dt><dd id="sPrecio">—</dd>
          <dt>Descuento</dt><dd id="sDesc">—</dd>
        </dl>
        <div class="sum-total"><span>Total</span><b id="sTotal"><?= money(0) ?></b></div>
        <div class="sum-line"><span>Paga ahora</span><b id="sPaga" class="text-success"><?= money(0) ?></b></div>
        <div class="sum-line"><span>Saldo</span><b id="sSaldo"><?= money(0) ?></b></div>
        <button class="btn btn-brand btn-lg w-100 mt-3"><i class="bi bi-check2-circle"></i> Confirmar membresía</button>
      </div>
    </div>
  </div>
</form>
<script>
window.addEventListener('DOMContentLoaded', function () {
  var cur = <?= json_encode(APP_CURRENCY) ?>, $ = function (i) { return document.getElementById(i); };
  var fm = function (n) { return window.fmtMoney(n); };
  var pagoTocado = false;
  function fmtDate(s) { if (!s) return '—'; var p = s.split('-'); return p[2] + '/' + p[1] + '/' + p[0]; }
  function calc() {
    var r = document.querySelector('input[name=plan_id]:checked');
    if (!r) return;
    var precio = parseFloat(r.dataset.precio), dias = parseInt(r.dataset.dias, 10);
    var desc = Math.min(Math.max(parseFloat($('fDesc').value || 0), 0), precio), total = precio - desc;
    var pago = Math.min(Math.max(parseFloat($('fPago').value || 0), 0), total);
    var ini = $('fIni').value, fin = '';
    if (ini) { var d = new Date(ini + 'T00:00:00'); d.setDate(d.getDate() + dias - 1); fin = d.toISOString().slice(0, 10); }
    $('sPlan').textContent = r.dataset.nombre; $('sIni').textContent = fmtDate(ini); $('sFin').textContent = fmtDate(fin);
    $('sPrecio').textContent = fm(precio); $('sDesc').textContent = '– ' + fm(desc);
    $('sTotal').textContent = fm(total); $('sPaga').textContent = fm(pago);
    $('sSaldo').textContent = fm(total - pago); $('sSaldo').className = (total - pago) > 0.009 ? 'text-danger' : '';
  }
  document.querySelectorAll('input[name=plan_id]').forEach(function (r) {
    r.addEventListener('change', function () {
      var precio = parseFloat(r.dataset.precio), desc = parseFloat($('fDesc').value || 0);
      if (!pagoTocado) $('fPago').value = Math.max(0, precio - desc).toFixed(2);
      calc();
    });
  });
  $('fPago').addEventListener('input', function () { pagoTocado = true; calc(); });
  $('fDesc').addEventListener('input', function () {
    var r = document.querySelector('input[name=plan_id]:checked');
    if (r && !pagoTocado) $('fPago').value = Math.max(0, parseFloat(r.dataset.precio) - parseFloat($('fDesc').value || 0)).toFixed(2);
    calc();
  });
  $('fIni').addEventListener('change', calc);
  $('memForm').addEventListener('submit', function (e) {
    if (!document.querySelector('input[name=socio_id]').value) { e.preventDefault(); alert('Selecciona un socio de la lista de resultados.'); }
    else if (!document.querySelector('input[name=plan_id]:checked')) { e.preventDefault(); alert('Selecciona un plan.'); }
  });
});
</script>
