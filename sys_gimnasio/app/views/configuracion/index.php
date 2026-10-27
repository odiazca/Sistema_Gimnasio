<?php $sel = fn($a, $b) => (string)$a === (string)$b ? 'selected' : ''; $chk = fn($a, $b) => (string)$a === (string)$b ? 'checked' : ''; ?>
<div class="page-head"><div><h1 class="page-title">Configuración</h1><p class="page-sub">Datos de la empresa, moneda, formatos y operación del gimnasio.</p></div>
  <a href="<?= url('sistema') ?>" class="btn btn-soft"><i class="bi bi-shield-check"></i> Respaldo y sistema</a></div>
<div class="row g-3 g-xl-4">
  <div class="col-lg-8">
    <form class="card-x" method="post" action="<?= url('configuracion/guardar') ?>" id="formCfg"><?= csrf_field() ?>
      <ul class="nav nav-pills cfg-tabs mb-4" role="tablist">
        <li class="nav-item"><button type="button" class="nav-link active" data-bs-toggle="pill" data-bs-target="#tEmpresa"><i class="bi bi-building"></i> Empresa</button></li>
        <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="pill" data-bs-target="#tMoneda"><i class="bi bi-currency-exchange"></i> Moneda y formato</button></li>
        <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="pill" data-bs-target="#tOper"><i class="bi bi-sliders"></i> Operación y apariencia</button></li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="tEmpresa"><div class="row g-3">
          <div class="col-md-7"><label class="form-lbl">Nombre comercial *</label><input name="gimnasio_nombre" class="form-control" required maxlength="80" value="<?= v($cfg, 'gimnasio_nombre') ?>"></div>
          <div class="col-md-5"><label class="form-lbl">RUC / NIT</label><input name="gimnasio_ruc" class="form-control" maxlength="15" inputmode="numeric" value="<?= v($cfg, 'gimnasio_ruc') ?>"></div>
          <div class="col-12"><label class="form-lbl">Razón social</label><input name="razon_social" class="form-control" maxlength="120" value="<?= v($cfg, 'razon_social') ?>"></div>
          <div class="col-md-8"><label class="form-lbl">Dirección</label><input name="gimnasio_direccion" class="form-control" maxlength="150" value="<?= v($cfg, 'gimnasio_direccion') ?>"></div>
          <div class="col-md-4"><label class="form-lbl">Ciudad</label><input name="gimnasio_ciudad" class="form-control" maxlength="60" value="<?= v($cfg, 'gimnasio_ciudad') ?>"></div>
          <div class="col-md-4"><label class="form-lbl">Teléfono</label><input name="gimnasio_telefono" class="form-control" maxlength="20" value="<?= v($cfg, 'gimnasio_telefono') ?>"></div>
          <div class="col-md-4"><label class="form-lbl">WhatsApp</label><input name="gimnasio_whatsapp" class="form-control" maxlength="20" placeholder="51999999999" value="<?= v($cfg, 'gimnasio_whatsapp') ?>"></div>
          <div class="col-md-4"><label class="form-lbl">Correo</label><input type="email" name="gimnasio_email" class="form-control" maxlength="120" value="<?= v($cfg, 'gimnasio_email') ?>"></div>
          <div class="col-md-4"><label class="form-lbl">Página web</label><input name="gimnasio_web" class="form-control" maxlength="120" value="<?= v($cfg, 'gimnasio_web') ?>"></div>
          <div class="col-md-4"><label class="form-lbl">Instagram</label><div class="input-group"><span class="input-group-text">@</span><input name="gimnasio_instagram" class="form-control" maxlength="60" value="<?= v($cfg, 'gimnasio_instagram') ?>"></div></div>
          <div class="col-md-4"><label class="form-lbl">Facebook</label><input name="gimnasio_facebook" class="form-control" maxlength="80" value="<?= v($cfg, 'gimnasio_facebook') ?>"></div>
          <div class="col-12"><label class="form-lbl">Mensaje al pie de recibos y tickets</label><input name="pie_recibo" class="form-control" maxlength="160" placeholder="¡Gracias por entrenar con nosotros!" value="<?= v($cfg, 'pie_recibo') ?>"></div>
        </div></div>

        <div class="tab-pane fade" id="tMoneda">
          <div class="mb-3"><label class="form-lbl">Moneda predefinida</label>
            <select class="form-select" id="monPreset"><option value="">Personalizada…</option>
              <?php foreach ($monedas as $c => $m): ?><option value="<?= e($c) ?>" data-sym="<?= e($m[1]) ?>" data-dec="<?= $m[2] ?>" data-ds="<?= e($m[3]) ?>" data-ts="<?= e($m[4]) ?>" <?= $sel($fmt['code'], $c) ?>><?= e($c . ' — ' . $m[0] . ' (' . $m[1] . ')') ?></option><?php endforeach; ?></select></div>
          <div class="row g-3">
            <div class="col-md-3"><label class="form-lbl">Símbolo *</label><input name="moneda" id="monSym" class="form-control" maxlength="6" required value="<?= e($fmt['sym']) ?>"></div>
            <div class="col-md-3"><label class="form-lbl">Código ISO</label><input name="moneda_codigo" id="monCode" class="form-control text-uppercase" maxlength="3" value="<?= e($fmt['code']) ?>"></div>
            <div class="col-md-3"><label class="form-lbl">Decimales</label><select name="moneda_decimales" id="monDec" class="form-select"><?php for ($i = 0; $i <= 4; $i++): ?><option <?= $sel($fmt['dec'], $i) ?>><?= $i ?></option><?php endfor; ?></select></div>
            <div class="col-md-3"><label class="form-lbl">Posición</label><select name="moneda_posicion" id="monPos" class="form-select"><option value="antes" <?= $sel($fmt['pos'], 'antes') ?>>Antes (S/ 10)</option><option value="despues" <?= $sel($fmt['pos'], 'despues') ?>>Después (10 S/)</option></select></div>
            <div class="col-md-4"><label class="form-lbl">Separador decimal</label><select name="sep_decimal" id="monDs" class="form-select"><option value="." <?= $sel($fmt['ds'], '.') ?>>Punto (1234.50)</option><option value="," <?= $sel($fmt['ds'], ',') ?>>Coma (1234,50)</option></select></div>
            <?php $tsk = ['coma' => ',', 'punto' => '.', 'espacio' => ' ', 'ninguno' => '']; $tsCur = array_search($fmt['ts'], $tsk, true) ?: 'ninguno'; ?>
            <div class="col-md-4"><label class="form-lbl">Separador de miles</label><select name="sep_miles" id="monTs" class="form-select"><option value="coma" <?= $sel($tsCur, 'coma') ?>>Coma (1,234)</option><option value="punto" <?= $sel($tsCur, 'punto') ?>>Punto (1.234)</option><option value="espacio" <?= $sel($tsCur, 'espacio') ?>>Espacio (1 234)</option><option value="ninguno" <?= $sel($tsCur, 'ninguno') ?>>Ninguno (1234)</option></select></div>
            <div class="col-md-4 d-flex align-items-end"><div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="moneda_espacio" id="monSp" value="1" <?= !empty($fmt['sp']) ? 'checked' : '' ?>><label class="form-check-label" for="monSp">Espacio entre símbolo y monto</label></div></div>
          </div>
          <div class="cfg-preview mt-4"><div class="small text-muted">Vista previa</div><div class="cfg-prev-big" id="prevBig">—</div><div class="small text-muted" id="prevSmall"></div></div>
          <hr class="my-4">
          <div class="row g-3">
            <div class="col-md-7"><label class="form-lbl">Zona horaria</label><select name="zona_horaria" class="form-select"><?php foreach ($zonas as $z => $n): ?><option value="<?= e($z) ?>" <?= $sel(cfg('zona_horaria', 'America/Lima'), $z) ?>><?= e($n) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-5"><label class="form-lbl">Formato de fecha</label><select name="formato_fecha" class="form-select"><?php foreach (['d/m/Y' => '31/12/2026', 'd-m-Y' => '31-12-2026', 'Y-m-d' => '2026-12-31', 'm/d/Y' => '12/31/2026'] as $k => $ej): ?><option value="<?= $k ?>" <?= $sel(cfg('formato_fecha', 'd/m/Y'), $k) ?>><?= $ej ?></option><?php endforeach; ?></select></div>
          </div>
        </div>

        <div class="tab-pane fade" id="tOper"><div class="row g-3">
          <div class="col-md-6"><label class="form-lbl">Aforo máximo (personas)</label><input type="number" min="1" max="5000" name="aforo_maximo" class="form-control" required value="<?= v($cfg, 'aforo_maximo', '120') ?>"></div>
          <div class="col-md-6"><label class="form-lbl">Días de aviso de vencimiento</label><input type="number" min="1" max="60" name="dias_aviso_vencimiento" class="form-control" required value="<?= v($cfg, 'dias_aviso_vencimiento', '7') ?>"></div>
          <div class="col-12"><label class="form-lbl">Tema por defecto</label>
            <div class="d-flex gap-2 flex-wrap"><?php foreach (['claro' => ['sun', 'Claro'], 'oscuro' => ['moon-stars', 'Oscuro'], 'sistema' => ['circle-half', 'Según el dispositivo']] as $k => $t): ?>
              <label class="cfg-opt"><input type="radio" name="tema_defecto" value="<?= $k ?>" <?= $chk(cfg('tema_defecto', 'claro'), $k) ?>><span><i class="bi bi-<?= $t[0] ?>"></i> <?= $t[1] ?></span></label><?php endforeach; ?></div>
            <div class="form-text">Se aplica a quienes aún no eligieron un tema con el botón del encabezado.</div></div>
        </div></div>
      </div>
      <button class="btn btn-brand mt-4"><i class="bi bi-check2"></i> Guardar configuración</button>
    </form>
  </div>
  <div class="col-lg-4">
    <div class="card-x mb-3"><h6 class="sec-title"><i class="bi bi-image"></i> Logo de la empresa</h6>
      <div class="cfg-logo"><?php if (!empty($cfg['logo'])): ?><img src="<?= e(foto_url($cfg['logo'])) ?>" alt="Logo"><?php else: ?><i class="bi bi-image"></i><span>Sin logo</span><?php endif; ?></div>
      <form method="post" action="<?= url('configuracion/logo') ?>" enctype="multipart/form-data"><?= csrf_field() ?>
        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="form-control form-control-sm mb-2" required>
        <div class="form-text mb-2">JPG, PNG o WEBP, máx. 1 MB. Se muestra en el menú lateral y en el acceso.</div>
        <button class="btn btn-soft btn-sm w-100"><i class="bi bi-upload"></i> Subir logo</button></form>
      <?php if (!empty($cfg['logo'])): ?><form method="post" action="<?= url('configuracion/logo') ?>" class="mt-2" data-confirm="¿Quitar el logo?"><?= csrf_field() ?><input type="hidden" name="quitar" value="1"><button class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-trash"></i> Quitar logo</button></form><?php endif; ?></div>
    <div class="card-x"><h6 class="sec-title"><i class="bi bi-database-lock"></i> Respaldo y reseteo</h6>
      <p class="small text-muted mb-3">Copias de seguridad, restauración y reinicio del sistema para una empresa nueva están en su propio módulo.</p>
      <a href="<?= url('sistema') ?>" class="btn btn-brand w-100"><i class="bi bi-shield-check"></i> Ir a Respaldo y sistema</a></div>
  </div>
