<div class="page-head"><div><h1 class="page-title">Mi perfil</h1><p class="page-sub">Tu cuenta y seguridad.</p></div></div>
<div class="row g-3 g-xl-4">
  <div class="col-lg-5"><div class="card-x text-center py-4"><div class="avatar-lg mx-auto mb-3" style="width:72px;height:72px;font-size:1.5rem"><?= e(iniciales($u['nombre'])) ?></div>
    <h5 class="fw-bold mb-0"><?= e($u['nombre']) ?></h5><div class="text-muted">@<?= e($u['usuario']) ?></div><span class="badge-soft secondary mt-2 d-inline-block"><?= e($u['rol']) ?></span>
    <div class="small text-muted mt-3"><?= e($u['email'] ?: 'Sin correo') ?><br>Último acceso: <?= $u['ultimo_login'] ? fdate($u['ultimo_login'], 'd/m/Y H:i') : '—' ?></div></div></div>
  <div class="col-lg-7"><form class="card-x" method="post" action="<?= url('perfil/cambiar') ?>" autocomplete="off"><?= csrf_field() ?>
    <h6 class="sec-title"><i class="bi bi-key"></i> Cambiar contraseña</h6>
    <div class="row g-3"><div class="col-12"><label class="form-lbl">Contraseña actual</label><input type="password" name="actual" class="form-control" required autocomplete="current-password"></div>
      <div class="col-md-6"><label class="form-lbl">Nueva contraseña</label><input type="password" name="nueva" class="form-control" required minlength="8" autocomplete="new-password"></div>
      <div class="col-md-6"><label class="form-lbl">Repetir nueva</label><input type="password" name="repetir" class="form-control" required minlength="8" autocomplete="new-password"></div></div>
    <div class="small text-muted mt-2">Mínimo 8 caracteres, distinta de tu usuario y de la actual.</div>
    <button class="btn btn-brand mt-3">Actualizar contraseña</button></form></div>
</div>
