<?php $sug = ['¿Cuántos socios activos tengo?', 'Socios cuya membresía vence en los próximos 7 días', 'Ingresos por plan este mes', 'Horas pico de asistencia por día de la semana', 'Los 10 productos más vendidos este mes', 'Socios con deuda pendiente', 'Ingresos vs gastos de los últimos 6 meses', 'Socios que no vienen hace más de 15 días']; ?>
<div class="ia-hero">
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
    <div><span class="ia-badge"><i class="bi bi-stars"></i> Asistente IA</span><h3 class="mt-2 mb-1">¿Qué quieres saber de tu gimnasio?</h3><p class="mb-0 opacity-75">Pregunta en lenguaje natural y obtén tabla, gráfico y resumen al instante.</p></div>
    <div class="d-flex gap-2 flex-wrap"><span class="ia-pill" id="iaEstadoPill"></span><button class="ia-hbtn" id="iaVer"><i class="bi bi-eye"></i> Qué datos ve la IA</button><button class="ia-hbtn" data-cfg><i class="bi bi-gear"></i> Configurar IA</button></div>
  </div>
  <div class="ia-ask"><textarea id="iaQ" rows="2" maxlength="500" placeholder="Ej: ¿Cuánto cobré por membresías esta semana?"></textarea>
    <div class="d-flex flex-column gap-2"><button class="btn btn-brand" id="iaBtn"><i class="bi bi-stars"></i> Preguntar</button><button class="btn btn-outline-light btn-sm" id="iaNueva" title="Nueva pregunta"><i class="bi bi-plus-lg"></i> Nueva</button></div></div>
  <div class="ia-chips"><?php foreach ($sug as $s): ?><button data-chip="<?= e($s) ?>"><?= e($s) ?></button><?php endforeach; ?></div>
</div>

<div class="ia-card ia-setup d-none" id="iaSetup">
  <div class="d-flex flex-wrap gap-3 align-items-center">
    <div class="ia-setup-ico"><i class="bi bi-lightning-charge-fill"></i></div>
    <div class="flex-grow-1" style="min-width:220px"><h6 class="mb-1">Actívalo en 2 minutos (gratis)</h6><p class="small text-muted mb-0">Crea una clave gratuita en <a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener" class="link-brand">aistudio.google.com/apikey</a>, pégala aquí y listo. Se guarda cifrada y solo tú la usas.</p></div>
    <div class="input-group" style="max-width:420px"><input type="password" id="stClave" class="form-control" placeholder="Pega tu API key de Gemini" autocomplete="off"><button class="btn btn-brand" id="stGuardar">Guardar y probar</button></div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-lg-8"><div id="iaOut"></div></div>
  <div class="col-lg-4">
    <div class="ia-card mb-3"><h6 class="ia-sec"><i class="bi bi-star-fill text-warning"></i> Favoritas</h6><div id="iaFavs"></div></div>
    <div class="ia-card"><h6 class="ia-sec"><i class="bi bi-clock-history"></i> Recientes</h6><div id="iaRec"></div></div>
  </div>
</div>

<div class="modal fade" id="iaCfgModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content modal-x">
  <div class="modal-header"><h6 class="modal-title fw-bold"><i class="bi bi-gear-fill text-brand"></i> Configurar IA</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <label class="form-label small fw-semibold">Proveedor</label>
    <select id="cfProv" class="form-select mb-1"><?php foreach (Ia::PRESETS as $k => $p): ?><option value="<?= e($k) ?>"><?= e($p['nombre']) ?></option><?php endforeach; ?></select>
    <div class="form-text mb-3" id="cfAyuda"></div>
    <label class="form-label small fw-semibold">URL base de la API</label><input id="cfUrl" class="form-control mb-3">
    <label class="form-label small fw-semibold">Modelo</label><input id="cfModelo" class="form-control mb-3">
    <label class="form-label small fw-semibold">Clave (API key)</label>
    <div class="input-group mb-3"><input type="password" id="cfClave" class="form-control" autocomplete="off"><button class="btn btn-outline-danger d-none" id="cfBorrar" type="button" title="Borrar clave"><i class="bi bi-trash"></i></button></div>
    <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" id="cfResumen"><label class="form-check-label small" for="cfResumen">Generar un resumen en texto de cada resultado (usa una llamada extra)</label></div>
    <div class="alert alert-light border small mb-0"><i class="bi bi-shield-lock-fill text-brand"></i> <b>Privacidad:</b> la IA solo ve vistas de lectura y la pregunta; envía al proveedor la estructura de esas vistas y, para el resumen y el chat, las primeras filas del resultado. Con Ollama todo queda en tu equipo. La clave se guarda cifrada.</div>
  </div>
  <div class="modal-footer"><button class="btn btn-outline-secondary" id="cfProbar">Guardar y probar</button><button class="btn btn-brand" id="cfGuardar">Guardar</button></div>
</div></div></div>

<div class="modal fade" id="iaEsqModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content modal-x">
  <div class="modal-header"><h6 class="modal-title fw-bold">Qué datos ve la IA</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><p class="small text-muted">Solo estas vistas de lectura. Nunca ve contraseñas, usuarios ni las tablas reales, y no puede modificar nada.</p><pre class="ia-sql" id="iaEsq" style="white-space:pre-wrap"></pre></div>
</div></div></div>

<script>window.IA = window.IA || {};</script>
<script src="<?= ASSETS_URL ?>/vendor/chart.umd.min.js"></script>
<script src="<?= ASSETS_URL ?>/js/asistente.js?v=<?= APP_VERSION ?>"></script>
