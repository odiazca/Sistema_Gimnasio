<?php $S = Notif::SEGS[$seg]; ?>
<div class="page-head">
  <div><h1 class="page-title">Notificaciones</h1><p class="page-sub">Recordatorios y saludos por WhatsApp listos para enviar con un clic.</p></div>
  <button class="btn btn-soft" data-bs-toggle="modal" data-bs-target="#tplModal"><i class="bi bi-chat-text"></i> Editar plantilla</button>
</div>
<div class="seg mb-3">
  <?php foreach (Notif::SEGS as $k => $s): ?><a href="<?= url('notificaciones?seg=' . $k) ?>" class="<?= $seg === $k ? 'on' : '' ?>"><i class="bi <?= $s[1] ?>"></i> <?= $s[0] ?> <?php if ($cont[$k]): ?><span class="cnt <?= $k === 'cumple' || $k === 'inactivos' ? '' : 'bad' ?>"><?= $cont[$k] ?></span><?php endif; ?></a><?php endforeach; ?>
</div>
<div class="card-x mb-3 py-3">
  <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
    <div class="flex-grow-1"><div class="small fw-bold mb-1">Mensaje de la plantilla «<?= $S[0] ?>»</div><div class="small text-muted" style="white-space:pre-line"><?= e($tpl) ?></div></div>
    <?php if ($seg === 'vencer'): ?>
    <form method="get" action="<?= url('notificaciones') ?>"><input type="hidden" name="seg" value="vencer"><label class="form-lbl small mb-1">Vencen en los próximos</label>
      <select name="dias" class="form-select form-select-sm" onchange="this.form.submit()"><?php foreach ($opts as $d): ?><option value="<?= $d ?>" <?= $dias === $d ? 'selected' : '' ?>><?= $d ?> días</option><?php endforeach; ?></select></form>
    <?php endif; ?>
  </div>
</div>
<form id="csrfBox" class="d-none"><?= csrf_field() ?></form>
<div class="card-x p-0"><div class="table-responsive"><table class="table tbl align-middle mb-0 tbl-pad">
  <thead><tr><th>Socio</th><th class="d-none d-md-table-cell">Detalle</th><th>Estado</th><th class="text-end">Enviar</th></tr></thead><tbody>
  <?php if (!$rows): ?><tr><td colspan="4" class="text-center text-muted py-5"><i class="bi bi-check2-circle fs-2 d-block mb-1 text-success"></i>No hay socios en esta lista. ¡Todo al día!</td></tr><?php endif; ?>
  <?php foreach ($rows as $r): $sent = $env[$r['id']] ?? null; ?>
    <tr><td><a class="fw-semibold text-reset text-decoration-none" href="<?= url('socios/ver/' . $r['id']) ?>"><?= e($r['nombres'] . ' ' . $r['apellidos']) ?></a><div class="small text-muted"><?= e($r['codigo']) ?><?= $r['telefono'] ? ' · ' . e($r['telefono']) : '' ?></div></td>
      <td class="d-none d-md-table-cell small">
        <?php if ($seg === 'vencer'): ?><?= e($r['plan']) ?> · vence <?= fdate($r['fecha']) ?> <span class="badge-soft warning"><?= (int)$r['dias'] === 0 ? 'hoy' : (int)$r['dias'] . ' d' ?></span>
        <?php elseif ($seg === 'vencidos'): ?><?= e($r['plan']) ?> · venció <?= fdate($r['fecha']) ?> <span class="badge-soft danger">hace <?= (int)$r['dias'] ?> d</span>
        <?php elseif ($seg === 'deudas'): ?><?= e($r['plan']) ?> · saldo <b class="text-danger"><?= money($r['saldo']) ?></b>
        <?php elseif ($seg === 'cumple'): ?><?= (int)$r['dias'] === 0 ? '<span class="badge-soft success">Hoy</span>' : 'en ' . (int)$r['dias'] . ' días' ?> · <?= fdate($r['fecha'], 'd/m') ?>
        <?php else: ?><?= e($r['plan']) ?> · última visita: <?= $r['ult'] ? fdate($r['ult']) : '<span class="text-danger">nunca</span>' ?><?php endif; ?></td>
      <td><?= $sent ? '<span class="badge-soft success"><i class="bi bi-check2"></i> Enviado ' . fdate($sent, 'd/m') . '</span>' : '<span class="badge-soft secondary">Pendiente</span>' ?></td>
      <td class="text-end"><?php if ($r['wa']): ?><a href="<?= e($r['wa']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-brand wa-send" data-socio="<?= (int)$r['id'] ?>"><i class="bi bi-whatsapp"></i> WhatsApp</a>
        <?php else: ?><span class="small text-muted">Sin teléfono</span><?php endif; ?></td></tr>
  <?php endforeach; ?></tbody></table></div></div>
<p class="small text-muted mt-2">WhatsApp se abre con el mensaje ya redactado; tú confirmas el envío. Al hacer clic, el socio queda marcado como «Enviado» durante 7 días.</p>

<div class="modal fade" id="tplModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content modal-x" method="post" action="<?= url('notificaciones/plantilla') ?>"><?= csrf_field() ?><input type="hidden" name="seg" value="<?= e($seg) ?>">
  <div class="modal-header border-0"><h5 class="modal-title fw-bold">Plantilla · <?= $S[0] ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><textarea name="texto" rows="6" class="form-control" maxlength="600" required><?= e($tpl) ?></textarea>
    <div class="small text-muted mt-2">Variables: <code>{nombre}</code> <code>{apellido}</code> <code>{plan}</code> <code>{fecha}</code> <code>{dias}</code> <code>{saldo}</code> <code>{gimnasio}</code></div></div>
  <div class="modal-footer border-0 justify-content-between"><button name="restaurar" value="1" class="btn btn-outline-secondary btn-sm" formnovalidate>Restaurar original</button>
    <div class="d-flex gap-2"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand">Guardar</button></div></div></form></div></div>
<script>
document.querySelectorAll('.wa-send').forEach(function (a) {
  a.addEventListener('click', function () {
    var fd = new FormData(); fd.append('csrf', document.querySelector('#csrfBox input').value); fd.append('seg', '<?= e($seg) ?>'); fd.append('socio_id', a.dataset.socio);
    fetch('<?= url('notificaciones/registrar') ?>', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function () {
      var td = a.closest('tr').children[2]; td.innerHTML = '<span class="badge-soft success"><i class="bi bi-check2"></i> Enviado hoy</span>';
    });
  });
});
</script>
