<?php $act = count(array_filter($rows, fn($r) => $r['estado'])); ?>
<div class="page-head">
  <div><h1 class="page-title">Entrenadores</h1><p class="page-sub"><?= $act ?> activo<?= $act == 1 ? '' : 's' ?> de <?= count($rows) ?> registrados.</p></div>
  <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#entModal" data-ent='{}'><i class="bi bi-plus-lg"></i> Nuevo entrenador</button>
</div>
<div class="row g-3 g-xl-4">
<?php foreach ($rows as $r): ?>
  <div class="col-md-6 col-xl-4">
    <div class="card-x h-100 <?= $r['estado'] ? '' : 'opacity-75' ?>">
      <div class="d-flex align-items-center gap-3 mb-3">
        <div class="avatar-lg"><?= e(iniciales($r['nombres'] . ' ' . $r['apellidos'])) ?></div>
        <div class="min-w-0">
          <div class="fw-bold text-truncate"><?= e($r['nombres'] . ' ' . $r['apellidos']) ?></div>
          <div class="text-muted small"><?= e($r['especialidad'] ?: 'Sin especialidad') ?></div>
        </div>
        <?php if (!$r['estado']): ?><span class="badge-soft secondary ms-auto">Inactivo</span><?php endif; ?>
      </div>
      <div class="small mb-2"><i class="bi bi-telephone text-muted"></i> <?= e($r['telefono'] ?: '—') ?>
        <?php if ($r['telefono']): ?> · <a href="<?= e(wa_link($r['telefono'])) ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?></div>
      <div class="small mb-3 text-truncate"><i class="bi bi-envelope text-muted"></i> <?= e($r['email'] ?: '—') ?></div>
      <div class="plan-stats mb-3">
        <div><b><?= (int)$r['n_clases'] ?></b><span>clases</span></div>
        <div><b><?= (int)$r['n_horarios'] ?></b><span>horarios/sem.</span></div>
      </div>
      <?php if ($r['clases']): ?><div class="small text-muted mb-3"><?= e($r['clases']) ?></div><?php endif; ?>
      <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-soft btn-sm" data-bs-toggle="modal" data-bs-target="#entModal" data-ent='<?= e(json_encode(['id' => $r['id'], 'nombres' => $r['nombres'], 'apellidos' => $r['apellidos'], 'especialidad' => $r['especialidad'], 'telefono' => $r['telefono'], 'email' => $r['email']])) ?>'><i class="bi bi-pencil"></i> Editar</button>
        <form method="post" action="<?= url('entrenadores/estado/' . $r['id']) ?>"><?= csrf_field() ?>
          <button class="btn btn-outline-secondary btn-sm"><?= $r['estado'] ? 'Desactivar' : 'Activar' ?></button></form>
        <?php if (!$r['n_clases']): ?>
        <form method="post" action="<?= url('entrenadores/eliminar/' . $r['id']) ?>" data-confirm="¿Eliminar a <?= e($r['nombres']) ?>?"><?= csrf_field() ?>
          <button class="btn btn-outline-danger btn-sm" title="Eliminar"><i class="bi bi-trash"></i></button></form>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>

<div class="modal fade" id="entModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content modal-x" method="post" action="<?= url('entrenadores/guardar') ?>">
      <?= csrf_field() ?><input type="hidden" name="id" id="enId">
      <div class="modal-header border-0"><h5 class="modal-title fw-bold" id="enTitle">Nuevo entrenador</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body"><div class="row g-3">
        <div class="col-6"><label class="form-lbl">Nombres *</label><input name="nombres" id="enN" class="form-control" required maxlength="80"></div>
        <div class="col-6"><label class="form-lbl">Apellidos *</label><input name="apellidos" id="enA" class="form-control" required maxlength="80"></div>
        <div class="col-12"><label class="form-lbl">Especialidad</label><input name="especialidad" id="enE" class="form-control" maxlength="100" placeholder="Ej: Funcional, Spinning, Yoga"></div>
        <div class="col-6"><label class="form-lbl">Teléfono</label><input name="telefono" id="enT" class="form-control" maxlength="20"></div>
        <div class="col-6"><label class="form-lbl">Correo</label><input type="email" name="email" id="enM" class="form-control" maxlength="120"></div>
      </div></div>
      <div class="modal-footer border-0"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand">Guardar</button></div>
    </form>
  </div>
</div>
<script>
window.addEventListener('DOMContentLoaded', function () {
  document.getElementById('entModal').addEventListener('show.bs.modal', function (ev) {
    var d = {}; try { d = JSON.parse(ev.relatedTarget.getAttribute('data-ent') || '{}'); } catch (e) {}
    document.getElementById('enTitle').textContent = d.id ? 'Editar entrenador' : 'Nuevo entrenador';
    document.getElementById('enId').value = d.id || ''; document.getElementById('enN').value = d.nombres || '';
    document.getElementById('enA').value = d.apellidos || ''; document.getElementById('enE').value = d.especialidad || '';
    document.getElementById('enT').value = d.telefono || ''; document.getElementById('enM').value = d.email || '';
  });
});
</script>
