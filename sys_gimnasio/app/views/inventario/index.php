<div class="page-head">
  <div><h1 class="page-title">Inventario</h1><p class="page-sub">Productos, stock y alertas de stock mínimo.</p></div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="<?= url('ventas') ?>" class="btn btn-outline-secondary"><i class="bi bi-cart"></i> Punto de venta</a>
    <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#prodModal" data-prod='{}'><i class="bi bi-plus-lg"></i> Nuevo producto</button>
  </div>
</div>
<div class="row g-3 mb-3 kpi-grid">
  <?php foreach ([
    ['Productos', (int)$res['total'], 'bi-box-seam-fill', '#6366f1', '#4f46e5', 'registrados'],
    ['Valor a costo', money($res['valor_costo']), 'bi-wallet2', '#10b981', '#059669', 'venta potencial ' . money($res['valor_venta'])],
    ['Stock bajo', (int)$res['bajo'], 'bi-exclamation-triangle-fill', '#f59e0b', '#d97706', 'por reponer'],
    ['Agotados', (int)$res['agotado'], 'bi-x-octagon-fill', '#ef4444', '#dc2626', 'sin stock'],
  ] as $k): ?>
  <div class="col-6 col-xl-3"><div class="stat-card" style="--c1:<?= $k[3] ?>;--c2:<?= $k[4] ?>"><div class="stat-body">
    <div class="stat-val"><?= $k[1] ?></div><div class="stat-lbl"><?= $k[0] ?></div><div class="stat-delta"><span class="delta"><?= $k[5] ?></span></div><i class="bi <?= $k[2] ?> stat-ghost"></i></div></div></div>
  <?php endforeach; ?>
