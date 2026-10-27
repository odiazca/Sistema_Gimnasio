<div class="page-head">
  <div><h1 class="page-title">Rutinas</h1><p class="page-sub">Biblioteca de ejercicios y rutinas personalizadas por socio.</p></div>
  <div class="d-flex gap-2">
    <button class="btn btn-soft" data-bs-toggle="modal" data-bs-target="#ejModal" data-ej='{}'><i class="bi bi-plus-lg"></i> Nuevo ejercicio</button>
    <a href="<?= url('rutinas/crear') ?>" class="btn btn-brand"><i class="bi bi-clipboard2-plus"></i> Nueva rutina</a></div>
</div>
<div class="seg mb-3">
  <a href="<?= url('rutinas') ?>" class="<?= $vista === 'rutinas' ? 'on' : '' ?>"><i class="bi bi-clipboard2-pulse"></i> Rutinas</a>
  <a href="<?= url('rutinas?vista=ejercicios') ?>" class="<?= $vista === 'ejercicios' ? 'on' : '' ?>"><i class="bi bi-lightning-charge"></i> Ejercicios</a>
</div>
<?php if ($vista === 'rutinas'): ?>
<div class="row g-3 g-xl-4">
  <?php if (!$rutinas): ?><div class="col-12"><div class="card-x text-center text-muted py-5">Aún no hay rutinas. Crea la primera.</div></div><?php endif; ?>
  <?php foreach ($rutinas as $r): ?>
  <div class="col-md-6 col-xl-4"><div class="card-x h-100 <?= $r['estado'] ? '' : 'opacity-75' ?>">
    <div class="d-flex justify-content-between gap-2 mb-2"><a href="<?= url('rutinas/ver/' . $r['id']) ?>" class="fw-bold fs-6 text-reset text-decoration-none"><?= e($r['nombre']) ?></a><?php if (!$r['estado']): ?><span class="badge-soft secondary">Inactiva</span><?php endif; ?></div>
    <div class="d-flex gap-1 flex-wrap mb-2"><?php if ($r['objetivo']): ?><span class="badge-soft info"><?= e($r['objetivo']) ?></span><?php endif; ?><?php if ($r['nivel']): ?><span class="badge-soft warning"><?= e($r['nivel']) ?></span><?php endif; ?></div>
    <p class="small text-muted mb-3"><?= e($r['descripcion'] ?: 'Sin descripción') ?></p>
    <div class="plan-stats mb-3"><div><b><?= (int)$r['n_dias'] ?></b><span>días</span></div><div><b><?= (int)$r['n_ej'] ?></b><span>ejercicios</span></div><div><b><?= (int)$r['n_socios'] ?></b><span>socios</span></div></div>
    <div class="d-flex gap-2 flex-wrap">
      <a class="btn btn-soft btn-sm" href="<?= url('rutinas/ver/' . $r['id']) ?>"><i class="bi bi-eye"></i> Ver / asignar</a>
      <a class="btn btn-outline-secondary btn-sm" href="<?= url('rutinas/editar/' . $r['id']) ?>"><i class="bi bi-pencil"></i></a>
      <form method="post" action="<?= url('rutinas/estado/' . $r['id']) ?>"><?= csrf_field() ?><button class="btn btn-outline-secondary btn-sm"><?= $r['estado'] ? 'Desactivar' : 'Activar' ?></button></form>
      <form method="post" action="<?= url('rutinas/eliminar/' . $r['id']) ?>" data-confirm="¿Eliminar la rutina «<?= e($r['nombre']) ?>»?"><?= csrf_field() ?><button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button></form>
    </div></div></div>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="card-x p-0">
  <form class="filter-bar" method="get" action="<?= url('rutinas') ?>"><input type="hidden" name="vista" value="ejercicios">
    <div class="flex-fill" style="min-width:160px"><label class="form-lbl mb-1 small">Buscar</label><input type="search" name="q" value="<?= e($f['q']) ?>" class="form-control form-control-sm"></div>
    <div><label class="form-lbl mb-1 small">Grupo</label><select name="grupo" class="form-select form-select-sm"><option value="">Todos</option><?php foreach (Rutina::GRUPOS as $g): ?><option <?= $f['grupo'] === $g ? 'selected' : '' ?>><?= $g ?></option><?php endforeach; ?></select></div>
    <div class="align-self-end d-flex gap-2"><button class="btn btn-soft btn-sm"><i class="bi bi-funnel"></i> Filtrar</button><a href="<?= url('rutinas?vista=ejercicios') ?>" class="btn btn-outline-secondary btn-sm">Limpiar</a></div></form>
  <div class="table-responsive"><table class="table tbl align-middle mb-0 tbl-pad"><thead><tr><th>Ejercicio</th><th>Grupo</th><th class="d-none d-md-table-cell">Equipo</th><th class="text-end">Acciones</th></tr></thead><tbody>
  <?php if (!$ejs): ?><tr><td colspan="4" class="text-center text-muted py-5">Sin ejercicios.</td></tr><?php endif; ?>
  <?php foreach ($ejs as $x): ?><tr class="<?= $x['estado'] ? '' : 'opacity-50' ?>"><td class="fw-semibold"><?= e($x['nombre']) ?> <?php if ($x['video_url']): ?><a href="<?= e($x['video_url']) ?>" target="_blank" rel="noopener noreferrer" title="Ver video"><i class="bi bi-play-circle-fill text-danger"></i></a><?php endif; ?></td>
    <td><span class="badge-soft secondary"><?= e($x['grupo']) ?></span></td><td class="d-none d-md-table-cell"><?= e($x['equipo'] ?: '—') ?></td>
    <td class="text-end text-nowrap">
      <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#ejModal" data-ej='<?= e(json_encode(['id' => $x['id'], 'nombre' => $x['nombre'], 'grupo' => $x['grupo'], 'equipo' => $x['equipo'], 'video_url' => $x['video_url']])) ?>'><i class="bi bi-pencil"></i></button>
      <form method="post" action="<?= url('rutinas/estadoEjercicio/' . $x['id']) ?>" class="d-inline"><?= csrf_field() ?><button class="btn btn-outline-secondary btn-sm"><i class="bi <?= $x['estado'] ? 'bi-pause-circle' : 'bi-play-circle' ?>"></i></button></form>
      <form method="post" action="<?= url('rutinas/eliminarEjercicio/' . $x['id']) ?>" class="d-inline" data-confirm="¿Eliminar este ejercicio?"><?= csrf_field() ?><button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button></form></td></tr><?php endforeach; ?>
  </tbody></table></div></div>
