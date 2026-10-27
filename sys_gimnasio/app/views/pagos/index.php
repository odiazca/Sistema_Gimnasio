<?php $metIc = ['efectivo' => 'bi-cash', 'tarjeta' => 'bi-credit-card', 'yape' => 'bi-phone', 'plin' => 'bi-phone', 'transferencia' => 'bi-bank']; ?>
<div class="page-head">
  <div><h1 class="page-title">Pagos y cobranzas</h1><p class="page-sub">Registra cobros, emite recibos y da seguimiento a las deudas.</p></div>
  <a href="<?= url('pagos/nuevo') ?>" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Registrar pago</a>
</div>

<div class="seg mb-3">
  <a href="<?= url('pagos') ?>" class="<?= $vista === 'cobros' ? 'on' : '' ?>"><i class="bi bi-cash-stack"></i> Cobros</a>
  <a href="<?= url('pagos?vista=deudas') ?>" class="<?= $vista === 'deudas' ? 'on' : '' ?>"><i class="bi bi-exclamation-triangle-fill"></i> Deudas pendientes <?php if ($n_deudas): ?><span class="cnt bad"><?= $n_deudas ?></span><?php endif; ?></a>
</div>

<?php if ($vista === 'cobros'): ?>
<div class="row g-3 mb-3 kpi-grid">
  <div class="col-12 col-xl-4"><div class="stat-card" style="--c1:#10b981;--c2:#059669"><div class="stat-body">
    <div class="stat-val"><?= money($tot['total']) ?></div><div class="stat-lbl">Total cobrado en el periodo</div>
    <div class="stat-delta"><span class="delta"><?= $tot['n'] ?> pagos</span></div><i class="bi bi-wallet2 stat-ghost"></i></div>
    <div class="stat-foot"><span><?= fdate($f['desde']) ?> – <?= fdate($f['hasta']) ?></span><i class="bi bi-calendar-range"></i></div></div></div>
  <div class="col-12 col-xl-8"><div class="card-x h-100 py-3">
    <div class="small fw-bold mb-2">Por método de pago</div>
    <div class="row g-2">
      <?php foreach (Pago::metodos() as $k => $l): $t = $tot['metodos'][$k] ?? 0; $pc = $tot['total'] > 0 ? round($t / $tot['total'] * 100) : 0; ?>
        <div class="col-6 col-md"><div class="met-box"><i class="bi <?= $metIc[$k] ?>"></i><b><?= money($t) ?></b><span><?= $l ?> · <?= $pc ?>%</span>
          <div class="progress-x"><span style="width:<?= $pc ?>%"></span></div></div></div>
      <?php endforeach; ?>
    </div></div></div>
</div>

