<div class="page-head">
  <div><a href="<?= url('inventario') ?>" class="small text-muted text-decoration-none"><i class="bi bi-arrow-left"></i> Volver al inventario</a>
    <h1 class="page-title mt-1">Kardex · <?= e($p['nombre']) ?></h1><p class="page-sub"><?= e($p['codigo']) ?> · Stock actual <b><?= (int)$p['stock'] ?></b> (mínimo <?= (int)$p['stock_minimo'] ?>)</p></div>
</div>
<div class="card-x p-0"><div class="table-responsive"><table class="table tbl align-middle mb-0 tbl-pad">
  <thead><tr><th>Fecha</th><th>Tipo</th><th class="text-end">Cantidad</th><th class="text-end">Stock final</th><th>Motivo</th><th class="d-none d-md-table-cell">Usuario</th></tr></thead><tbody>
  <?php if (!$mov): ?><tr><td colspan="6" class="text-center text-muted py-5">Sin movimientos registrados todavía.</td></tr><?php endif; ?>
  <?php foreach ($mov as $m): $sal = in_array($m['tipo'], ['salida', 'venta'], true) || ($m['tipo'] === 'ajuste' && $m['cantidad'] < 0);
    $lbl = ['entrada' => 'Entrada', 'salida' => 'Salida', 'ajuste' => 'Ajuste', 'venta' => 'Venta', 'inicial' => 'Stock inicial'][$m['tipo']]; ?>
    <tr><td><?= fdate($m['fecha'], 'd/m/Y H:i') ?></td><td><span class="badge-soft <?= $sal ? 'danger' : 'success' ?>"><?= $lbl ?></span></td>
      <td class="text-end fw-semibold <?= $sal ? 'text-danger' : 'text-success' ?>"><?= $sal ? '−' : '+' ?><?= abs((int)$m['cantidad']) ?></td>
      <td class="text-end"><?= (int)$m['stock_final'] ?></td><td><?= e($m['motivo'] ?: '—') ?></td><td class="d-none d-md-table-cell"><?= e($m['usuario'] ?: '—') ?></td></tr>
  <?php endforeach; ?></tbody></table></div></div>
<p class="small text-muted mt-2">Se muestran los últimos 60 movimientos registrados desde la activación del kardex.</p>
