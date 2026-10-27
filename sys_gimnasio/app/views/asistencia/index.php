<div class="page-head">
  <div><h1 class="page-title">Control de asistencia</h1><p class="page-sub">Registra el ingreso y la salida de los socios en recepción.</p></div>
  <div class="d-flex gap-2 flex-wrap">
    <button class="btn btn-outline-secondary" id="soundBtn" type="button" title="Sonido"><i class="bi bi-volume-up-fill"></i></button>
    <a href="<?= url('asistencia/historial') ?>" class="btn btn-outline-secondary"><i class="bi bi-clock-history"></i> Historial</a>
  </div>
</div>

<div class="row g-4">
  <div class="col-xl-7">
    <div class="card-x scan-card">
      <div class="scan-head"><span class="scan-ico"><i class="bi bi-qr-code-scan"></i></span>
        <div><h5 class="m-0 fw-bold">Registrar ingreso / salida</h5><small class="text-muted">Escanea el QR del carnet, digita el DNI o código, o busca por nombre.</small></div></div>
      <div class="ac mt-3" id="acScan" data-url="<?= url('socios/buscar') ?>" data-onpick="pickSocio">
        <input type="hidden" id="scanHid">
        <div class="input-ico scan-input"><i class="bi bi-upc-scan"></i>
          <input type="text" id="scan" class="form-control" autocomplete="off" autofocus placeholder="DNI, código (SOC-0001) o nombre…" inputmode="text">
          <button type="button" class="scan-go" id="scanGo" aria-label="Registrar"><i class="bi bi-arrow-return-left"></i></button></div>
        <div class="ac-list d-none"></div>
      </div>
      <div class="scan-hint"><kbd>Enter</kbd> para registrar · si el socio ya está dentro, se registra su <b>salida</b>.</div>

      <div id="result" class="result idle">
        <div class="result-idle"><i class="bi bi-person-check"></i><span>Esperando escaneo…</span></div>
      </div>
    </div>

    <div class="row g-3 mt-2 mb-1">
      <div class="col-4"><div class="mini"><i class="bi bi-box-arrow-in-right" style="--c:#10b981"></i><div><b id="kIng">0</b><span>Ingresos hoy</span></div></div></div>
      <div class="col-4"><div class="mini"><i class="bi bi-people" style="--c:#6366f1"></i><div><b id="kUni">0</b><span>Socios distintos</span></div></div></div>
      <div class="col-4"><div class="mini"><i class="bi bi-box-arrow-right" style="--c:#f59e0b"></i><div><b id="kSal">0</b><span>Salidas hoy</span></div></div></div>
    </div>

    <div class="card-x mt-4">
      <div class="card-h"><div><h6>Últimos movimientos</h6><small>Actividad de hoy en recepción</small></div></div>
      <ul class="feed" id="recientes"><li class="text-muted">Cargando…</li></ul>
    </div>
  </div>

  <div class="col-xl-5">
    <div class="card-x h-100">
      <div class="card-h">
        <div><h6>En el gimnasio ahora</h6><small>Se actualiza automáticamente</small></div>
        <form method="post" action="<?= url('asistencia/cerrar') ?>" data-confirm="¿Cerrar la jornada? Se marcará la salida de todos los socios que siguen dentro.">
          <?= csrf_field() ?><input type="hidden" name="redir" value="1"><button class="btn btn-soft btn-sm"><i class="bi bi-door-closed"></i> Cerrar jornada</button></form>
      </div>
      <div class="aforo">
        <div class="occ-ring light" id="ring" style="--p:0"><div><b id="dentroN">0</b><small>dentro</small></div></div>
        <div class="aforo-txt"><b id="aforoPct">0%</b> del aforo<small class="d-block text-muted">Máximo permitido: <?= (int)$aforo ?> personas</small>
          <div class="progress-x mt-2"><span id="aforoBar" style="width:0%"></span></div></div>
      </div>
      <ul class="feed dentro-list" id="dentroList"><li class="text-muted justify-content-center py-4">Nadie dentro en este momento</li></ul>
    </div>
  </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', function () {
  var CSRF = <?= json_encode(Auth::csrf()) ?>, URL_ = { reg: <?= json_encode(url('asistencia/registrar')) ?>, est: <?= json_encode(url('asistencia/estado')) ?>, sal: <?= json_encode(url('asistencia/salida')) ?>, base: <?= json_encode(url('')) ?> };
  var $ = function (i) { return document.getElementById(i); };
  var scan = $('scan'), result = $('result'), busy = false, hideT = null, MAX = <?= (int)$aforo ?>;
  var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); };

  // ---- sonido ----
  var soundOn = true; try { soundOn = localStorage.getItem('fc-sound') !== '0'; } catch (e) {}
  var ac = null;
  function paintSound() { $('soundBtn').innerHTML = '<i class="bi bi-volume-' + (soundOn ? 'up' : 'mute') + '-fill"></i>'; }
  paintSound();
  $('soundBtn').addEventListener('click', function () { soundOn = !soundOn; try { localStorage.setItem('fc-sound', soundOn ? '1' : '0'); } catch (e) {} paintSound(); scan.focus(); });
  function beep(kind) {
    if (!soundOn) return;
    try {
      ac = ac || new (window.AudioContext || window.webkitAudioContext)();
      var seq = kind === 'ok' ? [[880, .09], [1320, .14]] : kind === 'out' ? [[660, .09], [440, .14]] : [[220, .2], [180, .25]];
      var t = ac.currentTime;
      seq.forEach(function (n) {
        var o = ac.createOscillator(), g = ac.createGain(); o.type = kind === 'bad' ? 'sawtooth' : 'sine'; o.frequency.value = n[0];
        g.gain.setValueAtTime(.0001, t); g.gain.exponentialRampToValueAtTime(.18, t + .02); g.gain.exponentialRampToValueAtTime(.0001, t + n[1]);
        o.connect(g); g.connect(ac.destination); o.start(t); o.stop(t + n[1] + .02); t += n[1];
      });
    } catch (e) {}
  }

  // ---- resultado ----
  var THEMES = { ingreso: ['ok', 'bi-check-circle-fill'], aviso: ['warn', 'bi-exclamation-triangle-fill'], salida: ['out', 'bi-box-arrow-right'], duplicado: ['dup', 'bi-arrow-repeat'] };
  function showResult(r) {
    var th = THEMES[r.tipo] || ['bad', r.tipo === 'noexiste' ? 'bi-question-circle-fill' : 'bi-x-octagon-fill'];
    var s = r.socio, av = s ? (s.foto ? '<img src="' + esc(s.foto) + '" class="res-photo" alt="">' : '<span class="res-photo ph">' + esc(s.ini) + '</span>') : '<span class="res-photo ph"><i class="bi bi-question-lg"></i></span>';
    var h = '<div class="res-body ' + th[0] + '">' + av + '<div class="res-main"><div class="res-title"><i class="bi ' + th[1] + '"></i> ' + esc(r.titulo) + '</div>';
    if (s) h += '<div class="res-name">' + esc(s.nombre) + ' <small>· ' + esc(s.codigo) + '</small></div>';
    h += '<div class="res-msg">' + esc(r.mensaje) + '</div>';
    if (r.plan) h += '<div class="res-chips"><span class="res-chip"><i class="bi bi-award"></i> ' + esc(r.plan) + '</span>' + (r.vence ? '<span class="res-chip"><i class="bi bi-calendar-event"></i> Vence ' + esc(r.vence) + '</span>' : '') + '</div>';
    if (r.avisos && r.avisos.length) h += '<ul class="res-avisos">' + r.avisos.map(function (a) { return '<li><i class="bi bi-exclamation-circle"></i> ' + esc(a) + '</li>'; }).join('') + '</ul>';
    if (r.accion) h += '<a class="btn btn-light btn-sm mt-2 res-act" href="' + URL_.base + '/' + esc(r.accion) + '"><i class="bi bi-arrow-right-circle"></i> ' + (r.tipo === 'aviso' ? 'Resolver ahora' : r.tipo === 'vencida' || r.tipo === 'sinplan' ? 'Renovar / inscribir' : 'Ver ficha') + '</a>';
    h += '</div></div>';
    result.className = 'result show'; result.innerHTML = h;
    clearTimeout(hideT); hideT = setTimeout(function () { result.className = 'result idle'; result.innerHTML = '<div class="result-idle"><i class="bi bi-person-check"></i><span>Esperando escaneo…</span></div>'; }, 14000);
    beep(r.tipo === 'ingreso' || r.tipo === 'aviso' ? 'ok' : r.tipo === 'salida' ? 'out' : r.tipo === 'duplicado' ? 'out' : 'bad');
  }

  function post(url, data) {
    var fd = new FormData(); fd.append('csrf', CSRF); Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
    return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } }).then(function (r) { return r.json(); });
  }

  function registrar(codigo, metodo) {
    if (busy) return; codigo = (codigo || '').trim(); if (!codigo) return; busy = true;
    $('scanGo').disabled = true;
    post(URL_.reg, { codigo: codigo, metodo: metodo }).then(showResult)
      .catch(function () { showResult({ ok: false, tipo: 'error', titulo: 'Error de conexión', mensaje: 'No se pudo registrar. Intenta nuevamente.' }); })
      .then(function () { busy = false; $('scanGo').disabled = false; scan.value = ''; scan.dispatchEvent(new Event('input')); scan.focus(); refresh(); });
  }
  window.pickSocio = function (r) { registrar(r.codigo, 'manual'); };
  scan.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); $('acScan').querySelector('.ac-list').classList.add('d-none'); var v = scan.value.trim(); registrar(v, /^SOC-/i.test(v) ? 'qr' : 'dni'); }
  });
  $('scanGo').addEventListener('click', function () { var v = scan.value.trim(); registrar(v, /^SOC-/i.test(v) ? 'qr' : 'dni'); });
  document.addEventListener('click', function (e) { if (!/INPUT|TEXTAREA|SELECT|BUTTON|A/.test(e.target.tagName) && !e.target.closest('.modal')) scan.focus(); });

  // ---- panel en vivo ----
  function refresh() {
    fetch(URL_.est, { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (d) {
      $('kIng').textContent = d.hoy.ingresos; $('kUni').textContent = d.hoy.unicos; $('kSal').textContent = d.hoy.salidas;
      var pct = d.max ? Math.min(100, Math.round(d.dentro_n / d.max * 100)) : 0;
      $('dentroN').textContent = d.dentro_n; $('aforoPct').textContent = pct + '%'; $('aforoBar').style.width = pct + '%'; $('ring').style.setProperty('--p', pct);
      $('aforoBar').style.background = pct >= 90 ? 'var(--bad)' : pct >= 70 ? 'var(--warn)' : 'var(--ok)';
      $('dentroList').innerHTML = d.dentro.length ? d.dentro.map(function (x) {
        var av = x.foto ? '<img src="' + esc(x.foto) + '" class="avatar-img" style="width:34px;height:34px" alt="">' : '<span class="avatar sm">' + esc(x.ini) + '</span>';
        var t = x.min >= 60 ? Math.floor(x.min / 60) + ' h ' + (x.min % 60) + ' min' : x.min + ' min';
        return '<li>' + av + '<div class="flex-fill lh-sm"><b>' + esc(x.nombre) + '</b><br><small class="text-muted">' + esc(x.codigo) + ' · desde ' + x.hora + ' · ' + t + '</small></div><button class="btn btn-soft btn-sm" data-sal="' + x.id + '"><i class="bi bi-box-arrow-right"></i> Salida</button></li>';
      }).join('') : '<li class="text-muted justify-content-center py-4">Nadie dentro en este momento</li>';
      $('recientes').innerHTML = d.recientes.length ? d.recientes.map(function (x) {
        var ing = x.tipo === 'ingreso';
        return '<li><span class="pay-ico" style="--c:' + (ing ? '#10b981' : '#f59e0b') + '"><i class="bi bi-box-arrow-' + (ing ? 'in-right' : 'right') + '"></i></span><div class="flex-fill lh-sm"><b>' + esc(x.nombre) + '</b><br><small class="text-muted">' + esc(x.codigo) + '</small></div><div class="text-end lh-sm"><b>' + x.hora + '</b><br><small class="' + (ing ? 'text-success' : 'text-muted') + '">' + (ing ? 'ingreso' : 'salida') + '</small></div></li>';
      }).join('') : '<li class="text-muted justify-content-center py-3">Sin movimientos hoy</li>';
    }).catch(function () {});
  }
  $('dentroList').addEventListener('click', function (e) {
    var b = e.target.closest('[data-sal]'); if (!b) return; b.disabled = true;
    post(URL_.sal + '/' + b.dataset.sal).then(refresh);
  });
  refresh(); setInterval(refresh, 15000); scan.focus();
});
</script>
