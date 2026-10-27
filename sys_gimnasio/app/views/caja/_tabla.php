<div class="table-responsive"><table class="table tbl align-middle mb-0 tbl-pad">
  <thead><tr><th>Turno</th><th>Apertura / cierre</th><th class="d-none d-md-table-cell">Usuario</th><th class="text-end">Esperado</th><th class="text-end">Contado</th><th class="text-end">Dif.</th><th></th></tr></thead><tbody>
  <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">Aún no hay cierres registrados.</td></tr><?php endif; ?>
  <?php foreach ($rows as $h): $d = (float)$h['diferencia']; ?>
    <tr><td class="fw-semibold">#<?= (int)$h['id'] ?></td><td><?= fdate($h['apertura'], 'd/m H:i') ?><div class="small text-muted"><?= fdate($h['cierre'], 'd/m H:i') ?></div></td>
      <td class="d-none d-md-table-cell"><?= e($h['usuario'] ?: '—') ?></td><td class="text-end"><?= money($h['esperado']) ?></td><td class="text-end"><?= money($h['monto_contado']) ?></td>
      <td class="text-end"><span class="badge-soft <?= abs($d) < 0.01 ? 'success' : 'danger' ?>"><?= abs($d) < 0.01 ? 'Cuadra' : ($d > 0 ? '+' : '−') . money(abs($d)) ?></span></td>
      <td class="text-end"><a class="btn btn-soft btn-sm" href="<?= url('caja/ver/' . $h['id']) ?>"><i class="bi bi-eye"></i></a></td></tr>
  <?php endforeach; ?></tbody></table></div>
