<div class="page-head">
  <div><h1 class="page-title">Usuarios y roles</h1><p class="page-sub">Quién puede entrar al sistema y a qué módulos.</p></div>
  <?php if ($vista === 'usuarios'): ?><button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#usrModal" data-usr='{}'><i class="bi bi-person-plus"></i> Nuevo usuario</button><?php endif; ?>
</div>
<div class="seg mb-3">
  <a href="<?= url('usuarios') ?>" class="<?= $vista === 'usuarios' ? 'on' : '' ?>"><i class="bi bi-people"></i> Usuarios</a>
  <a href="<?= url('usuarios?vista=permisos') ?>" class="<?= $vista === 'permisos' ? 'on' : '' ?>"><i class="bi bi-shield-check"></i> Permisos por rol</a>
  <a href="<?= url('usuarios?vista=bitacora') ?>" class="<?= $vista === 'bitacora' ? 'on' : '' ?>"><i class="bi bi-journal-text"></i> Bitácora</a>
</div>

<?php if ($vista === 'usuarios'): $me = (int)Auth::user()['id']; ?>
<div class="card-x p-0"><div class="table-responsive"><table class="table tbl align-middle mb-0 tbl-pad">
  <thead><tr><th>Usuario</th><th>Rol</th><th class="d-none d-md-table-cell">Último acceso</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead><tbody>
  <?php foreach ($rows as $u): ?>
    <tr class="<?= $u['estado'] ? '' : 'opacity-50' ?>"><td><div class="d-flex align-items-center gap-2"><span class="avatar sm"><?= e(iniciales($u['nombre'])) ?></span><div><div class="fw-semibold"><?= e($u['nombre']) ?> <?= (int)$u['id'] === $me ? '<span class="badge-soft info">Tú</span>' : '' ?></div><div class="small text-muted">@<?= e($u['usuario']) ?><?= $u['email'] ? ' · ' . e($u['email']) : '' ?></div></div></div></td>
      <td><span class="badge-soft <?= (int)$u['rol_id'] === 1 ? 'danger' : 'secondary' ?>"><?= e($u['rol']) ?></span></td>
      <td class="d-none d-md-table-cell small"><?= $u['ultimo_login'] ? fdate($u['ultimo_login'], 'd/m/Y H:i') : '<span class="text-muted">Nunca</span>' ?></td>
      <td><span class="badge-soft <?= $u['estado'] ? 'success' : 'secondary' ?>"><?= $u['estado'] ? 'Activo' : 'Inactivo' ?></span></td>
      <td class="text-end text-nowrap">
        <button class="btn btn-outline-secondary btn-sm" title="Editar" data-bs-toggle="modal" data-bs-target="#usrModal" data-usr='<?= e(json_encode(['id' => $u['id'], 'nombre' => $u['nombre'], 'usuario' => $u['usuario'], 'email' => $u['email'], 'rol_id' => $u['rol_id']])) ?>'><i class="bi bi-pencil"></i></button>
        <button class="btn btn-outline-secondary btn-sm" title="Restablecer contraseña" data-bs-toggle="modal" data-bs-target="#pwModal" data-id="<?= $u['id'] ?>" data-nombre="<?= e($u['nombre']) ?>"><i class="bi bi-key"></i></button>
        <?php if ((int)$u['id'] !== $me): ?><form method="post" action="<?= url('usuarios/estado/' . $u['id']) ?>" class="d-inline" data-confirm="<?= $u['estado'] ? '¿Desactivar a ' . e($u['nombre']) . '? No podrá iniciar sesión.' : '¿Reactivar a ' . e($u['nombre']) . '?' ?>"><?= csrf_field() ?>
          <button class="btn btn-outline-secondary btn-sm" title="<?= $u['estado'] ? 'Desactivar' : 'Activar' ?>"><i class="bi <?= $u['estado'] ? 'bi-person-slash' : 'bi-person-check' ?>"></i></button></form><?php endif; ?></td></tr>
  <?php endforeach; ?></tbody></table></div></div>

<div class="modal fade" id="usrModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content modal-x" method="post" action="<?= url('usuarios/guardar') ?>" autocomplete="off"><?= csrf_field() ?><input type="hidden" name="id" id="uId">
  <div class="modal-header border-0"><h5 class="modal-title fw-bold" id="uTitle">Nuevo usuario</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><div class="row g-3">
    <div class="col-12"><label class="form-lbl">Nombre completo *</label><input name="nombre" id="uNom" class="form-control" required maxlength="120"></div>
    <div class="col-6"><label class="form-lbl">Usuario *</label><input name="usuario" id="uUsr" class="form-control" required maxlength="40" autocomplete="off"></div>
    <div class="col-6"><label class="form-lbl">Rol *</label><select name="rol_id" id="uRol" class="form-select"><?php foreach ($roles as $r): ?><option value="<?= $r['id'] ?>"><?= e($r['nombre']) ?></option><?php endforeach; ?></select></div>
    <div class="col-12"><label class="form-lbl">Correo</label><input type="email" name="email" id="uMail" class="form-control" maxlength="120"></div>
    <div class="col-12" id="uPassBox"><label class="form-lbl">Contraseña inicial * <span class="text-muted fw-normal">(mín. 8 caracteres)</span></label><input type="password" name="password" id="uPass" class="form-control" minlength="8" autocomplete="new-password"></div>
  </div></div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand">Guardar</button></div></form></div></div>
