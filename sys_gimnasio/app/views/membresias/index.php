<div class="page-head">
  <div><h1 class="page-title">Planes y membresías</h1><p class="page-sub">Controla vigencias, renovaciones y saldos de cada membresía.</p></div>
  <a href="<?= url('membresias/nueva') ?>" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Nueva membresía</a>
</div>
<?php $tab = 'mem'; require __DIR__ . '/_tabs.php'; ?>

<div class="card-x p-0">
  <div class="px-3 pt-3 d-flex flex-wrap gap-2">
    <?php foreach (['vigentes' => ['Vigentes', 'success'], 'porvencer' => ['Por vencer', 'warning'], 'vencidas' => ['Vencidas', 'danger'], 'canceladas' => ['Canceladas', 'secondary'], 'todas' => ['Todas', 'info']] as $k => [$l, $c]): ?>
      <a href="<?= url('membresias') . qs(['vista' => $k, 'p' => null]) ?>" class="chip <?= $f['vista'] === $k ? 'on ' . $c : '' ?>"><?= $l ?> <b><?= $cont[$k] ?? 0 ?></b></a>
    <?php endforeach; ?>
  </div>
  <form class="filter-bar" method="get" action="<?= url('membresias') ?>">
    <input type="hidden" name="vista" value="<?= e($f['vista']) ?>">
    <div class="input-ico flex-fill" style="min-width:220px"><i class="bi bi-search"></i>
      <input type="search" name="q" class="form-control" placeholder="Buscar socio…" value="<?= e($f['q']) ?>"></div>
    <select name="plan" class="form-select w-auto" onchange="this.form.submit()"><option value="">Todos los planes</option>
      <?php foreach ($planes as $p): ?><option value="<?= $p['id'] ?>" <?= $f['plan'] == $p['id'] ? 'selected' : '' ?>><?= e($p['nombre']) ?></option><?php endforeach; ?></select>
    <button class="btn btn-soft"><i class="bi bi-funnel"></i> Filtrar</button>
  </form>
  <div class="table-responsive">
    <table class="table tbl align-middle mb-0 tbl-pad">
      <thead><tr><th>Socio</th><th>Plan</th><th class="d-none d-md-table-cell">Periodo</th><th>Vence</th><th class="d-none d-lg-table-cell">Total / Pagado</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
      <tbody>
      <?php if (!$rows): ?><tr><td colspan="7"><div class="empty-state"><i class="bi bi-card-checklist"></i><b>No hay membresías en esta vista</b></div></td></tr><?php endif; ?>
      <?php foreach ($rows as $m): [$l, $c] = estado_membresia($m['fecha_inicio'], $m['fecha_fin'], $m['estado']); $dr = dias_restantes($m['fecha_fin']); $saldo = $m['total'] - $m['pagado']; ?>
        <tr>
          <td><a href="<?= url('socios/ver/' . $m['socio_id']) ?>" class="d-flex align-items-center gap-2 text-reset">
            <span class="avatar sm"><?= e(iniciales($m['nombres'], $m['apellidos'])) ?></span>
            <div class="lh-sm"><b><?= e($m['nombres'] . ' ' . $m['apellidos']) ?></b><br><small class="text-muted"><?= e($m['codigo']) ?></small></div></a></td>
          <td><span class="tag" style="--c:<?= e($m['plan_color']) ?>"><?= e($m['plan']) ?></span></td>
          <td class="d-none d-md-table-cell"><small><?= fdate($m['fecha_inicio']) ?> – <?= fdate($m['fecha_fin']) ?></small></td>
          <td><?= fdate($m['fecha_fin'], 'd/m/Y') ?><br><small class="<?= $dr !== null && $dr < 0 ? 'text-danger' : 'text-muted' ?>"><?= $m['estado'] === 'cancelada' ? '' : ($dr >= 0 ? ($dr === 0 ? 'vence hoy' : "en $dr d") : 'hace ' . abs($dr) . ' d') ?></small></td>
          <td class="d-none d-lg-table-cell"><?= money($m['total']) ?><br><small class="<?= $saldo > 0.009 && $m['estado'] !== 'cancelada' ? 'text-danger fw-semibold' : 'text-muted' ?>"><?= $saldo > 0.009 && $m['estado'] !== 'cancelada' ? 'debe ' . money($saldo) : 'pagado ' . money($m['pagado']) ?></small></td>
          <td><span class="badge-soft <?= $c ?>"><?= $l ?></span></td>
          <td class="text-end text-nowrap">
            <?php if ($m['estado'] !== 'cancelada'): ?>
              <a class="icon-sm" title="Renovar" href="<?= url('membresias/nueva?socio=' . $m['socio_id']) ?>"><i class="bi bi-arrow-repeat"></i></a>
              <?php if ($saldo > 0.009): ?><a class="icon-sm" title="Cobrar saldo" href="<?= url('pagos/nuevo?socio=' . $m['socio_id'] . '&membresia=' . $m['id']) ?>"><i class="bi bi-cash-coin"></i></a><?php endif; ?>
              <?php if ($m['telefono']): ?><a class="icon-sm wa" title="WhatsApp" target="_blank" rel="noopener" href="<?= e(wa_link($m['telefono'], 'Hola ' . $m['nombres'] . ', te escribimos de ' . APP_NAME . ' sobre tu membresía ' . $m['plan'] . ' (vence el ' . fdate($m['fecha_fin']) . ').')) ?>"><i class="bi bi-whatsapp"></i></a><?php endif; ?>
              <form method="post" action="<?= url('membresias/cancelar/' . $m['id']) ?>" class="d-inline" data-confirm="¿Cancelar la membresía de <?= e($m['nombres']) ?>? Los pagos registrados se conservan.">
                <?= csrf_field() ?><button class="icon-sm danger" title="Cancelar"><i class="bi bi-x-lg"></i></button></form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="table-foot"><small class="text-muted"><?= $pg['total'] ?> membresías</small><?= pager($pg) ?></div>
</div>