</div>
<div class="card-x p-0">
  <form class="filter-bar" method="get" action="<?= url('inventario') ?>">
    <div class="flex-fill" style="min-width:180px"><label class="form-lbl mb-1 small">Buscar</label><input type="search" name="q" value="<?= e($f['q']) ?>" class="form-control form-control-sm" placeholder="Nombre o código"></div>
    <div><label class="form-lbl mb-1 small">Categoría</label><select name="cat" class="form-select form-select-sm"><option value="">Todas</option>
      <?php foreach ($cats as $c): ?><option <?= $f['cat'] === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
    <div><label class="form-lbl mb-1 small">Stock</label><select name="stock" class="form-select form-select-sm">
      <option value="">Todos</option><option value="bajo" <?= $f['stock'] === 'bajo' ? 'selected' : '' ?>>Stock bajo</option><option value="agotado" <?= $f['stock'] === 'agotado' ? 'selected' : '' ?>>Agotados</option></select></div>
    <div class="align-self-end d-flex gap-2"><button class="btn btn-soft btn-sm"><i class="bi bi-funnel"></i> Filtrar</button><a href="<?= url('inventario') ?>" class="btn btn-outline-secondary btn-sm">Limpiar</a></div>
  </form>
  <div class="table-responsive"><table class="table tbl align-middle mb-0 tbl-pad">
    <thead><tr><th>Producto</th><th class="d-none d-md-table-cell">Categoría</th><th class="text-end d-none d-lg-table-cell">Compra</th><th class="text-end">Venta</th><th class="text-center">Stock</th><th class="text-end">Acciones</th></tr></thead><tbody>
    <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-5">No hay productos con esos filtros.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): $cls = $r['stock'] <= 0 ? 'danger' : ($r['stock'] <= $r['stock_minimo'] ? 'warning' : 'success'); ?>
      <tr class="<?= $r['estado'] ? '' : 'opacity-50' ?>">
        <td><div class="fw-semibold"><?= e($r['nombre']) ?> <?= $r['estado'] ? '' : '<span class="badge-soft secondary">Inactivo</span>' ?></div><div class="small text-muted"><?= e($r['codigo']) ?></div></td>
        <td class="d-none d-md-table-cell"><?= e($r['categoria'] ?: '—') ?></td>
        <td class="text-end d-none d-lg-table-cell"><?= money($r['precio_compra']) ?></td>
        <td class="text-end fw-semibold"><?= money($r['precio_venta']) ?></td>
        <td class="text-center"><span class="badge-soft <?= $cls ?>"><?= (int)$r['stock'] ?></span><div class="small text-muted">mín. <?= (int)$r['stock_minimo'] ?></div></td>
        <td class="text-end text-nowrap">
          <button class="btn btn-soft btn-sm" title="Movimiento de stock" data-bs-toggle="modal" data-bs-target="#movModal" data-id="<?= $r['id'] ?>" data-nombre="<?= e($r['nombre']) ?>" data-stock="<?= (int)$r['stock'] ?>"><i class="bi bi-arrow-left-right"></i></button>
          <a class="btn btn-outline-secondary btn-sm" title="Kardex" href="<?= url('inventario/kardex/' . $r['id']) ?>"><i class="bi bi-journal-text"></i></a>
          <button class="btn btn-outline-secondary btn-sm" title="Editar" data-bs-toggle="modal" data-bs-target="#prodModal" data-prod='<?= e(json_encode(['id' => $r['id'], 'codigo' => $r['codigo'], 'nombre' => $r['nombre'], 'categoria' => $r['categoria'], 'precio_compra' => $r['precio_compra'], 'precio_venta' => $r['precio_venta'], 'stock_minimo' => $r['stock_minimo']])) ?>'><i class="bi bi-pencil"></i></button>
          <form method="post" action="<?= url('inventario/estado/' . $r['id']) ?>" class="d-inline"><?= csrf_field() ?><button class="btn btn-outline-secondary btn-sm" title="<?= $r['estado'] ? 'Desactivar' : 'Activar' ?>"><i class="bi <?= $r['estado'] ? 'bi-pause-circle' : 'bi-play-circle' ?>"></i></button></form>
          <form method="post" action="<?= url('inventario/eliminar/' . $r['id']) ?>" class="d-inline" data-confirm="¿Eliminar «<?= e($r['nombre']) ?>»?"><?= csrf_field() ?><button class="btn btn-outline-danger btn-sm" title="Eliminar"><i class="bi bi-trash"></i></button></form>
        </td></tr>
    <?php endforeach; ?></tbody></table></div>
  <div class="p-3 d-flex justify-content-between align-items-center flex-wrap gap-2"><span class="small text-muted"><?= $pg['total'] ?> producto<?= $pg['total'] == 1 ? '' : 's' ?></span><?= pager($pg) ?></div>
</div>

