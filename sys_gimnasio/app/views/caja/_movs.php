<div class="table-responsive"><table class="table tbl align-middle mb-0 tbl-pad">
  <thead><tr><th>Hora</th><th>Concepto</th><th class="d-none d-md-table-cell">Usuario</th><th class="text-end">Monto</th></tr></thead><tbody>
  <?php if (!$mov): ?><tr><td colspan="4" class="text-center text-muted py-4">Sin movimientos manuales en este turno.</td></tr><?php endif; ?>
  <?php foreach ($mov as $m): ?><tr><td><?= fdate($m['fecha'], 'd/m H:i') ?></td><td><?= e($m['concepto']) ?></td><td class="d-none d-md-table-cell"><?= e($m['usuario'] ?: '—') ?></td>
    <td class="text-end fw-bold <?= $m['tipo'] === 'ingreso' ? 'text-success' : 'text-danger' ?>"><?= $m['tipo'] === 'ingreso' ? '+' : '−' ?><?= money($m['monto']) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
