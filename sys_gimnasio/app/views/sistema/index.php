<link href="<?= ASSETS_URL ?>/css/sistema.css?v=<?= APP_VERSION ?>" rel="stylesheet">
<?php $tipoN = ['manual' => 'Manual', 'auto' => 'Automática', 'seguridad' => 'De seguridad']; ?>
<div class="page-head"><div><h1 class="page-title">Respaldo y sistema</h1><p class="page-sub">Protege tu información, recupérala cuando la necesites o reinicia el sistema para una empresa nueva.</p></div>
  <a href="<?= url('configuracion') ?>" class="btn btn-soft"><i class="bi bi-gear"></i> Configuración</a></div>

<div class="sys-hero mb-4">
  <div class="d-flex align-items-center gap-3 position-relative" style="z-index:1"><div class="ico"><i class="bi bi-shield-check"></i></div>
    <div><h2>Tus datos, siempre protegidos</h2><p><?= $ultimo ? 'Última copia: ' . fdate($ultimo['fecha'], 'd/m/Y') . ' a las ' . fdate($ultimo['fecha'], 'H:i') . ' (' . ($tipoN[$ultimo['tipo']] ?? '') . ').' : 'Aún no hay copias de seguridad. Crea la primera ahora.' ?></p></div></div>
  <div class="sys-stats">
    <div class="sys-stat"><small>Base de datos</small><b><?= e(DB_NAME) ?></b></div>
    <div class="sys-stat"><small>Tablas · registros</small><b><?= (int)$info['tablas'] ?> · <?= fmt_num($info['filas'], 0) ?></b></div>
    <div class="sys-stat"><small>Tamaño de datos</small><b><?= Backup::bytes($info['bytes']) ?></b></div>
    <div class="sys-stat"><small>Imágenes subidas</small><b><?= Backup::bytes($uploadsBytes) ?></b></div>
  </div>
</div>
<?php if (!$dirOk): ?><div class="alert alert-danger">La carpeta de respaldos no tiene permisos de escritura. Da permisos de escritura a <code>storage/backups</code>.</div><?php endif; ?>

<div class="row g-3 g-xl-4 mb-4">
  <div class="col-md-4"><div class="sys-card"><div class="ic ic-b"><i class="bi bi-cloud-arrow-down-fill"></i></div><h5>Crear copia de seguridad</h5>
    <p>Guarda toda la base de datos<?= $zip ? ' y las imágenes (logo y fotos)' : '' ?> en un archivo que puedes descargar y guardar fuera del servidor.</p>
    <form method="post" action="<?= url('sistema/crear') ?>"><?= csrf_field() ?>
      <?php if ($zip): ?><div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="imagenes" value="1" id="incImg" checked><label class="form-check-label small" for="incImg">Incluir logo y fotos de socios</label></div><?php endif; ?>
      <button class="btn btn-brand w-100" <?= $dirOk ? '' : 'disabled' ?>><i class="bi bi-plus-circle"></i> Crear copia ahora</button></form></div></div>
  <div class="col-md-4"><div class="sys-card"><div class="ic ic-r"><i class="bi bi-arrow-counterclockwise"></i></div><h5>Restaurar una copia</h5>
    <p>Devuelve el sistema al estado de una copia guardada o de un archivo que subas. Antes se hace una copia de seguridad automática del estado actual.</p>
    <button class="btn btn-soft w-100" data-bs-toggle="modal" data-bs-target="#mRest"><i class="bi bi-upload"></i> Restaurar…</button></div></div>
  <div class="col-md-4"><div class="sys-card danger"><div class="ic ic-d"><i class="bi bi-exclamation-octagon-fill"></i></div><h5>Resetear para empresa nueva</h5>
    <p>Borra socios, pagos, ventas, asistencias y demás movimientos para empezar desde cero. Puedes conservar usuarios, configuración y catálogos.</p>
    <button class="btn btn-danger-soft w-100" data-bs-toggle="modal" data-bs-target="#mReset"><i class="bi bi-trash3"></i> Resetear sistema…</button></div></div>
</div>

