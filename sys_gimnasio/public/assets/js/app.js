(function () {
  var root = document.documentElement;
  var body = document.body;
  var toggle = document.getElementById('sbToggle');
  var backdrop = document.getElementById('sbBackdrop');

  if (toggle) toggle.addEventListener('click', function () {
    if (window.innerWidth >= 992) {
      var c = root.classList.toggle('sb-collapsed');
      try { localStorage.setItem('fc-collapsed', c ? '1' : '0'); } catch (e) {}
      setTimeout(function () { window.dispatchEvent(new Event('resize')); }, 300);
    } else {
      body.classList.toggle('sb-open');
    }
  });
  if (backdrop) backdrop.addEventListener('click', function () { body.classList.remove('sb-open'); });

  // Tema claro / oscuro
  var tbtn = document.getElementById('themeToggle');
  function paintIcon() {
    if (!tbtn) return;
    var dark = root.getAttribute('data-theme') === 'dark';
    tbtn.innerHTML = '<i class="bi ' + (dark ? 'bi-sun-fill' : 'bi-moon-stars-fill') + '"></i>';
  }
  paintIcon();
  if (tbtn) tbtn.addEventListener('click', function () {
    var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    root.setAttribute('data-bs-theme', next);
    try { localStorage.setItem('fc-theme', next); } catch (e) {}
    paintIcon();
    document.dispatchEvent(new CustomEvent('themechange'));
  });

  // Atajo "/" para buscar
  document.addEventListener('keydown', function (e) {
    var s = document.querySelector('.search-box input');
    if (e.key === '/' && s && !/INPUT|TEXTAREA/.test(document.activeElement.tagName)) { e.preventDefault(); s.focus(); }
  });

  // Confirmación con modal para formularios [data-confirm]
  var pendingForm = null, cm = document.getElementById('confirmModal');
  if (cm && window.bootstrap) {
    var modal = new bootstrap.Modal(cm);
    document.addEventListener('submit', function (e) {
      var f = e.target;
      if (f.matches && f.matches('form[data-confirm]') && !f.dataset.ok) {
        e.preventDefault(); pendingForm = f;
        document.getElementById('confirmText').textContent = f.getAttribute('data-confirm');
        modal.show();
      }
    });
    document.getElementById('confirmOk').addEventListener('click', function () {
      if (pendingForm) { pendingForm.dataset.ok = '1'; modal.hide(); pendingForm.submit(); }
    });
  }

  // Autocompletado de socios: <div class="ac" data-url data-onpick>
  document.querySelectorAll('.ac').forEach(function (ac) {
    var hid = ac.querySelector('input[type=hidden]'), inp = ac.querySelector('input[type=text]'), list = ac.querySelector('.ac-list'), t = null, seq = 0;
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    function close() { list.classList.add('d-none'); }
    function render(rows) {
      if (!rows.length) { list.innerHTML = '<div class="ac-empty">Sin resultados</div>'; list.classList.remove('d-none'); return; }
      list.innerHTML = rows.map(function (r, i) {
        var v = r.vence ? '<small class="text-muted">' + esc(r.plan) + ' · vence ' + r.vence.split('-').reverse().join('/') + '</small>' : '<small class="text-muted">Sin membresía</small>';
        return '<button type="button" class="ac-item" data-i="' + i + '"><span class="avatar sm">' + esc(r.ini) + '</span><span class="lh-sm"><b>' + esc(r.nombre) + '</b><br><small class="text-muted">' + esc(r.codigo) + (r.dni ? ' · ' + esc(r.dni) : '') + '</small></span><span class="ms-auto text-end">' + v + '</span></button>';
      }).join('');
      list.classList.remove('d-none');
      list.querySelectorAll('.ac-item').forEach(function (b) {
        b.addEventListener('click', function () {
          var r = rows[+b.dataset.i]; hid.value = r.id; inp.value = r.nombre + ' · ' + r.codigo; close();
          var cb = ac.dataset.onpick; if (cb && typeof window[cb] === 'function') window[cb](r);
        });
      });
    }
    inp.addEventListener('input', function () {
      hid.value = ''; clearTimeout(t); seq++;
      var q = inp.value.trim(); if (q.length < 2) { close(); return; }
      t = setTimeout(function () {
        var my = seq;
        fetch(ac.dataset.url + '?q=' + encodeURIComponent(q), { credentials: 'same-origin' }).then(function (r) { return r.json(); })
          .then(function (rows) { if (my === seq) render(rows); }).catch(close);
      }, 220);
    });
    document.addEventListener('click', function (e) { if (!ac.contains(e.target)) close(); });
  });
})();
