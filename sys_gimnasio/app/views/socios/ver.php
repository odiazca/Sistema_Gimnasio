<?php
[$lbl, $cls] = $actual ? estado_membresia($actual['fecha_inicio'], $actual['fecha_fin'], $actual['estado']) : ['Sin membresía', 'secondary'];
$dr = $actual ? dias_restantes($actual['fecha_fin']) : null;
$edad = $s['fecha_nacimiento'] ? (new DateTime($s['fecha_nacimiento']))->diff(new DateTime('today'))->y : null;
$progreso = 0;
if ($actual && $actual['estado'] !== 'cancelada') {
    $tot = max(1, (int)(new DateTime($actual['fecha_inicio']))->diff(new DateTime($actual['fecha_fin']))->days + 1);
    $usado = max(0, min($tot, (int)(new DateTime($actual['fecha_inicio']))->diff(new DateTime('today'))->format('%r%a') + 1));
    $progreso = round($usado / $tot * 100);
}
$estCls = ['activo' => 'success', 'inactivo' => 'secondary', 'suspendido' => 'warning'][$s['estado']];
?>
<div class="page-head">
  <div class="d-flex align-items-center gap-2 text-muted"><a href="<?= url('socios') ?>" class="link-brand"><i class="bi bi-arrow-left"></i> Socios</a> / <span><?= e($s['codigo']) ?></span></div>
</div>

<section class="profile-hero">
  <div class="profile-bg"></div>
  <div class="position-relative d-flex flex-wrap align-items-center gap-4">
    <?php if ($s['foto']): ?><img src="<?= e(foto_url($s['foto'])) ?>" class="profile-photo" alt="">
    <?php else: ?><span class="profile-photo ph"><?= e(iniciales($s['nombres'], $s['apellidos'])) ?></span><?php endif; ?>
    <div class="flex-fill">
      <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
        <h1 class="m-0"><?= e($s['nombres'] . ' ' . $s['apellidos']) ?></h1>
        <span class="badge-soft <?= $estCls ?> on-dark"><?= ucfirst($s['estado']) ?></span>
      </div>
      <div class="profile-meta">
        <span><i class="bi bi-upc-scan"></i> <?= e($s['codigo']) ?></span>
        <?php if ($s['dni']): ?><span><i class="bi bi-person-vcard"></i> <?= e($s['dni']) ?></span><?php endif; ?>
        <?php if ($edad !== null): ?><span><i class="bi bi-cake2"></i> <?= $edad ?> años</span><?php endif; ?>
        <?php if ($s['telefono']): ?><span><i class="bi bi-telephone"></i> <?= e($s['telefono']) ?></span><?php endif; ?>
        <?php if ($s['objetivo']): ?><span><i class="bi bi-bullseye"></i> <?= e($s['objetivo']) ?></span><?php endif; ?>
        <span><i class="bi bi-calendar-check"></i> Socio desde <?= fdate($s['fecha_registro']) ?></span>
      </div>
      <div class="d-flex flex-wrap gap-2 mt-3">
        <a href="<?= url('membresias/nueva?socio=' . $s['id']) ?>" class="btn btn-light btn-pill"><i class="bi bi-arrow-repeat"></i> <?= $actual ? 'Renovar' : 'Inscribir' ?></a>
        <a href="<?= url('pagos/nuevo?socio=' . $s['id']) ?>" class="btn btn-glass btn-pill"><i class="bi bi-cash-stack"></i> Registrar pago</a>
        <a href="<?= url('socios/editar/' . $s['id']) ?>" class="btn btn-glass btn-pill"><i class="bi bi-pencil"></i> Editar</a>
        <a href="<?= url('evaluaciones/socio/' . $s['id']) ?>" class="btn btn-glass btn-pill"><i class="bi bi-activity"></i> Evaluaciones</a>
        <a href="<?= url('asistencia/carnet/' . $s['id']) ?>" target="_blank" class="btn btn-glass btn-pill"><i class="bi bi-qr-code"></i> Carnet</a>
        <?php if (Permisos::can('crm')): ?><a href="<?= url('crm/socio/' . $s['id']) ?>" class="btn btn-glass btn-pill"><i class="bi bi-bullseye"></i> CRM 360°</a><?php endif; ?>
        <?php if ($s['telefono']): ?><a href="<?= e(wa_link($s['telefono'])) ?>" target="_blank" rel="noopener" class="btn btn-glass btn-pill"><i class="bi bi-whatsapp"></i> WhatsApp</a><?php endif; ?>
      </div>
    </div>
  </div>
