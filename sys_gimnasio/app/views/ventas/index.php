<div class="page-head">
  <div><h1 class="page-title">Punto de venta</h1><p class="page-sub">Hoy: <b><?= (int)$hoy['n'] ?></b> venta<?= $hoy['n'] == 1 ? '' : 's' ?> · <b><?= money($hoy['t']) ?></b></p></div>
  <div class="d-flex gap-2"><a href="<?= url('ventas/historial') ?>" class="btn btn-outline-secondary"><i class="bi bi-clock-history"></i> Historial</a>
    <a href="<?= url('inventario') ?>" class="btn btn-outline-secondary"><i class="bi bi-box-seam"></i> Inventario</a></div>
</div>
<div class="row g-3 g-xl-4">
  <div class="col-lg-7 col-xl-8">
    <div class="card-x">
      <div class="input-ico mb-3"><i class="bi bi-search"></i><input type="search" id="posQ" class="form-control" placeholder="Buscar producto o escanear código… (Enter agrega)" autocomplete="off" autofocus></div>
      <div class="d-flex gap-2 flex-wrap mb-3" id="posCats"><button type="button" class="btn btn-sm btn-brand" data-cat="">Todos</button>
        <?php foreach ($cats as $c): ?><button type="button" class="btn btn-sm btn-outline-secondary" data-cat="<?= e($c) ?>"><?= e($c) ?></button><?php endforeach; ?></div>
      <div class="row g-2" id="posGrid"></div>
    </div>
  </div>
  <div class="col-lg-5 col-xl-4">
    <form class="card-x pos-cart" method="post" action="<?= url('ventas/cobrar') ?>" id="posForm"><?= csrf_field() ?>
      <input type="hidden" name="items" id="posItems">
      <h6 class="sec-title"><i class="bi bi-cart3"></i> Carrito</h6>
      <div id="posLines" class="mb-3"></div>
      <div class="ac mb-3" id="acSocio" data-url="<?= url('socios/buscar') ?>">
        <input type="hidden" name="socio_id">
        <div class="input-ico"><i class="bi bi-person"></i><input type="text" class="form-control form-control-sm" placeholder="Socio (opcional) — cliente libre" autocomplete="off"></div>
        <div class="ac-list d-none"></div>
      </div>
      <div class="mb-3"><label class="form-lbl">Método de pago</label><select name="metodo" class="form-select">
        <?php foreach (Pago::metodos() as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
      <div class="pos-total"><span>Total</span><b id="posTotal"><?= money(0) ?></b></div>
      <div id="posVuelto" class="mb-2 d-none">
        <div class="input-group input-group-sm"><span class="input-group-text">Paga con</span><input type="number" step="0.01" min="0" id="posPaga" class="form-control"><span class="input-group-text">Vuelto: <b class="ms-1" id="posVal">—</b></span></div>
      </div>
      <button class="btn btn-brand btn-lg w-100 mt-2" id="posGo" disabled><i class="bi bi-check2-circle"></i> Cobrar e imprimir ticket</button>
      <button type="button" class="btn btn-link btn-sm w-100 text-muted mt-1" id="posClear">Vaciar carrito</button>
    </form>
  </div>
</div>
<style>
.pos-prod{width:100%;text-align:left;border:1px solid var(--line);background:var(--card-2);border-radius:14px;padding:.8rem .9rem;color:var(--text);transition:.15s;height:100%}
.pos-prod:hover:not(:disabled){border-color:var(--brand);transform:translateY(-2px);box-shadow:0 8px 20px -12px var(--brand)}
.pos-prod:disabled{opacity:.45}.pos-prod b{display:block;font-size:.92rem;line-height:1.25}.pos-prod small{color:var(--muted)}
.pos-prod .pr{font:800 1.05rem 'Plus Jakarta Sans',sans-serif,sans-serif;color:var(--brand);margin-top:.3rem}
.pos-line{display:flex;align-items:center;gap:.6rem;padding:.55rem 0;border-bottom:1px dashed var(--line)}
.pos-line .n{flex:1;min-width:0;font-size:.88rem;font-weight:600}.pos-line .n small{display:block;color:var(--muted);font-weight:500}
.pos-qty{display:inline-flex;align-items:center;gap:.25rem}.pos-qty button{width:26px;height:26px;border-radius:8px;border:1px solid var(--line);background:var(--card-2);color:var(--text);line-height:1}
.pos-total{display:flex;justify-content:space-between;align-items:center;background:var(--brand-soft);border-radius:12px;padding:.8rem 1rem}.pos-total b{font:800 1.5rem 'Plus Jakarta Sans',sans-serif,sans-serif;color:var(--brand)}
@media(min-width:992px){.pos-cart{position:sticky;top:90px}}
</style>
<script>
window.addEventListener('DOMContentLoaded', function () {
  var P = <?= json_encode($prods, JSON_UNESCAPED_UNICODE) ?>, CUR = '<?= APP_CURRENCY ?>', cart = {}, cat = '';
  var $ = function (i) { return document.getElementById(i); };
  var fm = function (n) { return window.fmtMoney(n); };
  var esc = function (s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; };
  function grid() {
    var q = $('posQ').value.trim().toLowerCase(), h = '';
    P.forEach(function (p) {
      if (cat && p.categoria !== cat) return;
      if (q && p.nombre.toLowerCase().indexOf(q) < 0 && p.codigo.toLowerCase().indexOf(q) < 0) return;
      var inC = cart[p.id] || 0, left = p.stock - inC;
      h += '<div class="col-6 col-md-4 col-xl-3"><button type="button" class="pos-prod" data-id="' + p.id + '"' + (left <= 0 ? ' disabled' : '') + '><b>' + esc(p.nombre) + '</b><small>' + esc(p.codigo) + ' · ' + (left <= 0 ? 'Agotado' : left + ' disp.') + '</small><div class="pr">' + fm(p.precio) + '</div></button></div>';
    });
    $('posGrid').innerHTML = h || '<div class="col-12 text-muted text-center py-4">Sin resultados.</div>';
  }
  function lines() {
    var h = '', t = 0, n = 0;
    P.forEach(function (p) {
      var q = cart[p.id]; if (!q) return; t += q * p.precio; n++;
      h += '<div class="pos-line"><div class="n">' + esc(p.nombre) + '<small>' + fm(p.precio) + ' c/u</small></div><div class="pos-qty"><button type="button" data-m="' + p.id + '">−</button><b>' + q + '</b><button type="button" data-p="' + p.id + '">+</button></div><b style="width:70px;text-align:right">' + fm(q * p.precio) + '</b></div>';
    });
    $('posLines').innerHTML = h || '<div class="text-muted text-center py-4 small"><i class="bi bi-cart-x fs-3 d-block mb-1"></i>Agrega productos para vender.</div>';
    $('posTotal').textContent = fm(t); $('posGo').disabled = !n; window._t = t; vuelto(); grid();
  }
  function vuelto() {
    var ef = document.querySelector('[name=metodo]').value === 'efectivo' && window._t > 0; $('posVuelto').classList.toggle('d-none', !ef);
    var v = parseFloat($('posPaga').value); $('posVal').textContent = isNaN(v) ? '—' : (v >= window._t ? fm(v - window._t) : 'falta ' + fm(window._t - v));
  }
  function add(id, d) {
    var p = P.filter(function (x) { return x.id == id; })[0]; if (!p) return;
    var q = (cart[id] || 0) + d; if (q > p.stock) q = p.stock; if (q <= 0) delete cart[id]; else cart[id] = q; lines();
  }
  $('posGrid').addEventListener('click', function (e) { var b = e.target.closest('.pos-prod'); if (b) add(b.dataset.id, 1); });
  $('posLines').addEventListener('click', function (e) { var b = e.target.closest('button'); if (!b) return; if (b.dataset.p) add(b.dataset.p, 1); if (b.dataset.m) add(b.dataset.m, -1); });
  $('posQ').addEventListener('input', grid);
  $('posQ').addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') return; e.preventDefault(); var q = this.value.trim().toLowerCase(); if (!q) return;
    var m = P.filter(function (p) { return p.codigo.toLowerCase() === q; });
    if (!m.length) m = P.filter(function (p) { return p.nombre.toLowerCase().indexOf(q) >= 0; });
    if (m.length === 1 || (m.length && m[0].codigo.toLowerCase() === q)) { add(m[0].id, 1); this.value = ''; grid(); }
  });
  $('posCats').addEventListener('click', function (e) {
    var b = e.target.closest('button'); if (!b) return; cat = b.dataset.cat;
    this.querySelectorAll('button').forEach(function (x) { x.className = 'btn btn-sm ' + (x === b ? 'btn-brand' : 'btn-outline-secondary'); }); grid();
  });
  $('posClear').addEventListener('click', function () { cart = {}; lines(); });
  $('posPaga').addEventListener('input', vuelto); document.querySelector('[name=metodo]').addEventListener('change', vuelto);
  $('posForm').addEventListener('submit', function (e) {
    var a = []; for (var id in cart) a.push({ id: +id, q: cart[id] });
    if (!a.length) { e.preventDefault(); return; } $('posItems').value = JSON.stringify(a);
  });
  document.querySelector('#acSocio input[type=text]').addEventListener('input', function () { if (!this.value) document.querySelector('#acSocio input[type=hidden]').value = ''; });
  lines();
});
</script>
