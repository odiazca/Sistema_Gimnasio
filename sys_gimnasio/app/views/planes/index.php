<div class="page-head">
  <div><h1 class="page-title">Planes y membresías</h1><p class="page-sub">Define los planes que vende tu gimnasio, su duración y su precio.</p></div>
  <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#planModal" data-plan='{}'><i class="bi bi-plus-lg"></i> Nuevo plan</button>
</div>
<?php $tab = 'planes'; require __DIR__ . '/../membresias/_tabs.php'; ?>

<div class="row g-3 g-xl-4">
  <?php foreach ($planes as $p): ?>
  <div class="col-md-6 col-xl-4">
    <div class="plan-card <?= $p['estado'] ? '' : 'off' ?>" style="--c:<?= e($p['color']) ?>">
      <div class="plan-top">
        <span class="plan-name"><?= e($p['nombre']) ?></span>
        <?php if (!$p['estado']): ?><span class="badge-soft secondary">Inactivo</span><?php endif; ?>
      </div>
      <div class="plan-price"><small><?= APP_CURRENCY ?></small><?= fmt_num($p['precio']) ?></div>
      <div class="plan-dur"><i class="bi bi-clock-history"></i> <?= (int)$p['duracion_dias'] ?> día<?= $p['duracion_dias'] > 1 ? 's' : '' ?> · <?= money($p['precio'] / max(1, $p['duracion_dias'])) ?>/día</div>
      <p class="plan-desc"><?= e($p['descripcion'] ?: 'Sin descripción') ?></p>
      <div class="plan-stats">
        <div><b><?= (int)$p['vigentes'] ?></b><span>vigentes</span></div>
        <div><b><?= (int)$p['total_mem'] ?></b><span>vendidas</span></div>
      </div>
      <div class="plan-actions">
        <button class="btn btn-soft btn-sm" data-bs-toggle="modal" data-bs-target="#planModal"
          data-plan='<?= e(json_encode(['id' => $p['id'], 'nombre' => $p['nombre'], 'descripcion' => $p['descripcion'], 'duracion_dias' => $p['duracion_dias'], 'precio' => $p['precio'], 'color' => $p['color']])) ?>'><i class="bi bi-pencil"></i> Editar</button>
        <form method="post" action="<?= url('planes/estado/' . $p['id']) ?>" class="d-inline"><?= csrf_field() ?>
          <button class="btn btn-outline-secondary btn-sm"><?= $p['estado'] ? '<i class="bi bi-pause-circle"></i> Desactivar' : '<i class="bi bi-play-circle"></i> Activar' ?></button></form>
        <?php if (!$p['total_mem']): ?>
        <form method="post" action="<?= url('planes/eliminar/' . $p['id']) ?>" class="d-inline" data-confirm="¿Eliminar el plan «<?= e($p['nombre']) ?>»?"><?= csrf_field() ?>
          <button class="btn btn-outline-danger btn-sm" title="Eliminar"><i class="bi bi-trash"></i></button></form>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="modal fade" id="planModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content modal-x" method="post" action="<?= url('planes/guardar') ?>">
      <?= csrf_field() ?><input type="hidden" name="id" id="plId">
      <div class="modal-header border-0"><h5 class="modal-title fw-bold" id="plTitle">Nuevo plan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12"><label class="form-lbl">Nombre del plan *</label><input name="nombre" id="plNombre" class="form-control" required maxlength="80"></div>
          <div class="col-12"><label class="form-lbl">Descripción</label><input name="descripcion" id="plDesc" class="form-control" maxlength="255" placeholder="Ej: Acceso total + 1 evaluación física"></div>
          <div class="col-6"><label class="form-lbl">Duración (días) *</label><input type="number" min="1" max="1095" name="duracion_dias" id="plDias" class="form-control" required></div>
          <div class="col-6"><label class="form-lbl">Precio (<?= APP_CURRENCY ?>) *</label><input type="number" step="0.01" min="0" name="precio" id="plPrecio" class="form-control" required></div>
          <div class="col-12"><label class="form-lbl">Color</label><input type="color" name="color" id="plColor" class="form-control form-control-color w-100" value="#ff5a1f"></div>
        </div>
      </div>
      <div class="modal-footer border-0"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand">Guardar plan</button></div>
    </form>
  </div>
</div>
<script>
window.addEventListener('DOMContentLoaded', function () {
  document.getElementById('planModal').addEventListener('show.bs.modal', function (ev) {
    var d = {}; try { d = JSON.parse(ev.relatedTarget.getAttribute('data-plan') || '{}'); } catch (e) {}
    document.getElementById('plTitle').textContent = d.id ? 'Editar plan' : 'Nuevo plan';
    document.getElementById('plId').value = d.id || '';
    document.getElementById('plNombre').value = d.nombre || '';
    document.getElementById('plDesc').value = d.descripcion || '';
    document.getElementById('plDias').value = d.duracion_dias || 30;
    document.getElementById('plPrecio').value = d.precio || '';
    document.getElementById('plColor').value = d.color || '#ff5a1f';
  });
});
</script>