<div class="row g-3 g-xl-4">
  <div class="col-lg-8"><div class="card-x p-0">
    <div class="p-3 pb-2 d-flex justify-content-between align-items-center"><h6 class="sec-title mb-0"><i class="bi bi-archive"></i> Copias guardadas (<?= count($lista) ?>)</h6></div>
    <div class="table-responsive"><table class="table tbl align-middle mb-0 tbl-pad">
      <thead><tr><th>Archivo</th><th>Fecha</th><th class="d-none d-md-table-cell">Tipo</th><th>Tamaño</th><th class="text-end">Acciones</th></tr></thead><tbody>
      <?php if (!$lista): ?><tr><td colspan="5" class="text-center text-muted py-5"><i class="bi bi-inbox fs-2 d-block"></i>Todavía no hay copias guardadas.</td></tr><?php endif; ?>
      <?php foreach ($lista as $b): ?><tr>
        <td><div class="sys-file"><i class="bi <?= $b['zip'] ? 'bi-file-earmark-zip-fill' : 'bi-filetype-sql' ?>"></i><span class="small fw-semibold text-break"><?= e($b['archivo']) ?></span></div></td>
        <td class="small text-nowrap"><?= fdate($b['fecha'], 'd/m/Y') ?><br><span class="text-muted"><?= fdate($b['fecha'], 'H:i') ?></span></td>
        <td class="d-none d-md-table-cell"><span class="sys-tag <?= e($b['tipo']) ?>"><?= e($tipoN[$b['tipo']] ?? $b['tipo']) ?></span></td>
        <td class="small text-nowrap"><?= Backup::bytes($b['bytes']) ?></td>
        <td class="text-end text-nowrap">
          <a class="btn btn-sm btn-soft" title="Descargar" href="<?= url('sistema/descargar/' . $b['archivo']) ?>"><i class="bi bi-download"></i></a>
          <button class="btn btn-sm btn-soft" title="Restaurar" data-restaurar="<?= e($b['archivo']) ?>"><i class="bi bi-arrow-counterclockwise"></i></button>
          <form method="post" action="<?= url('sistema/eliminar') ?>" class="d-inline" data-confirm="¿Eliminar esta copia de seguridad?"><?= csrf_field() ?><input type="hidden" name="archivo" value="<?= e($b['archivo']) ?>"><button class="btn btn-sm btn-danger-soft" title="Eliminar"><i class="bi bi-trash"></i></button></form></td>
      </tr><?php endforeach; ?></tbody></table></div></div></div>

  <div class="col-lg-4">
    <div class="card-x mb-3"><h6 class="sec-title"><i class="bi bi-clock-history"></i> Respaldo automático</h6>
      <form method="post" action="<?= url('sistema/auto') ?>"><?= csrf_field() ?>
        <label class="form-lbl">Frecuencia</label>
        <select name="backup_auto" class="form-select mb-3"><?php foreach (['off' => 'Desactivado', 'diario' => 'Una vez al día', 'semanal' => 'Una vez por semana'] as $k => $t): ?><option value="<?= $k ?>" <?= $auto === $k ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?></select>
        <label class="form-lbl">Copias automáticas a conservar</label>
        <input type="number" name="backup_retencion" min="1" max="60" class="form-control mb-2" value="<?= (int)$ret ?>">
        <div class="form-text mb-3">Se crea cuando el administrador abre el panel y toca una nueva copia.<?= $ultimoAuto ? ' Última: ' . fdate($ultimoAuto, 'd/m/Y H:i') . '.' : '' ?></div>
        <button class="btn btn-brand w-100"><i class="bi bi-check2"></i> Guardar</button></form></div>
    <div class="card-x"><h6 class="sec-title"><i class="bi bi-info-circle"></i> Información del sistema</h6>
      <ul class="list-unstyled small mb-0">
        <li class="d-flex justify-content-between py-1"><span class="text-muted">Versión de la app</span><b><?= e(APP_VERSION) ?></b></li>
        <li class="d-flex justify-content-between py-1"><span class="text-muted">PHP</span><b><?= e($php) ?></b></li>
        <li class="d-flex justify-content-between py-1"><span class="text-muted">Base de datos</span><b><?= e($info['version']) ?></b></li>
        <li class="d-flex justify-content-between py-1"><span class="text-muted">Subida máxima</span><b><?= Backup::bytes($maxSubida) ?></b></li>
        <li class="d-flex justify-content-between py-1"><span class="text-muted">Formato de copia</span><b><?= $zip ? '.zip (datos + imágenes)' : '.sql (solo datos)' ?></b></li>
      </ul>
      <div class="small text-muted mt-3"><i class="bi bi-lock"></i> Las copias contienen datos personales. Descárgalas y guárdalas en un lugar seguro.</div></div>
  </div>
