<?php include __DIR__ . '/_nav.php'; ?>
<div class="page-head"><div><h1 class="page-title">Campañas</h1><p class="page-sub">Segmenta a tus socios y prospectos y envía mensajes personalizados por WhatsApp.</p></div></div>
<div class="row g-3 mb-3">
<?php foreach (Crm::SEGS as $k => $S): ?><div class="col-6 col-md-4 col-xl"><a href="<?= url('crm/campanas?seg=' . $k) ?>" class="seg-card <?= $seg === $k ? 'on' : '' ?>" style="--c:<?= $S[2] ?>">
  <span class="seg-ic"><i class="bi <?= $S[1] ?>"></i></span><b><?= $cnt[$k] ?></b><span class="seg-n"><?= $S[0] ?></span></a></div><?php endforeach; ?>
</div>
<?php if ($seg): $S = Crm::SEGS[$seg]; $conTel = array_filter($dest, fn($d) => preg_replace('/\D/', '', (string)$d['telefono']) !== ''); ?>
<form method="post" action="<?= url('crm/crearCampana') ?>" class="row g-3 g-xl-4 mb-4" id="campForm"><?= csrf_field() ?><input type="hidden" name="seg" value="<?= e($seg) ?>">
  <div class="col-xl-5"><div class="card-x h-100"><h6 class="sec-title"><i class="bi <?= $S[1] ?>" style="color:<?= $S[2] ?>"></i> <?= $S[0] ?></h6><p class="small text-muted"><?= e($S[3]) ?></p>
    <label class="form-lbl">Nombre de la campaña</label><input name="nombre" class="form-control mb-3" maxlength="120" value="<?= e($S[0] . ' · ' . date('d/m/Y')) ?>">
    <label class="form-lbl">Mensaje</label><textarea name="mensaje" id="campMsg" rows="6" class="form-control" maxlength="700" required><?= e($tpl) ?></textarea>
    <div class="small text-muted mt-1">Variables: <code>{nombre}</code> <code>{plan}</code> <code>{vence}</code> <code>{dias}</code> <code>{saldo}</code> <code>{gimnasio}</code></div>
    <div class="wa-preview mt-3"><div class="wa-bubble" id="campPrev"><?= $dest ? e($dest[0]['preview']) : 'Sin destinatarios en este segmento.' ?></div></div>
    <button class="btn btn-brand w-100 mt-3" <?= !$conTel ? 'disabled' : '' ?>><i class="bi bi-rocket-takeoff-fill"></i> Crear campaña con <span id="campSel"><?= count($conTel) ?></span> destinatarios</button></div></div>
  <div class="col-xl-7"><div class="card-x p-0 h-100"><div class="d-flex justify-content-between align-items-center p-3 pb-2"><h6 class="sec-title mb-0"><i class="bi bi-people-fill"></i> Destinatarios (<?= count($dest) ?>)</h6>
      <label class="small"><input type="checkbox" id="campAll" checked class="form-check-input me-1">Seleccionar todos</label></div>
    <div class="table-responsive" style="max-height:480px"><table class="table tbl align-middle mb-0 tbl-pad"><tbody>
      <?php if (!$dest): ?><tr><td class="text-center text-muted py-5"><i class="bi bi-check2-circle fs-2 d-block text-success"></i>Nadie en este segmento por ahora.</td></tr><?php endif; ?>
      <?php foreach ($dest as $d): $ok = preg_replace('/\D/', '', (string)$d['telefono']) !== ''; ?><tr data-prev="<?= e($d['preview']) ?>">
        <td style="width:36px"><input type="checkbox" class="form-check-input camp-chk" name="dest[]" value="<?= e($d['tipo'] . ':' . $d['id']) ?>" <?= $ok ? 'checked' : 'disabled' ?>></td>
        <td><b><?= e($d['nombres'] . ' ' . $d['apellidos']) ?></b> <span class="badge-soft secondary"><?= $d['tipo'] === 'lead' ? 'Prospecto' : 'Socio' ?></span><div class="small text-muted"><?= $d['plan'] ? e($d['plan']) . ' · ' : '' ?><?= $ok ? e($d['telefono']) : '<span class="text-danger">Sin teléfono</span>' ?></div></td></tr><?php endforeach; ?></tbody></table></div></div></div>
</form>
<?php endif; ?>
<div class="card-x p-0"><div class="p-3 pb-0"><h6 class="sec-title"><i class="bi bi-clock-history"></i> Historial de campañas</h6></div>
  <?php if (!$hist): ?><div class="crm-empty py-4"><i class="bi bi-megaphone"></i><p>Aún no has creado campañas. Elige un segmento arriba para empezar.</p></div><?php else: ?>
  <div class="table-responsive"><table class="table tbl align-middle mb-0 tbl-pad"><thead><tr><th>Campaña</th><th>Segmento</th><th style="min-width:160px">Avance</th><th class="d-none d-md-table-cell">Fecha</th><th></th></tr></thead><tbody>
  <?php foreach ($hist as $h): $S = Crm::SEGS[$h['segmento']] ?? ['—', 'bi-megaphone', '#64748b']; $pct = $h['total'] ? round($h['enviados'] / $h['total'] * 100) : 0; ?><tr>
    <td><a class="fw-semibold text-reset text-decoration-none" href="<?= url('crm/campana/' . $h['id']) ?>"><?= e($h['nombre']) ?></a><div class="small text-muted"><?= e($h['usuario'] ?: '') ?></div></td>
    <td><span class="badge-soft secondary"><i class="bi <?= $S[1] ?>"></i> <?= e($S[0]) ?></span></td>
    <td><div class="risk-bar low"><span style="width:<?= $pct ?>%"></span></div><small><?= (int)$h['enviados'] ?>/<?= (int)$h['total'] ?> enviados</small></td>
    <td class="d-none d-md-table-cell small text-muted"><?= fdate($h['created_at'], 'd/m/Y H:i') ?></td><td class="text-end"><a href="<?= url('crm/campana/' . $h['id']) ?>" class="btn btn-soft btn-sm">Abrir</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div>
<script>window.CRM = { base: <?= json_encode(rtrim(url(''), '/')) ?> };</script>
<script src="<?= ASSETS_URL ?>/js/crm.js?v=<?= APP_VERSION ?>"></script>