<div class="modal fade" id="prodModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered">
  <form class="modal-content modal-x" method="post" action="<?= url('inventario/guardar') ?>"><?= csrf_field() ?><input type="hidden" name="id" id="pId">
    <div class="modal-header border-0"><h5 class="modal-title fw-bold" id="pTitle">Nuevo producto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="row g-3">
      <div class="col-5"><label class="form-lbl">Código *</label><input name="codigo" id="pCod" class="form-control" required maxlength="20"></div>
      <div class="col-7"><label class="form-lbl">Categoría</label><input name="categoria" id="pCat" class="form-control" list="catList" maxlength="60"><datalist id="catList"><?php foreach ($cats as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist></div>
      <div class="col-12"><label class="form-lbl">Nombre *</label><input name="nombre" id="pNom" class="form-control" required maxlength="120"></div>
      <div class="col-6"><label class="form-lbl">Precio compra (<?= APP_CURRENCY ?>)</label><input type="number" step="0.01" min="0" name="precio_compra" id="pPc" class="form-control"></div>
      <div class="col-6"><label class="form-lbl">Precio venta (<?= APP_CURRENCY ?>) *</label><input type="number" step="0.01" min="0.01" name="precio_venta" id="pPv" class="form-control" required></div>
      <div class="col-6" id="pStockBox"><label class="form-lbl">Stock inicial</label><input type="number" min="0" name="stock" id="pStock" class="form-control" value="0"></div>
      <div class="col-6"><label class="form-lbl">Stock mínimo</label><input type="number" min="0" name="stock_minimo" id="pMin" class="form-control" value="5"></div>
    </div></div>
    <div class="modal-footer border-0"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand">Guardar</button></div>
  </form></div></div>

<div class="modal fade" id="movModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered">
  <form class="modal-content modal-x" method="post" id="movForm"><?= csrf_field() ?>
    <div class="modal-header border-0"><div><h5 class="modal-title fw-bold">Movimiento de stock</h5><div class="small text-muted" id="mvName"></div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="row g-3">
      <div class="col-12"><div class="seg w-100"><label class="flex-fill text-center"><input type="radio" name="tipo" value="entrada" checked class="d-none"><a>Entrada</a></label>
        <label class="flex-fill text-center"><input type="radio" name="tipo" value="salida" class="d-none"><a>Salida</a></label>
        <label class="flex-fill text-center"><input type="radio" name="tipo" value="ajuste" class="d-none"><a>Ajuste</a></label></div></div>
      <div class="col-12"><label class="form-lbl" id="mvLbl">Cantidad a ingresar</label><input type="number" min="0" name="cantidad" id="mvCant" class="form-control" required></div>
      <div class="col-12"><label class="form-lbl">Motivo</label><input name="motivo" class="form-control" maxlength="160" placeholder="Ej: Compra a proveedor, merma, conteo físico"></div>
      <div class="col-12 small text-muted" id="mvInfo"></div>
    </div></div>
    <div class="modal-footer border-0"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand">Registrar</button></div>
  </form></div></div>
<style>#movForm .seg a{display:block;cursor:pointer}#movForm label:has(input:checked) a{background:var(--brand);color:#fff}</style>
<script>
window.addEventListener('DOMContentLoaded', function () {
  var $ = function (i) { return document.getElementById(i); };
  $('prodModal').addEventListener('show.bs.modal', function (ev) {
    var d = {}; try { d = JSON.parse(ev.relatedTarget.getAttribute('data-prod') || '{}'); } catch (e) {}
    $('pTitle').textContent = d.id ? 'Editar producto' : 'Nuevo producto';
    $('pId').value = d.id || ''; $('pCod').value = d.codigo || '<?= e($nextCode) ?>'; $('pCat').value = d.categoria || ''; $('pNom').value = d.nombre || '';
    $('pPc').value = d.precio_compra || ''; $('pPv').value = d.precio_venta || ''; $('pMin').value = d.stock_minimo != null ? d.stock_minimo : 5;
    $('pStockBox').style.display = d.id ? 'none' : ''; $('pStock').value = 0;
  });
  var stock = 0;
  function upd() {
    var t = document.querySelector('#movForm input[name=tipo]:checked').value;
    $('mvLbl').textContent = t === 'entrada' ? 'Cantidad a ingresar' : (t === 'salida' ? 'Cantidad a retirar' : 'Nuevo stock (conteo real)');
    $('mvInfo').textContent = 'Stock actual: ' + stock;
    $('mvCant').min = t === 'ajuste' ? 0 : 1; if (t === 'salida') $('mvCant').max = stock; else $('mvCant').removeAttribute('max');
  }
  $('movModal').addEventListener('show.bs.modal', function (ev) {
    var b = ev.relatedTarget; stock = parseInt(b.dataset.stock, 10);
    $('movForm').action = '<?= url('inventario/ajustar') ?>/' + b.dataset.id; $('mvName').textContent = b.dataset.nombre; $('mvCant').value = ''; upd();
  });
  document.querySelectorAll('#movForm input[name=tipo]').forEach(function (r) { r.addEventListener('change', upd); });
});
</script>