</div>
<style>
.cfg-tabs .nav-link{color:var(--text-2,inherit);border-radius:10px;font-weight:600;font-size:.88rem}
.cfg-tabs .nav-link.active{background:var(--brand,#ff6a00);color:#fff}
.cfg-preview{border:1px dashed var(--border,#ddd);border-radius:14px;padding:16px 20px;background:var(--surface-2,rgba(255,106,0,.05))}
.cfg-prev-big{font-size:1.8rem;font-weight:800;color:var(--brand,#ff6a00)}
.cfg-logo{height:130px;border:1px dashed var(--border,#ddd);border-radius:14px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;margin-bottom:14px;color:var(--muted,#999);font-size:.85rem}
.cfg-logo i{font-size:2rem}.cfg-logo img{max-width:90%;max-height:110px;object-fit:contain}
.cfg-opt input{display:none}.cfg-opt span{display:inline-flex;gap:8px;align-items:center;padding:10px 16px;border:1.5px solid var(--border,#ddd);border-radius:12px;cursor:pointer;font-weight:600;font-size:.88rem}
.cfg-opt input:checked+span{border-color:var(--brand,#ff6a00);background:rgba(255,106,0,.1);color:var(--brand,#ff6a00)}
</style>
<script>
document.addEventListener('DOMContentLoaded',function(){
  var $=function(i){return document.getElementById(i)};
  var tsMap={coma:',',punto:'.',espacio:' ',ninguno:''};
  function fmt(n,dec,ds,ts){var s=Math.abs(n).toFixed(dec),p=s.split('.');p[0]=p[0].replace(/\B(?=(\d{3})+(?!\d))/g,ts);return (n<0?'-':'')+p[0]+(dec?ds+p[1]:'')}
  function money(n){var dec=+$('monDec').value,ds=$('monDs').value,ts=tsMap[$('monTs').value],sym=$('monSym').value,sp=$('monSp').checked?' ':'';
    var x=fmt(n,dec,ds,ts);return $('monPos').value==='antes'?sym+sp+x:x+sp+sym}
  function prev(){$('prevBig').textContent=money(1234567.891);$('prevSmall').textContent='Otros ejemplos: '+money(85)+' · '+money(1250.5)+' · '+money(0.99);
    var ds=$('monDs').value,ts=$('monTs').value;var bad=(ts==='punto'&&ds==='.')||(ts==='coma'&&ds===',');$('prevSmall').className='small '+(bad?'text-danger':'text-muted');if(bad)$('prevSmall').textContent='El separador decimal y el de miles no pueden ser iguales.'}
  ['monSym','monDec','monPos','monDs','monTs','monSp'].forEach(function(i){$(i).addEventListener('input',prev);$(i).addEventListener('change',prev)});
  $('monPreset').addEventListener('change',function(){var o=this.selectedOptions[0];if(!o.value)return;
    $('monSym').value=o.dataset.sym;$('monCode').value=o.value;$('monDec').value=o.dataset.dec;$('monDs').value=o.dataset.ds;$('monTs').value=o.dataset.ts;prev()});
  prev();
});
</script>
