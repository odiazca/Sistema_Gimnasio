<?php $dias = Clase::dias(); $hoyN = (int)date('N'); ?>
<div class="page-head">
  <div><h1 class="page-title">Clases y horarios</h1><p class="page-sub">Programación semanal, cupos y reservas de clases grupales.</p></div>
  <div class="d-flex gap-2">
    <button class="btn btn-soft" data-bs-toggle="modal" data-bs-target="#claseModal" data-clase='{}'><i class="bi bi-plus-lg"></i> Nueva clase</button>
    <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#horModal" data-hor='{}'><i class="bi bi-calendar-plus"></i> Agregar horario</button>
  </div>
</div>
<div class="seg mb-3">
  <a href="<?= url('clases') ?>" class="<?= $vista === 'semana' ? 'on' : '' ?>"><i class="bi bi-calendar-week"></i> Semana</a>
  <a href="<?= url('clases?vista=catalogo') ?>" class="<?= $vista === 'catalogo' ? 'on' : '' ?>"><i class="bi bi-collection"></i> Catálogo de clases</a>
</div>

<?php if ($vista === 'semana'): ?>
<div class="row g-3">
<?php foreach ($dias as $n => $nom): ?>
  <div class="col-md-6 col-xl-4">
    <div class="card-x h-100">
      <h6 class="sec-title"><i class="bi bi-calendar-day"></i> <?= $nom ?> <?= $n === $hoyN ? '<span class="badge-soft success ms-1">Hoy</span>' : '' ?></h6>
      <?php if (!$semana[$n]): ?><div class="text-muted small py-3">Sin clases programadas.</div><?php endif; ?>
      <?php foreach ($semana[$n] as $h): $lleno = $h['reservas'] >= $h['cupo']; $pct = min(100, round($h['reservas'] * 100 / max(1, $h['cupo']))); ?>
      <div class="d-flex gap-2 align-items-stretch mb-3">
        <div style="width:5px;border-radius:4px;background:<?= e($h['color']) ?>"></div>
        <div class="flex-grow-1 min-w-0">
          <div class="d-flex justify-content-between gap-2">
            <a class="fw-bold text-reset text-decoration-none" href="<?= url('clases/sesion/' . $h['id']) ?>"><?= e($h['nombre']) ?></a>
            <span class="small fw-semibold"><?= substr($h['hora_inicio'], 0, 5) ?>–<?= substr($h['hora_fin'], 0, 5) ?></span>
          </div>
          <div class="small text-muted"><?= e(trim(($h['ent_nombres'] ?? '') . ' ' . ($h['ent_apellidos'] ?? '')) ?: 'Sin entrenador') ?> · <?= e($h['sala'] ?: 'Sin sala') ?></div>
          <div class="progress mt-1" style="height:6px"><div class="progress-bar <?= $lleno ? 'bg-danger' : '' ?>" style="width:<?= $pct ?>%;<?= $lleno ? '' : 'background:' . e($h['color']) ?>"></div></div>
          <div class="d-flex justify-content-between small mt-1">
            <span class="text-muted"><?= (int)$h['reservas'] ?>/<?= (int)$h['cupo'] ?> reservas (<?= fdate($h['proxima']) ?>)</span>
            <span>
              <a href="<?= url('clases/sesion/' . $h['id']) ?>" title="Ver sesión"><i class="bi bi-people"></i></a>
              <a href="#" class="ms-2" data-bs-toggle="modal" data-bs-target="#horModal" data-hor='<?= e(json_encode(['id' => $h['id'], 'clase_id' => $h['clase_id'], 'dia_semana' => $h['dia_semana'], 'hora_inicio' => substr($h['hora_inicio'], 0, 5), 'hora_fin' => substr($h['hora_fin'], 0, 5), 'sala' => $h['sala']])) ?>' title="Editar"><i class="bi bi-pencil"></i></a>
              <form method="post" action="<?= url('clases/eliminarHorario/' . $h['id']) ?>" class="d-inline" data-confirm="¿Eliminar este horario?"><?= csrf_field() ?>
                <button class="btn btn-link p-0 ms-2 text-danger border-0 align-baseline" title="Eliminar"><i class="bi bi-trash"></i></button></form>
            </span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>