<div class="card-x p-0">
  <form class="filter-bar" method="get" action="<?= url('pagos') ?>">
    <div><label class="form-lbl mb-1 small">Desde</label><input type="date" name="desde" value="<?= e($f['desde']) ?>" class="form-control form-control-sm"></div>
    <div><label class="form-lbl mb-1 small">Hasta</label><input type="date" name="hasta" value="<?= e($f['hasta']) ?>" class="form-control form-control-sm"></div>
    <div><label class="form-lbl mb-1 small">Método</label><select name="metodo" class="form-select form-select-sm"><option value="">Todos</option>
      <?php foreach (Pago::metodos() as $k => $l): ?><option value="<?= $k ?>" <?= $f['metodo'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
    <div class="flex-fill" style="min-width:180px"><label class="form-lbl mb-1 small">Socio</label><input type="search" name="q" value="<?= e($f['q']) ?>" class="form-control form-control-sm" placeholder="Nombre, DNI o código"></div>
    <div class="align-self-end d-flex gap-2"><button class="btn btn-soft btn-sm"><i class="bi bi-funnel"></i> Filtrar</button>
      <a href="<?= url('pagos') ?>" class="btn btn-outline-secondary btn-sm">Este mes</a></div>
  </form>
  <div class="table-responsive">
    <table class="table tbl align-middle mb-0 tbl-pad">
      <thead><tr><th>N°</th><th>Fecha</th><th>Socio</th><th class="d-none d-md-table-cell">Concepto</th><th>Método</th><th class="text-end">Monto</th><th></th></tr></thead>
      <tbody>
      <?php if (!$pagos): ?><tr><td colspan="7"><div class="empty-state"><i class="bi bi-cash-stack"></i><b>No hay pagos en este periodo</b></div></td></tr><?php endif; ?>
      <?php foreach ($pagos as $p): ?>
        <tr>
          <td><small class="text-muted"><?= sprintf('%06d', $p['id']) ?></small></td>
          <td><?= fdate($p['fecha_pago'], 'd/m/Y') ?><br><small class="text-muted"><?= fdate($p['fecha_pago'], 'H:i') ?></small></td>
          <td><a href="<?= url('socios/ver/' . $p['socio_id']) ?>" class="text-reset"><b><?= e($p['nombres'] . ' ' . $p['apellidos']) ?></b><br><small class="text-muted"><?= e($p['codigo']) ?></small></a></td>
          <td class="d-none d-md-table-cell"><?= e($p['concepto']) ?></td>
          <td><span class="pill-met"><i class="bi <?= $metIc[$p['metodo']] ?? 'bi-cash' ?>"></i> <?= e(ucfirst($p['metodo'])) ?></span></td>
          <td class="text-end fw-bold text-success text-nowrap"><?= money($p['monto']) ?></td>
          <td class="text-end"><a class="icon-sm" title="Ver / imprimir recibo" target="_blank" href="<?= url('pagos/recibo/' . $p['id']) ?>"><i class="bi bi-printer"></i></a>
            <?php if (Permisos::esAdmin()): ?><button type="button" class="icon-sm danger" title="Anular pago" data-anular="<?= url('pagos/anular/' . $p['id']) ?>" data-volver="pagos" data-titulo="Anular pago N° <?= sprintf('%06d', $p['id']) ?>" data-info="<?= e(money($p['monto']) . ' · ' . $p['nombres'] . ' ' . $p['apellidos'] . '. El saldo de la membresía volverá a figurar como deuda.') ?>"><i class="bi bi-slash-circle"></i></button><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="table-foot"><small class="text-muted"><?= $pg['total'] ?> pagos</small><?= pager($pg) ?></div>
</div>

<?php else: ?>
<div class="row g-3 mb-3 kpi-grid">
  <div class="col-12 col-md-6 col-xl-4"><div class="stat-card" style="--c1:#ef4444;--c2:#dc2626"><div class="stat-body">
    <div class="stat-val"><?= money($tot_deuda) ?></div><div class="stat-lbl">Total por cobrar</div>
    <div class="stat-delta"><span class="delta"><?= count($deudas) ?> membresías con saldo</span></div><i class="bi bi-exclamation-triangle-fill stat-ghost"></i></div>
    <div class="stat-foot"><span>Cobranza pendiente</span><i class="bi bi-hourglass-split"></i></div></div></div>
</div>
<div class="card-x p-0">
  <div class="table-responsive">
    <table class="table tbl align-middle mb-0 tbl-pad">
      <thead><tr><th>Socio</th><th>Plan</th><th class="d-none d-md-table-cell">Periodo</th><th class="text-end">Total</th><th class="text-end">Pagado</th><th class="text-end">Saldo</th><th class="text-end">Acción</th></tr></thead>
      <tbody>
      <?php if (!$deudas): ?><tr><td colspan="7"><div class="empty-state"><i class="bi bi-emoji-smile"></i><b>¡Sin deudas pendientes!</b><span>Todas las membresías están al día.</span></div></td></tr><?php endif; ?>
      <?php foreach ($deudas as $d): $saldo = $d['total'] - $d['pagado']; ?>
        <tr>
          <td><a href="<?= url('socios/ver/' . $d['socio_id']) ?>" class="text-reset"><b><?= e($d['nombres'] . ' ' . $d['apellidos']) ?></b><br><small class="text-muted"><?= e($d['codigo']) ?></small></a></td>
          <td><span class="tag"><?= e($d['plan']) ?></span></td>
          <td class="d-none d-md-table-cell"><small><?= fdate($d['fecha_inicio']) ?> – <?= fdate($d['fecha_fin']) ?></small></td>
          <td class="text-end"><?= money($d['total']) ?></td><td class="text-end"><?= money($d['pagado']) ?></td>
          <td class="text-end fw-bold text-danger"><?= money($saldo) ?></td>
          <td class="text-end text-nowrap">
            <a class="btn btn-brand btn-sm" href="<?= url('pagos/nuevo?socio=' . $d['socio_id'] . '&membresia=' . $d['id']) ?>"><i class="bi bi-cash-coin"></i> Cobrar</a>
            <?php if ($d['telefono']): ?><a class="icon-sm wa" target="_blank" rel="noopener" title="Recordar por WhatsApp" href="<?= e(wa_link($d['telefono'], 'Hola ' . $d['nombres'] . ', te recordamos que tienes un saldo pendiente de ' . money($saldo) . ' en ' . APP_NAME . '. ¡Gracias!')) ?>"><i class="bi bi-whatsapp"></i></a><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
<?php if (Permisos::esAdmin()) include ROOT_PATH . '/app/views/partials/acciones_modal.php'; ?>
