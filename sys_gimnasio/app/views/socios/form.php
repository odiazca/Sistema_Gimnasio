<?php $isEdit = $id > 0; ?>
<div class="page-head">
  <div><h1 class="page-title"><?= $isEdit ? 'Editar socio' : 'Nuevo socio' ?></h1>
    <p class="page-sub"><?= $isEdit ? e(($s['nombres'] ?? '') . ' ' . ($s['apellidos'] ?? '')) : 'Registra al socio e inscríbelo a un plan en un solo paso.' ?></p></div>
  <a href="<?= url($isEdit ? 'socios/ver/' . $id : 'socios') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger"><b>Revisa los datos:</b><ul class="mb-0 mt-1"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" action="<?= url('socios/guardar') ?>" enctype="multipart/form-data" novalidate>
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= $id ?>">
  <?php if (!empty($s['lead_id'])): ?><input type="hidden" name="lead_id" value="<?= (int)$s['lead_id'] ?>">
  <div class="alert alert-info d-flex align-items-center gap-2"><i class="bi bi-bullseye fs-5"></i><div>Registrando un prospecto del CRM: al guardar quedará como <b>Ganado</b> y enlazado a este socio.</div></div><?php endif; ?>
  <div class="row g-4">
    <div class="col-xl-8">
      <div class="card-x mb-4">
        <h6 class="sec-title"><i class="bi bi-person-vcard"></i> Datos personales</h6>
        <div class="row g-3">
          <div class="col-md-4"><label class="form-lbl">Documento (DNI / CE)</label><input name="dni" class="form-control" maxlength="12" value="<?= v($s, 'dni') ?>" placeholder="12345678"></div>
          <div class="col-md-4"><label class="form-lbl">Nombres *</label><input name="nombres" class="form-control" required value="<?= v($s, 'nombres') ?>"></div>
          <div class="col-md-4"><label class="form-lbl">Apellidos *</label><input name="apellidos" class="form-control" required value="<?= v($s, 'apellidos') ?>"></div>
          <div class="col-md-4"><label class="form-lbl">Fecha de nacimiento</label><input type="date" name="fecha_nacimiento" class="form-control" max="<?= date('Y-m-d') ?>" value="<?= v($s, 'fecha_nacimiento') ?>"></div>
          <div class="col-md-4"><label class="form-lbl">Género</label>
            <select name="genero" class="form-select"><?php foreach (['M' => 'Masculino', 'F' => 'Femenino', 'O' => 'Otro'] as $k => $l): ?><option value="<?= $k ?>" <?= ($s['genero'] ?? 'M') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
          <div class="col-md-4"><label class="form-lbl">Objetivo</label>
            <input name="objetivo" class="form-control" list="objetivos" value="<?= v($s, 'objetivo') ?>" placeholder="Ej: Perder peso">
            <datalist id="objetivos"><option>Perder peso</option><option>Ganar masa muscular</option><option>Tonificar</option><option>Salud y bienestar</option><option>Rendimiento deportivo</option><option>Rehabilitación</option></datalist></div>
        </div>
      </div>

      <div class="card-x mb-4">
        <h6 class="sec-title"><i class="bi bi-telephone"></i> Contacto y emergencia</h6>
        <div class="row g-3">
          <div class="col-md-4"><label class="form-lbl">Teléfono / WhatsApp</label><input name="telefono" class="form-control" value="<?= v($s, 'telefono') ?>" placeholder="999 999 999"></div>
          <div class="col-md-8"><label class="form-lbl">Correo electrónico</label><input type="email" name="email" class="form-control" value="<?= v($s, 'email') ?>"></div>
          <div class="col-12"><label class="form-lbl">Dirección</label><input name="direccion" class="form-control" value="<?= v($s, 'direccion') ?>"></div>
          <div class="col-md-6"><label class="form-lbl">Contacto de emergencia</label><input name="contacto_emergencia" class="form-control" value="<?= v($s, 'contacto_emergencia') ?>"></div>
          <div class="col-md-6"><label class="form-lbl">Teléfono de emergencia</label><input name="telefono_emergencia" class="form-control" value="<?= v($s, 'telefono_emergencia') ?>"></div>
          <div class="col-12"><label class="form-lbl">Observaciones (lesiones, condiciones médicas relevantes)</label><textarea name="observaciones" rows="3" class="form-control"><?= v($s, 'observaciones') ?></textarea></div>
        </div>
      </div>

      <?php if (!$isEdit): ?>
      <div class="card-x">
        <h6 class="sec-title"><i class="bi bi-award"></i> Inscripción inmediata <small class="text-muted fw-normal">(opcional)</small></h6>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-lbl">Plan</label>
            <select name="plan_id" id="planSel" class="form-select">
              <option value="">— Registrar sin membresía —</option>
              <?php foreach ($planes as $p): ?><option value="<?= $p['id'] ?>" data-precio="<?= $p['precio'] ?>" <?= ($s['plan_id'] ?? '') == $p['id'] ? 'selected' : '' ?>><?= e($p['nombre']) ?> · <?= money($p['precio']) ?> · <?= (int)$p['duracion_dias'] ?> días</option><?php endforeach; ?>
            </select></div>
          <div class="col-md-3"><label class="form-lbl">Fecha de inicio</label><input type="date" name="fecha_inicio" class="form-control" value="<?= v($s, 'fecha_inicio', date('Y-m-d')) ?>"></div>
          <div class="col-md-3"><label class="form-lbl">Descuento (<?= APP_CURRENCY ?>)</label><input type="number" step="0.01" min="0" name="descuento" id="descIn" class="form-control" value="<?= v($s, 'descuento', '0') ?>"></div>
          <div class="col-md-4"><label class="form-lbl">Monto que paga ahora (<?= APP_CURRENCY ?>)</label><input type="number" step="0.01" min="0" name="monto_pago" id="montoIn" class="form-control" value="<?= v($s, 'monto_pago', '0') ?>"></div>
          <div class="col-md-4"><label class="form-lbl">Método de pago</label>
            <select name="metodo" class="form-select"><?php foreach (Pago::metodos() as $k => $l): ?><option value="<?= $k ?>" <?= ($s['metodo'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
          <div class="col-md-4"><label class="form-lbl">Total a pagar</label><div class="total-box" id="totalBox"><?= APP_CURRENCY ?> 0.00</div></div>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <div class="col-xl-4">
      <div class="card-x mb-4">
        <h6 class="sec-title"><i class="bi bi-camera"></i> Foto</h6>
        <div class="photo-drop" id="photoDrop">
          <?php if (!empty($s['foto'])): ?><img id="photoPrev" src="<?= e(foto_url($s['foto'])) ?>" alt="">
          <?php else: ?><img id="photoPrev" class="d-none" alt=""><i class="bi bi-person-bounding-box" id="photoIco"></i><?php endif; ?>
        </div>
        <input type="file" name="foto" id="photoIn" accept="image/jpeg,image/png,image/webp" class="form-control mt-3">
        <small class="text-muted">JPG, PNG o WEBP · máx. 2 MB</small>
      </div>
      <div class="card-x">
        <h6 class="sec-title"><i class="bi bi-toggle-on"></i> Estado</h6>
        <select name="estado" class="form-select"><?php foreach (['activo' => 'Activo', 'inactivo' => 'Inactivo', 'suspendido' => 'Suspendido'] as $k => $l): ?><option value="<?= $k ?>" <?= ($s['estado'] ?? 'activo') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
        <div class="d-grid gap-2 mt-4">
          <button class="btn btn-brand btn-lg"><i class="bi bi-check2-circle"></i> <?= $isEdit ? 'Guardar cambios' : 'Registrar socio' ?></button>
          <a href="<?= url($isEdit ? 'socios/ver/' . $id : 'socios') ?>" class="btn btn-outline-secondary">Cancelar</a>
        </div>
      </div>
    </div>
  </div>
</form>
<script>
window.addEventListener('DOMContentLoaded', function () {
  var cur = <?= json_encode(APP_CURRENCY) ?>;
  var plan = document.getElementById('planSel'), desc = document.getElementById('descIn'), monto = document.getElementById('montoIn'), box = document.getElementById('totalBox');
  function calc(setMonto) {
    if (!plan) return;
    var o = plan.options[plan.selectedIndex], p = parseFloat(o && o.dataset.precio || 0), d = Math.min(parseFloat(desc.value || 0), p), t = Math.max(0, p - d);
    box.textContent = window.fmtMoney(t);
    if (setMonto) monto.value = t.toFixed(2);
  }
  if (plan) { plan.addEventListener('change', function () { calc(true); }); desc.addEventListener('input', function () { calc(true); }); calc(false); }
  var inp = document.getElementById('photoIn');
  inp.addEventListener('change', function () {
    var f = inp.files[0]; if (!f) return;
    var prev = document.getElementById('photoPrev'); prev.src = URL.createObjectURL(f); prev.classList.remove('d-none');
    var ico = document.getElementById('photoIco'); if (ico) ico.classList.add('d-none');
  });
});
</script>