<div class="modal fade" id="pwModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content modal-x" method="post" id="pwForm" autocomplete="off"><?= csrf_field() ?>
  <div class="modal-header border-0"><div><h5 class="modal-title fw-bold">Restablecer contraseña</h5><div class="small text-muted" id="pwName"></div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><label class="form-lbl">Nueva contraseña * <span class="text-muted fw-normal">(mín. 8 caracteres)</span></label><input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password"></div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand">Restablecer</button></div></form></div></div>
<script>
window.addEventListener('DOMContentLoaded', function () {
  var $ = function (i) { return document.getElementById(i); };
  $('usrModal').addEventListener('show.bs.modal', function (ev) {
    var d = {}; try { d = JSON.parse(ev.relatedTarget.getAttribute('data-usr') || '{}'); } catch (e) {}
    $('uTitle').textContent = d.id ? 'Editar usuario' : 'Nuevo usuario'; $('uId').value = d.id || ''; $('uNom').value = d.nombre || ''; $('uUsr').value = d.usuario || '';
    $('uMail').value = d.email || ''; $('uRol').value = d.rol_id || 2; $('uPass').value = ''; $('uPass').required = !d.id; $('uPassBox').style.display = d.id ? 'none' : '';
  });
  $('pwModal').addEventListener('show.bs.modal', function (ev) { var b = ev.relatedTarget; $('pwForm').action = '<?= url('usuarios/password') ?>/' + b.dataset.id; $('pwName').textContent = b.dataset.nombre; });
});
</script>

<?php elseif ($vista === 'permisos'): ?>
<form method="post" action="<?= url('usuarios/guardarPermisos') ?>"><?= csrf_field() ?>
<div class="card-x p-0"><div class="table-responsive"><table class="table tbl align-middle mb-0 tbl-pad">
  <thead><tr><th>Módulo</th><?php foreach ($roles as $r): ?><th class="text-center"><?= e($r['nombre']) ?></th><?php endforeach; ?></tr></thead><tbody>
  <?php foreach ($mods as $grupo => $items): ?>
    <tr><td colspan="<?= count($roles) + 1 ?>" class="small fw-bold text-uppercase text-muted" style="background:var(--card-2)"><?= e($grupo) ?></td></tr>
    <?php foreach ($items as $slug => $nom): $adm = in_array($slug, Permisos::SOLO_ADMIN, true); ?>
    <tr><td class="fw-semibold"><?= e($nom) ?></td>
      <?php foreach ($roles as $r): $rid = (int)$r['id']; ?>
        <td class="text-center"><?php if ($rid === 1): ?><i class="bi bi-check-circle-fill text-success" title="El administrador siempre tiene acceso"></i>
          <?php elseif ($adm): ?><i class="bi bi-lock-fill text-muted" title="Solo administrador"></i>
          <?php else: ?><input class="form-check-input" type="checkbox" name="perm[<?= $rid ?>][<?= e($slug) ?>]" value="1" <?= !empty($mapa[$rid][$slug]) ? 'checked' : '' ?>><?php endif; ?></td>
      <?php endforeach; ?></tr>
    <?php endforeach; ?>
  <?php endforeach; ?></tbody></table></div></div>
<div class="d-flex align-items-center gap-3 mt-3 flex-wrap"><button class="btn btn-brand btn-lg"><i class="bi bi-check2"></i> Guardar permisos</button><span class="small text-muted">Usuarios y Configuración son exclusivos del administrador.</span></div>
</form>

<?php else: ?>
<div class="card-x p-0">
  <form class="filter-bar" method="get" action="<?= url('usuarios') ?>"><input type="hidden" name="vista" value="bitacora">
    <div class="flex-fill" style="min-width:160px"><label class="form-lbl mb-1 small">Buscar</label><input type="search" name="q" value="<?= e($f['q']) ?>" class="form-control form-control-sm" placeholder="Usuario o detalle"></div>
    <div><label class="form-lbl mb-1 small">Acción</label><select name="accion" class="form-select form-select-sm"><option value="">Todas</option><?php foreach ($acciones as $a): ?><option <?= $f['accion'] === $a ? 'selected' : '' ?>><?= e($a) ?></option><?php endforeach; ?></select></div>
    <div class="align-self-end d-flex gap-2"><button class="btn btn-soft btn-sm"><i class="bi bi-funnel"></i> Filtrar</button><a href="<?= url('usuarios?vista=bitacora') ?>" class="btn btn-outline-secondary btn-sm">Limpiar</a></div></form>
  <div class="table-responsive"><table class="table tbl align-middle mb-0 tbl-pad"><thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th class="d-none d-md-table-cell">Detalle</th><th class="d-none d-lg-table-cell">IP</th></tr></thead><tbody>
  <?php if (!$rows): ?><tr><td colspan="5" class="text-center text-muted py-5">Sin registros todavía.</td></tr><?php endif; ?>
  <?php foreach ($rows as $b): ?><tr><td class="text-nowrap"><?= fdate($b['fecha'], 'd/m/Y H:i') ?></td><td><?= e($b['usuario'] ?: '—') ?></td><td><span class="badge-soft secondary"><?= e($b['accion']) ?></span></td><td class="d-none d-md-table-cell small"><?= e($b['detalle'] ?: '') ?></td><td class="d-none d-lg-table-cell small text-muted"><?= e($b['ip'] ?: '') ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
  <div class="p-3 d-flex justify-content-between align-items-center flex-wrap gap-2"><span class="small text-muted"><?= $pg['total'] ?> registro<?= $pg['total'] == 1 ? '' : 's' ?></span><?= pager($pg) ?></div>
</div>
<?php endif; ?>
