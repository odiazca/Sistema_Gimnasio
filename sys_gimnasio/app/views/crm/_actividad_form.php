<?php /* vars: $af_lead, $af_socio */ ?>
<form method="post" action="<?= url('crm/guardarActividad') ?>" class="act-form"><?= csrf_field() ?>
  <input type="hidden" name="lead_id" value="<?= (int)($af_lead ?? 0) ?>"><input type="hidden" name="socio_id" value="<?= (int)($af_socio ?? 0) ?>">
  <div class="act-types"><?php foreach (Crm::TIPOS_ACT as $k => $t): ?><label><input type="radio" name="tipo" value="<?= $k ?>" <?= $k === 'nota' ? 'checked' : '' ?>><span style="--c:<?= $t[2] ?>"><i class="bi <?= $t[1] ?>"></i> <?= $t[0] ?></span></label><?php endforeach; ?></div>
  <textarea name="detalle" class="form-control mb-2" rows="2" maxlength="500" placeholder="¿Qué pasó o qué hay que hacer?" required></textarea>
  <div class="d-flex flex-wrap gap-2 align-items-end"><div><label class="form-lbl small mb-1">Programar para (opcional)</label><input type="datetime-local" name="programada" class="form-control form-control-sm"></div>
    <button class="btn btn-brand btn-sm ms-auto"><i class="bi bi-plus-lg"></i> Guardar</button></div>
  <div class="small text-muted mt-1">Sin fecha se guarda como registro; con fecha queda como tarea pendiente.</div>
</form>