</section>

<div class="row g-3 g-xl-4 mt-0 kpi-grid">
  <div class="col-6 col-xl-3"><div class="stat-card" style="--c1:#6366f1;--c2:#4f46e5"><div class="stat-body">
    <div class="stat-val"><?= $stats['asis_mes'] ?></div><div class="stat-lbl">Asistencias este mes</div><i class="bi bi-door-open-fill stat-ghost"></i></div>
    <div class="stat-foot"><span><?= $stats['asis_total'] ?> en total</span><i class="bi bi-activity"></i></div></div></div>
  <div class="col-6 col-xl-3"><div class="stat-card" style="--c1:#10b981;--c2:#059669"><div class="stat-body">
    <div class="stat-val"><?= money($stats['pagado']) ?></div><div class="stat-lbl">Total pagado</div><i class="bi bi-wallet2 stat-ghost"></i></div>
    <a class="stat-foot" href="#tab-pagos" data-bs-toggle="tab" data-bs-target="#tab-pagos"><span>Ver pagos</span><i class="bi bi-arrow-right-circle"></i></a></div></div>
  <div class="col-6 col-xl-3"><div class="stat-card" style="--c1:<?= $stats['deuda'] > 0 ? '#ef4444' : '#14b8a6' ?>;--c2:<?= $stats['deuda'] > 0 ? '#dc2626' : '#0d9488' ?>"><div class="stat-body">
    <div class="stat-val"><?= money($stats['deuda']) ?></div><div class="stat-lbl"><?= $stats['deuda'] > 0 ? 'Saldo pendiente' : 'Sin deudas' ?></div><i class="bi bi-receipt stat-ghost"></i></div>
    <a class="stat-foot" href="<?= url('pagos/nuevo?socio=' . $s['id']) ?>"><span><?= $stats['deuda'] > 0 ? 'Cobrar ahora' : 'Registrar pago' ?></span><i class="bi bi-arrow-right-circle"></i></a></div></div>
  <div class="col-6 col-xl-3"><div class="stat-card" style="--c1:#f59e0b;--c2:#d97706"><div class="stat-body">
    <div class="stat-val"><?= $dr === null ? '—' : ($dr >= 0 ? $dr . ' d' : 'Vencida') ?></div><div class="stat-lbl"><?= $actual ? 'Plan ' . e($actual['plan']) : 'Sin membresía' ?></div>
    <div class="stat-delta"><span class="delta"><?= $lbl ?></span></div><i class="bi bi-hourglass-split stat-ghost"></i></div>
    <div class="stat-foot"><span><?= $actual ? 'Vence ' . fdate($actual['fecha_fin']) : 'Inscribe un plan' ?></span><i class="bi bi-calendar-event"></i></div></div></div>
</div>

<?php if ($actual && $progreso): ?>
<div class="card-x mt-4 py-3">
  <div class="d-flex justify-content-between small mb-1"><b>Progreso de la membresía actual</b><span class="text-muted"><?= fdate($actual['fecha_inicio']) ?> → <?= fdate($actual['fecha_fin']) ?></span></div>
  <div class="progress-x lg"><span style="width:<?= $progreso ?>%;--c:<?= e($actual['plan_color']) ?>"></span></div>
</div>
<?php endif; ?>

