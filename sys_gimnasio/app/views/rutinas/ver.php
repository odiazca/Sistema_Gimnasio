<?php $wa = fn($tel) => wa_link($tel, Rutina::texto($r, $det)); ?>
<div class="page-head no-print">
  <div><a href="<?= url('rutinas') ?>" class="small text-muted text-decoration-none"><i class="bi bi-arrow-left"></i> Volver a rutinas</a><h1 class="page-title mt-1"><?= e($r['nombre']) ?></h1>
    <p class="page-sub"><?= e(implode(' · ', array_filter([$r['objetivo'], $r['nivel'], $r['descripcion']]))) ?></p></div>
  <div class="d-flex gap-2"><button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button><a href="<?= url('rutinas/editar/' . $r['id']) ?>" class="btn btn-brand"><i class="bi bi-pencil"></i> Editar</a></div>
</div>
<div class="d-none d-print-block mb-3"><h2><?= e($r['nombre']) ?></h2><div><?= e(implode(' · ', array_filter([$r['objetivo'], $r['nivel']]))) ?></div></div>
<div class="row g-3 g-xl-4">
  <div class="col-xl-8"><div class="row g-3">
    <?php foreach ($det as $dia => $ex): ?>
    <div class="col-12"><div class="card-x p-0"><div class="p-3 pb-2"><h6 class="sec-title mb-0"><i class="bi bi-calendar-day"></i> Día <?= (int)$dia ?></h6></div>
      <div class="table-responsive"><table class="table tbl align-middle mb-0 tbl-pad"><thead><tr><th>Ejercicio</th><th class="text-center">Series</th><th class="text-center">Repet.</th><th class="text-center d-none d-sm-table-cell">Descanso</th></tr></thead><tbody>
      <?php foreach ($ex as $x): ?><tr><td><span class="fw-semibold"><?= e($x['nombre']) ?></span> <?php if ($x['video_url']): ?><a href="<?= e($x['video_url']) ?>" target="_blank" rel="noopener noreferrer" class="no-print"><i class="bi bi-play-circle-fill text-danger"></i></a><?php endif; ?>
        <div class="small text-muted"><?= e($x['grupo']) ?><?= $x['equipo'] ? ' · ' . e($x['equipo']) : '' ?></div></td>
        <td class="text-center fw-bold"><?= (int)$x['series'] ?></td><td class="text-center fw-bold"><?= e($x['repeticiones']) ?></td><td class="text-center d-none d-sm-table-cell"><?= $x['descanso_seg'] ? (int)$x['descanso_seg'] . ' s' : '—' ?></td></tr><?php endforeach; ?>
      </tbody></table></div></div></div>
    <?php endforeach; ?>
  </div></div>
  <div class="col-xl-4 no-print">
    <?php if ($r['estado']): ?>
    <div class="card-x mb-3"><h6 class="sec-title"><i class="bi bi-person-plus"></i> Asignar a un socio</h6>
      <form method="post" action="<?= url('rutinas/asignar/' . $r['id']) ?>" id="asForm"><?= csrf_field() ?>
        <div class="ac" id="acSocio" data-url="<?= url('socios/buscar') ?>"><input type="hidden" name="socio_id">
          <div class="input-ico"><i class="bi bi-search"></i><input type="text" class="form-control" autocomplete="off" placeholder="Nombre, DNI o código…"></div><div class="ac-list d-none"></div></div>
        <button class="btn btn-brand w-100 mt-3">Asignar rutina</button></form></div>
    <?php endif; ?>
    <div class="card-x"><h6 class="sec-title"><i class="bi bi-people"></i> Socios con esta rutina</h6>
      <?php if (!$asig): ?><div class="small text-muted">Aún no se ha asignado a nadie.</div><?php endif; ?>
      <?php foreach ($asig as $a): ?>
        <div class="d-flex align-items-center gap-2 py-2 border-bottom"><div class="min-w-0 flex-grow-1"><a class="fw-semibold text-reset text-decoration-none" href="<?= url('socios/ver/' . $a['socio_id']) ?>"><?= e($a['nombres'] . ' ' . $a['apellidos']) ?></a>
          <div class="small text-muted">desde <?= fdate($a['fecha_inicio']) ?> · <?= $a['estado'] === 'activa' ? '<span class="text-success">activa</span>' : 'finalizada' ?></div></div>
          <?php if ($a['estado'] === 'activa'): ?>
            <?php if ($a['telefono']): ?><a class="btn btn-soft btn-sm" target="_blank" rel="noopener" href="<?= e($wa($a['telefono'])) ?>" title="Enviar por WhatsApp"><i class="bi bi-whatsapp"></i></a><?php endif; ?>
            <form method="post" action="<?= url('rutinas/finalizar/' . $a['id']) ?>" data-confirm="¿Finalizar esta asignación?"><?= csrf_field() ?><button class="btn btn-outline-secondary btn-sm" title="Finalizar"><i class="bi bi-check2-circle"></i></button></form>
          <?php endif; ?></div>
      <?php endforeach; ?></div>
  </div>
</div>
<style>@media print{.no-print,.sidebar,.topbar,.flash-stack,footer{display:none!important}.main-wrap{margin:0!important;padding:0!important}.content{padding:0!important}.card-x{box-shadow:none;border:1px solid #ccc;break-inside:avoid}body{background:#fff!important;color:#000!important}}</style>
<script>
var f = document.getElementById('asForm'); f && f.addEventListener('submit', function (e) { if (!f.querySelector('input[name=socio_id]').value) { e.preventDefault(); f.querySelector('input[type=text]').focus(); } });
</script>
