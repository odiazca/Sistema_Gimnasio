<?php include __DIR__ . '/_nav.php'; $E = Crm::ETAPAS[$l['etapa']]; $F = Crm::FUENTES[$l['fuente']] ?? Crm::FUENTES['otro']; $nom = trim($l['nombres'] . ' ' . $l['apellidos']); $L = $l; $keys = array_keys(Crm::ETAPAS); ?>
<div class="page-head"><div class="d-flex align-items-center gap-2 text-muted"><a href="<?= url('crm/embudo') ?>" class="link-brand"><i class="bi bi-arrow-left"></i> Embudo</a> / <span>Prospecto #<?= (int)$l['id'] ?></span></div></div>
<section class="lead-hero" style="--c:<?= $E[2] ?>">
  <div class="d-flex flex-wrap align-items-center gap-3 position-relative">
    <span class="avatar lg"><?= e(iniciales($l['nombres'], $l['apellidos'])) ?></span>
    <div class="flex-grow-1"><h2 class="m-0"><?= e($nom) ?></h2>
      <div class="d-flex flex-wrap gap-2 mt-2"><span class="badge-soft on-dark"><i class="bi <?= $E[1] ?>"></i> <?= $E[0] ?></span><span class="badge-soft on-dark"><i class="bi <?= $F[1] ?>"></i> <?= e($F[0]) ?></span>
        <?php if ($l['valor'] > 0): ?><span class="badge-soft on-dark"><i class="bi bi-cash-coin"></i> <?= money($l['valor']) ?></span><?php endif; ?><span class="badge-soft on-dark"><i class="bi bi-calendar3"></i> Desde <?= fdate($l['created_at']) ?></span></div></div>
    <div class="d-flex flex-wrap gap-2">
      <?php if ($l['telefono']): ?><a class="btn btn-light btn-pill" target="_blank" rel="noopener" href="<?= e(wa_link($l['telefono'], 'Hola ' . $l['nombres'] . ', te escribimos de ' . cfg('gimnasio_nombre', APP_NAME) . ' 👋')) ?>"><i class="bi bi-whatsapp"></i> WhatsApp</a><?php endif; ?>
      <?php if ($l['socio_id']): ?><a class="btn btn-glass btn-pill" href="<?= url('socios/ver/' . $l['socio_id']) ?>"><i class="bi bi-person-check-fill"></i> Ver socio</a>
      <?php else: ?><a class="btn btn-glass btn-pill" href="<?= url('socios/crear?lead=' . $l['id']) ?>"><i class="bi bi-person-plus-fill"></i> Registrar como socio</a><?php endif; ?>
      <button class="btn btn-glass btn-pill" data-bs-toggle="modal" data-bs-target="#leadModal"><i class="bi bi-pencil"></i> Editar</button></div>
  </div>
</section>
<div class="row g-3 g-xl-4 mt-0">
  <div class="col-lg-4">
    <div class="card-x mb-3"><h6 class="sec-title"><i class="bi bi-signpost-split-fill"></i> Etapa</h6>
      <div class="stage-steps"><?php foreach ($keys as $i => $k): if ($k === 'perdido') continue; $on = array_search($l['etapa'], $keys) >= $i && $l['etapa'] !== 'perdido'; ?><button class="<?= $on ? 'on' : '' ?> <?= $k === $l['etapa'] ? 'cur' : '' ?>" style="--c:<?= Crm::ETAPAS[$k][2] ?>" data-stage="<?= $k ?>" title="<?= Crm::ETAPAS[$k][0] ?>"><i class="bi <?= Crm::ETAPAS[$k][1] ?>"></i><span><?= Crm::ETAPAS[$k][0] ?></span></button><?php endforeach; ?></div>
      <?php if ($l['etapa'] === 'perdido'): ?><div class="alert alert-secondary small mt-3 mb-0">Perdido · motivo: <b><?= e($l['motivo_perdida'] ?: 'Otro') ?></b>. <a href="#" data-stage="nuevo" class="link-brand">Reabrir</a></div>
      <?php elseif ($l['etapa'] !== 'ganado'): ?><button class="btn btn-outline-secondary btn-sm mt-3" data-stage="perdido"><i class="bi bi-x-octagon"></i> Marcar como perdido</button><?php endif; ?></div>
    <div class="card-x mb-3"><h6 class="sec-title"><i class="bi bi-person-lines-fill"></i> Datos</h6><dl class="dl-crm">
      <dt>Teléfono</dt><dd><?= $l['telefono'] ? e($l['telefono']) : '—' ?></dd><dt>Correo</dt><dd><?= $l['email'] ? e($l['email']) : '—' ?></dd><dt>Interés</dt><dd><?= $l['interes'] ? e($l['interes']) : '—' ?></dd>
      <dt>Responsable</dt><dd><?= e($l['responsable'] ?: '—') ?></dd><dt>Notas</dt><dd><?= $l['notas'] ? e($l['notas']) : '—' ?></dd></dl>
      <form method="post" action="<?= url('crm/eliminarLead/' . $l['id']) ?>" onsubmit="return confirm('¿Eliminar este prospecto y su historial?')"><?= csrf_field() ?><button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i> Eliminar prospecto</button></form></div>
  </div>
  <div class="col-lg-8"><div class="card-x mb-3"><h6 class="sec-title"><i class="bi bi-plus-circle-fill"></i> Registrar actividad o programar seguimiento</h6><?php $af_lead = $l['id']; $af_socio = 0; include __DIR__ . '/_actividad_form.php'; ?></div>
    <div class="card-x"><h6 class="sec-title"><i class="bi bi-clock-history"></i> Historial</h6><?php include __DIR__ . '/_timeline_acts.php'; ?></div></div>
</div>
<div class="modal fade" id="lostModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content modal-x p-3">
  <h6 class="fw-bold mb-2"><i class="bi bi-x-octagon text-secondary"></i> ¿Por qué se perdió?</h6><p class="small text-muted" id="lostName"><?= e($nom) ?></p>
  <select id="lostMotivo" class="form-select mb-3"><?php foreach (Crm::MOTIVOS as $m): ?><option><?= e($m) ?></option><?php endforeach; ?></select>
  <div class="d-flex gap-2"><button class="btn btn-outline-secondary flex-fill" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand flex-fill" id="lostOk">Marcar perdido</button></div></div></div></div>
<?php include __DIR__ . '/_lead_modal.php'; ?>
<script>window.CRM = { base: <?= json_encode(rtrim(url(''), '/')) ?>, leadId: <?= (int)$l['id'] ?> };</script>
<script src="<?= ASSETS_URL ?>/js/crm.js?v=<?= APP_VERSION ?>"></script>