<?php endif; ?>
<div class="modal fade" id="ejModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content modal-x" method="post" action="<?= url('rutinas/guardarEjercicio') ?>"><?= csrf_field() ?><input type="hidden" name="id" id="xId">
  <div class="modal-header border-0"><h5 class="modal-title fw-bold" id="xTitle">Nuevo ejercicio</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><div class="row g-3">
    <div class="col-12"><label class="form-lbl">Nombre *</label><input name="nombre" id="xNom" class="form-control" required maxlength="100"></div>
    <div class="col-6"><label class="form-lbl">Grupo muscular *</label><select name="grupo" id="xGru" class="form-select"><?php foreach (Rutina::GRUPOS as $g): ?><option><?= $g ?></option><?php endforeach; ?></select></div>
    <div class="col-6"><label class="form-lbl">Equipo</label><input name="equipo" id="xEq" class="form-control" maxlength="60"></div>
    <div class="col-12"><label class="form-lbl">Enlace de video (opcional)</label><input type="url" name="video_url" id="xVid" class="form-control" maxlength="255" placeholder="https://youtube.com/…"></div>
  </div></div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand">Guardar</button></div></form></div></div>
<script>
window.addEventListener('DOMContentLoaded', function () {
  var $ = function (i) { return document.getElementById(i); };
  $('ejModal').addEventListener('show.bs.modal', function (ev) {
    var d = {}; try { d = JSON.parse(ev.relatedTarget.getAttribute('data-ej') || '{}'); } catch (e) {}
    $('xTitle').textContent = d.id ? 'Editar ejercicio' : 'Nuevo ejercicio'; $('xId').value = d.id || ''; $('xNom').value = d.nombre || '';
    $('xGru').value = d.grupo || 'Pecho'; $('xEq').value = d.equipo || ''; $('xVid').value = d.video_url || '';
  });
});
</script>
