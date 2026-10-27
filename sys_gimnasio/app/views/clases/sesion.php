<?php $lleno = $activas >= $h['cupo']; $pct = min(100, round($activas * 100 / max(1, $h['cupo']))); $pasada = $fecha < date('Y-m-d');
$asist = count(array_filter($res, fn($r) => $r['estado'] === 'asistio')); ?>
<div class="page-head">
  <div>
    <a href="<?= url('clases') ?>" class="small text-muted text-decoration-none"><i class="bi bi-arrow-left"></i> Volver al calendario</a>
    <h1 class="page-title mt-1"><?= e($h['nombre']) ?></h1>
    <p class="page-sub"><?= Clase::dias()[(int)$h['dia_semana']] ?> <?= fdate($fecha) ?> · <?= substr($h['hora_inicio'], 0, 5) ?>–<?= substr($h['hora_fin'], 0, 5) ?> · <?= e($h['sala'] ?: 'Sin sala') ?> · <?= e(trim(($h['ent_nombres'] ?? '') . ' ' . ($h['ent_apellidos'] ?? '')) ?: 'Sin entrenador') ?></p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-soft" href="<?= url('clases/sesion/' . $h['id'] . '?fecha=' . $prev) ?>"><i class="bi bi-chevron-left"></i> Semana anterior</a>
    <a class="btn btn-soft" href="<?= url('clases/sesion/' . $h['id'] . '?fecha=' . $next) ?>">Semana siguiente <i class="bi bi-chevron-right"></i></a>
  </div>
</div>
<div class="row g-3 g-xl-4">
  <div class="col-lg-4">
    <div class="card-x mb-3">
      <h6 class="sec-title"><i class="bi bi-people"></i> Ocupación</h6>
      <div class="display-6 fw-bold"><?= $activas ?><small class="fs-5 text-muted">/<?= (int)$h['cupo'] ?></small></div>
      <div class="progress my-2" style="height:8px"><div class="progress-bar <?= $lleno ? 'bg-danger' : '' ?>" style="width:<?= $pct ?>%"></div></div>
      <div class="small text-muted"><?= $lleno ? 'Clase llena' : ($h['cupo'] - $activas) . ' cupos disponibles' ?> · <?= $asist ?> asistieron</div>
    </div>
    <?php if (!$pasada): ?>
    <div class="card-x">
      <h6 class="sec-title"><i class="bi bi-plus-circle"></i> Reservar cupo</h6>
      <form method="post" action="<?= url('clases/reservar/' . $h['id']) ?>" id="resForm"><?= csrf_field() ?>
        <input type="hidden" name="fecha" value="<?= e($fecha) ?>">
        <div class="ac" id="acSocio" data-url="<?= url('socios/buscar') ?>">
          <input type="hidden" name="socio_id">
          <div class="input-ico"><i class="bi bi-search"></i><input type="text" class="form-control" autocomplete="off" placeholder="Nombre, DNI o código…" <?= $lleno ? 'disabled' : '' ?>></div>
          <div class="ac-list d-none"></div>
        </div>
        <button class="btn btn-brand w-100 mt-3" <?= $lleno ? 'disabled' : '' ?>>Reservar</button>
      </form>
    </div>
    <?php endif; ?>
  </div>
  <div class="col-lg-8">
    <div class="card-x">
      <h6 class="sec-title"><i class="bi bi-list-check"></i> Lista de reservas</h6>
      <?php if (!$res): ?><div class="text-muted py-4 text-center">Aún no hay reservas para esta fecha.</div><?php else: ?>
      <div class="table-responsive"><table class="tbl"><thead><tr><th>Socio</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead><tbody>
      <?php foreach ($res as $r): $cls = ['reservada' => 'info', 'asistio' => 'success', 'cancelada' => 'secondary'][$r['estado']]; ?>
        <tr>
          <td><a class="fw-semibold text-reset text-decoration-none" href="<?= url('socios/ver/' . $r['socio_id']) ?>"><?= e($r['nombres'] . ' ' . $r['apellidos']) ?></a><div class="small text-muted"><?= e($r['codigo']) ?></div></td>
          <td><span class="badge-soft <?= $cls ?>"><?= ucfirst($r['estado'] === 'asistio' ? 'asistió' : $r['estado']) ?></span></td>
          <td class="text-end">
            <form method="post" action="<?= url('clases/reservaEstado/' . $r['id']) ?>" class="d-inline"><?= csrf_field() ?>
              <?php if ($r['estado'] === 'reservada'): ?>
                <?php if ($fecha <= date('Y-m-d')): ?><button name="estado" value="asistio" class="btn btn-soft btn-sm"><i class="bi bi-check2"></i> Asistió</button><?php endif; ?>
                <?php if (!$pasada): ?><button name="estado" value="cancelada" class="btn btn-outline-danger btn-sm">Cancelar</button><?php endif; ?>
              <?php else: ?><button name="estado" value="reservada" class="btn btn-outline-secondary btn-sm">Restablecer</button><?php endif; ?>
            </form>
          </td>
        </tr>
      <?php endforeach; ?></tbody></table></div>
      <?php endif; ?>
    </div>
  </div>
</div>
<script>
document.getElementById('resForm') && document.getElementById('resForm').addEventListener('submit', function (e) {
  if (!this.querySelector('input[name=socio_id]').value) { e.preventDefault(); this.querySelector('input[type=text]').focus(); }
});
</script>