<?php else: ?>
<div class="row g-3 g-xl-4">
<?php foreach ($catalogo as $c): ?>
  <div class="col-md-6 col-xl-4">
    <div class="plan-card <?= $c['estado'] ? '' : 'off' ?>" style="--c:<?= e($c['color']) ?>">
      <div class="plan-top"><span class="plan-name"><?= e($c['nombre']) ?></span><?php if (!$c['estado']): ?><span class="badge-soft secondary">Inactiva</span><?php endif; ?></div>
      <p class="plan-desc"><?= e($c['descripcion'] ?: 'Sin descripción') ?></p>
      <div class="small mb-2"><i class="bi bi-person-badge"></i> <?= e(trim(($c['ent_nombres'] ?? '') . ' ' . ($c['ent_apellidos'] ?? '')) ?: 'Sin entrenador') ?></div>
      <div class="plan-stats"><div><b><?= (int)$c['cupo'] ?></b><span>cupo</span></div><div><b><?= (int)$c['n_horarios'] ?></b><span>horarios</span></div></div>
      <div class="plan-actions">
        <button class="btn btn-soft btn-sm" data-bs-toggle="modal" data-bs-target="#claseModal" data-clase='<?= e(json_encode(['id' => $c['id'], 'nombre' => $c['nombre'], 'descripcion' => $c['descripcion'], 'entrenador_id' => $c['entrenador_id'], 'cupo' => $c['cupo'], 'color' => $c['color']])) ?>'><i class="bi bi-pencil"></i> Editar</button>
        <form method="post" action="<?= url('clases/estadoClase/' . $c['id']) ?>" class="d-inline"><?= csrf_field() ?><button class="btn btn-outline-secondary btn-sm"><?= $c['estado'] ? 'Desactivar' : 'Activar' ?></button></form>
        <form method="post" action="<?= url('clases/eliminarClase/' . $c['id']) ?>" class="d-inline" data-confirm="¿Eliminar la clase «<?= e($c['nombre']) ?>» y sus horarios?"><?= csrf_field() ?><button class="btn btn-outline-danger btn-sm" title="Eliminar"><i class="bi bi-trash"></i></button></form>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<div class="modal fade" id="claseModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered">
  <form class="modal-content modal-x" method="post" action="<?= url('clases/guardarClase') ?>">
    <?= csrf_field() ?><input type="hidden" name="id" id="clId">
    <div class="modal-header border-0"><h5 class="modal-title fw-bold" id="clTitle">Nueva clase</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="row g-3">
      <div class="col-12"><label class="form-lbl">Nombre *</label><input name="nombre" id="clN" class="form-control" required maxlength="80"></div>
      <div class="col-12"><label class="form-lbl">Descripción</label><input name="descripcion" id="clD" class="form-control" maxlength="255"></div>
      <div class="col-12"><label class="form-lbl">Entrenador</label><select name="entrenador_id" id="clE" class="form-select"><option value="">— Sin asignar —</option>
        <?php foreach ($ents as $en): ?><option value="<?= $en['id'] ?>"><?= e($en['nombres'] . ' ' . $en['apellidos']) ?></option><?php endforeach; ?></select></div>
      <div class="col-6"><label class="form-lbl">Cupo *</label><input type="number" min="1" max="300" name="cupo" id="clC" class="form-control" required></div>
      <div class="col-6"><label class="form-lbl">Color</label><input type="color" name="color" id="clK" class="form-control form-control-color w-100"></div>
    </div></div>
    <div class="modal-footer border-0"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand">Guardar</button></div>
  </form></div></div>

<div class="modal fade" id="horModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered">
  <form class="modal-content modal-x" method="post" action="<?= url('clases/guardarHorario') ?>">
    <?= csrf_field() ?><input type="hidden" name="id" id="hId">
    <div class="modal-header border-0"><h5 class="modal-title fw-bold" id="hTitle">Agregar horario</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="row g-3">
      <div class="col-12"><label class="form-lbl">Clase *</label><select name="clase_id" id="hClase" class="form-select" required>
        <?php foreach ($catalogo as $c): if ($c['estado']): ?><option value="<?= $c['id'] ?>"><?= e($c['nombre']) ?></option><?php endif; endforeach; ?></select></div>
      <div class="col-12"><label class="form-lbl">Día *</label><select name="dia_semana" id="hDia" class="form-select">
        <?php foreach ($dias as $n => $nom): ?><option value="<?= $n ?>"><?= $nom ?></option><?php endforeach; ?></select></div>
      <div class="col-6"><label class="form-lbl">Inicio *</label><input type="time" name="hora_inicio" id="hIni" class="form-control" required></div>
      <div class="col-6"><label class="form-lbl">Fin *</label><input type="time" name="hora_fin" id="hFin" class="form-control" required></div>
      <div class="col-12"><label class="form-lbl">Sala</label><input name="sala" id="hSala" class="form-control" maxlength="60" placeholder="Ej: Sala 1"></div>
    </div></div>
    <div class="modal-footer border-0"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand">Guardar</button></div>
  </form></div></div>
<script>
window.addEventListener('DOMContentLoaded', function () {
  var $ = function (i) { return document.getElementById(i); };
  function data(ev, a) { try { return JSON.parse(ev.relatedTarget.getAttribute(a) || '{}'); } catch (e) { return {}; } }
  $('claseModal').addEventListener('show.bs.modal', function (ev) {
    var d = data(ev, 'data-clase');
    $('clTitle').textContent = d.id ? 'Editar clase' : 'Nueva clase';
    $('clId').value = d.id || ''; $('clN').value = d.nombre || ''; $('clD').value = d.descripcion || '';
    $('clE').value = d.entrenador_id || ''; $('clC').value = d.cupo || 20; $('clK').value = d.color || '#ff5a1f';
  });
  $('horModal').addEventListener('show.bs.modal', function (ev) {
    var d = data(ev, 'data-hor');
    $('hTitle').textContent = d.id ? 'Editar horario' : 'Agregar horario';
    $('hId').value = d.id || ''; if (d.clase_id) $('hClase').value = d.clase_id;
    $('hDia').value = d.dia_semana || 1; $('hIni').value = d.hora_inicio || '07:00'; $('hFin').value = d.hora_fin || '08:00'; $('hSala').value = d.sala || '';
  });
});
</script>