</div>

<!-- Restaurar -->
<div class="modal fade" id="mRest" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="post" action="<?= url('sistema/restaurar') ?>" enctype="multipart/form-data"><?= csrf_field() ?>
  <div class="modal-header"><h5 class="modal-title"><i class="bi bi-arrow-counterclockwise text-primary"></i> Restaurar copia de seguridad</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="sys-warn mb-3"><b>Reemplaza los datos actuales</b> por los de la copia. Antes se guarda una copia automática del estado actual y, si algo falla, se revierte solo.</div>
    <label class="form-lbl">Copia guardada</label>
    <select name="archivo_guardado" id="selRest" class="form-select mb-2"><option value="">— Elegir —</option><?php foreach ($lista as $b): ?><option value="<?= e($b['archivo']) ?>"><?= e(fdate($b['fecha'], 'd/m/Y H:i') . ' · ' . ($tipoN[$b['tipo']] ?? '') . ' · ' . Backup::bytes($b['bytes'])) ?></option><?php endforeach; ?></select>
    <div class="text-center small text-muted my-2">— o sube un archivo —</div>
    <div class="sys-drop mb-3"><input type="file" name="archivo" class="form-control form-control-sm" accept=".zip,.sql"><div class="mt-2">Respaldo de FitCore (.zip o .sql). Máx. <?= Backup::bytes($maxSubida) ?>.</div></div>
    <label class="form-lbl">Tu contraseña</label><input type="password" name="password" class="form-control mb-3" required autocomplete="current-password">
    <label class="form-lbl">Escribe <b>RESTAURAR</b> para confirmar</label><input name="frase" class="form-control" required autocomplete="off" placeholder="RESTAURAR">
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand"><i class="bi bi-arrow-counterclockwise"></i> Restaurar</button></div>
</form></div></div>

<!-- Resetear -->
<div class="modal fade" id="mReset" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="post" action="<?= url('sistema/resetear') ?>"><?= csrf_field() ?>
  <div class="modal-header"><h5 class="modal-title"><i class="bi bi-exclamation-octagon-fill text-danger"></i> Resetear para empresa nueva</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="sys-warn mb-3"><b>Se borrarán</b> socios, membresías, pagos, asistencias, ventas, caja, gastos, evaluaciones, CRM, notificaciones, anulaciones y la bitácora. Se guardará antes una copia de seguridad.</div>
    <div class="small fw-semibold mb-2">¿Qué quieres conservar?</div>
    <label class="sys-opt"><input class="form-check-input" type="checkbox" name="k_usuarios" value="1" checked><span><b>Usuarios y roles</b><br><small class="text-muted">Si lo desmarcas solo queda tu usuario administrador.</small></span></label>
    <label class="sys-opt"><input class="form-check-input" type="checkbox" name="k_config" value="1" checked><span><b>Datos de la empresa y configuración</b><br><small class="text-muted">Nombre, logo, moneda, formatos. Desmárcalo para empezar con una empresa en blanco.</small></span></label>
    <label class="sys-opt"><input class="form-check-input" type="checkbox" name="k_catalogos" value="1" checked><span><b>Catálogos</b><br><small class="text-muted">Planes, productos, clases, entrenadores y rutinas.</small></span></label>
    <label class="form-lbl mt-3">Tu contraseña</label><input type="password" name="password" class="form-control mb-3" required autocomplete="current-password">
    <label class="form-lbl">Escribe <b>RESETEAR</b> para confirmar</label><input name="frase" class="form-control" required autocomplete="off" placeholder="RESETEAR">
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-danger"><i class="bi bi-trash3"></i> Resetear sistema</button></div>
</form></div></div>
<script>
document.addEventListener('DOMContentLoaded',function(){
  document.querySelectorAll('[data-restaurar]').forEach(function(b){b.addEventListener('click',function(){
    document.getElementById('selRest').value=b.dataset.restaurar;new bootstrap.Modal(document.getElementById('mRest')).show();});});
});
</script>