<div class="card-x mt-4 p-0">
  <ul class="nav nav-tabs-x px-3 pt-2" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-mem"><i class="bi bi-award"></i> Membresías <span class="cnt"><?= count($mems) ?></span></button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-pagos"><i class="bi bi-cash-stack"></i> Pagos <span class="cnt"><?= count($pagos) ?></span></button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-asis"><i class="bi bi-door-open"></i> Asistencias</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-datos"><i class="bi bi-person-lines-fill"></i> Datos</button></li>
  </ul>
  <div class="tab-content">
    <div class="tab-pane fade show active" id="tab-mem">
      <div class="table-responsive"><table class="table tbl align-middle mb-0 tbl-pad">
        <thead><tr><th>Plan</th><th>Periodo</th><th>Total</th><th>Pagado</th><th>Estado</th><th class="text-end"></th></tr></thead><tbody>
        <?php if (!$mems): ?><tr><td colspan="6"><div class="empty-state"><i class="bi bi-award"></i><b>Sin membresías</b><span>Inscribe al socio en un plan.</span></div></td></tr><?php endif; ?>
        <?php foreach ($mems as $m): [$ml, $mc] = estado_membresia($m['fecha_inicio'], $m['fecha_fin'], $m['estado']); $saldo = $m['total'] - $m['pagado']; ?>
          <tr>
            <td><span class="tag" style="--c:<?= e($m['plan_color']) ?>"><?= e($m['plan']) ?></span></td>
            <td><?= fdate($m['fecha_inicio']) ?> – <?= fdate($m['fecha_fin']) ?></td>
            <td><?= money($m['total']) ?><?= $m['descuento'] > 0 ? '<br><small class="text-muted">desc. ' . money($m['descuento']) . '</small>' : '' ?></td>
            <td><?= money($m['pagado']) ?><?= ($saldo > 0.009 && $m['estado'] !== 'cancelada') ? '<br><small class="text-danger fw-semibold">debe ' . money($saldo) . '</small>' : '' ?></td>
            <td><span class="badge-soft <?= $mc ?>"><?= $ml ?></span></td>
            <td class="text-end text-nowrap">
              <?php if ($saldo > 0.009 && $m['estado'] !== 'cancelada'): ?><a class="icon-sm" title="Cobrar saldo" href="<?= url('pagos/nuevo?socio=' . $s['id'] . '&membresia=' . $m['id']) ?>"><i class="bi bi-cash-coin"></i></a><?php endif; ?>
              <?php if ($m['estado'] === 'activa' && $m['fecha_fin'] >= date('Y-m-d') && $m['fecha_inicio'] <= date('Y-m-d')): ?><button type="button" class="icon-sm" title="Congelar membresía" data-congelar="<?= url('membresias/congelar/' . $m['id']) ?>" data-volver="socios/ver/<?= $s['id'] ?>" data-info="<?= e($m['plan'] . ' · vence ' . fdate($m['fecha_fin'])) ?>"><i class="bi bi-snow2"></i></button><?php endif; ?>
              <?php if ($m['estado'] === 'congelada'): ?><form method="post" action="<?= url('membresias/descongelar/' . $m['id']) ?>" class="d-inline" data-confirm="¿Reactivar la membresía? Se sumarán los días congelados a su fecha de fin."><?= csrf_field() ?><input type="hidden" name="volver" value="socios/ver/<?= $s['id'] ?>"><button class="icon-sm" title="Reactivar membresía"><i class="bi bi-play-circle-fill"></i></button></form><?php endif; ?>
              <?php if ($m['estado'] !== 'cancelada'): ?>
              <form method="post" action="<?= url('membresias/cancelar/' . $m['id']) ?>" class="d-inline" data-confirm="¿Cancelar esta membresía? Los pagos registrados se conservan.">
                <?= csrf_field() ?><input type="hidden" name="volver" value="socios/ver/<?= $s['id'] ?>"><button class="icon-sm danger" title="Cancelar membresía"><i class="bi bi-x-lg"></i></button></form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?></tbody></table></div>
    </div>
    <div class="tab-pane fade" id="tab-pagos">
      <div class="table-responsive"><table class="table tbl align-middle mb-0 tbl-pad">
        <thead><tr><th>Fecha</th><th>Concepto</th><th>Método</th><th class="text-end">Monto</th><th class="text-end"></th></tr></thead><tbody>
        <?php if (!$pagos): ?><tr><td colspan="5"><div class="empty-state"><i class="bi bi-cash-stack"></i><b>Sin pagos registrados</b></div></td></tr><?php endif; ?>
        <?php foreach ($pagos as $p): ?>
          <tr><td><?= fdate($p['fecha_pago'], 'd/m/Y H:i') ?></td><td><?= e($p['concepto']) ?></td><td><span class="tag"><?= e(ucfirst($p['metodo'])) ?></span></td>
            <td class="text-end fw-bold text-success"><?= money($p['monto']) ?></td>
            <td class="text-end"><a class="icon-sm" title="Ver recibo" target="_blank" href="<?= url('pagos/recibo/' . $p['id']) ?>"><i class="bi bi-printer"></i></a>
              <?php if (Permisos::esAdmin()): ?><button type="button" class="icon-sm danger" title="Anular pago" data-anular="<?= url('pagos/anular/' . $p['id']) ?>" data-volver="socios/ver/<?= $s['id'] ?>" data-titulo="Anular pago N° <?= sprintf('%06d', $p['id']) ?>" data-info="<?= e(money($p['monto']) . '. El saldo de la membresía volverá a figurar como deuda.') ?>"><i class="bi bi-slash-circle"></i></button><?php endif; ?></td></tr>
        <?php endforeach; ?></tbody></table></div>
    </div>
    <div class="tab-pane fade" id="tab-asis">
      <ul class="feed px-4 py-2">
        <?php if (!$asis): ?><li><div class="empty-state w-100"><i class="bi bi-door-open"></i><b>Sin asistencias registradas</b></div></li><?php endif; ?>
        <?php foreach ($asis as $a): ?>
          <li><span class="pay-ico" style="--c:#6366f1"><i class="bi bi-box-arrow-in-right"></i></span>
            <div class="flex-fill lh-sm"><b><?= fdate($a['ingreso'], 'd/m/Y') ?></b><br><small class="text-muted">vía <?= e($a['metodo']) ?></small></div>
            <div class="text-end lh-sm"><b><?= fdate($a['ingreso'], 'H:i') ?></b><?= $a['salida'] ? '<br><small class="text-muted">salió ' . fdate($a['salida'], 'H:i') . '</small>' : '' ?></div></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="tab-pane fade" id="tab-datos">
      <div class="row g-0 data-grid">
        <?php foreach ([
          'Documento' => $s['dni'], 'Fecha de nacimiento' => $s['fecha_nacimiento'] ? fdate($s['fecha_nacimiento']) . ($edad !== null ? " ($edad años)" : '') : null,
          'Género' => ['M' => 'Masculino', 'F' => 'Femenino', 'O' => 'Otro'][$s['genero']] ?? null, 'Objetivo' => $s['objetivo'],
          'Teléfono' => $s['telefono'], 'Correo' => $s['email'], 'Dirección' => $s['direccion'],
          'Contacto de emergencia' => $s['contacto_emergencia'] ? $s['contacto_emergencia'] . ($s['telefono_emergencia'] ? ' · ' . $s['telefono_emergencia'] : '') : null,
          'Observaciones' => $s['observaciones'],
        ] as $k => $val): ?>
          <div class="col-md-6"><div class="data-item"><small><?= $k ?></small><span><?= $val !== null && $val !== '' ? e($val) : '—' ?></span></div></div>
        <?php endforeach; ?>
      </div>
      <div class="p-3 border-top d-flex flex-wrap gap-2 align-items-center">
        <form method="post" action="<?= url('socios/estado/' . $s['id']) ?>" class="d-flex gap-2 align-items-center"><?= csrf_field() ?>
          <label class="small text-muted">Cambiar estado:</label>
          <select name="estado" class="form-select form-select-sm w-auto"><?php foreach (['activo', 'inactivo', 'suspendido'] as $es): ?><option value="<?= $es ?>" <?= $s['estado'] === $es ? 'selected' : '' ?>><?= ucfirst($es) ?></option><?php endforeach; ?></select>
          <button class="btn btn-soft btn-sm">Aplicar</button></form>
        <form method="post" action="<?= url('socios/eliminar/' . $s['id']) ?>" class="ms-auto" data-confirm="¿Eliminar definitivamente a este socio? Solo es posible si no tiene membresías ni pagos.">
          <?= csrf_field() ?><button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i> Eliminar socio</button></form>
      </div>
    </div>
  </div>
</div>
<?php include ROOT_PATH . '/app/views/partials/acciones_modal.php'; ?>
