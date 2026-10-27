<?php $L = $L ?? []; $uid = (int)Auth::user()['id']; $usr = $usuarios ?? Database::all('SELECT id, nombre FROM usuarios WHERE estado = 1 ORDER BY nombre'); $edit = !empty($L['id']); ?>
<div class="modal fade" id="leadModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-lg"><form class="modal-content modal-x" method="post" action="<?= url('crm/guardarLead') ?>"><?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int)($L['id'] ?? 0) ?>"><?php if ($edit): ?><input type="hidden" name="volver" value="lead"><?php endif; ?>
  <div class="modal-header border-0"><h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill text-brand"></i> <?= $edit ? 'Editar prospecto' : 'Nuevo prospecto' ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><div class="row g-3">
    <div class="col-md-6"><label class="form-lbl">Nombres *</label><input name="nombres" class="form-control" required maxlength="80" value="<?= e($L['nombres'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-lbl">Apellidos</label><input name="apellidos" class="form-control" maxlength="80" value="<?= e($L['apellidos'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-lbl">Teléfono / WhatsApp</label><input name="telefono" class="form-control" maxlength="20" placeholder="987654321" value="<?= e($L['telefono'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-lbl">Correo</label><input type="email" name="email" class="form-control" maxlength="120" value="<?= e($L['email'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-lbl">¿Cómo nos conoció?</label><select name="fuente" class="form-select"><?php foreach (Crm::FUENTES as $k => $f): ?><option value="<?= $k ?>" <?= ($L['fuente'] ?? 'instagram') === $k ? 'selected' : '' ?>><?= $f[0] ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-lbl">Interés / objetivo</label><input name="interes" class="form-control" maxlength="120" placeholder="Bajar de peso, plan trimestral…" value="<?= e($L['interes'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-lbl">Valor estimado (<?= APP_CURRENCY ?>)</label><input type="number" step="0.01" min="0" name="valor" class="form-control" value="<?= e($L['valor'] ?? '0') ?>"></div>
    <div class="col-md-6"><label class="form-lbl">Responsable</label><select name="responsable_id" class="form-select"><?php foreach ($usr as $u): ?><option value="<?= $u['id'] ?>" <?= (int)($L['responsable_id'] ?? $uid) === (int)$u['id'] ? 'selected' : '' ?>><?= e($u['nombre']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-6"><label class="form-lbl">Notas</label><input name="notas" class="form-control" maxlength="400" value="<?= e($L['notas'] ?? '') ?>"></div>
  </div></div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand">Guardar prospecto</button></div></form></div></div>
