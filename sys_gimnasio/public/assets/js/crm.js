/* CRM · FitCore Gym */
document.addEventListener('DOMContentLoaded', function () {
  var C = window.CRM || {}, $ = function (s, r) { return (r || document).querySelector(s); }, $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var tok = function () { var i = $('#csrfBox input'); return i ? i.value : ''; };
  function post(ruta, datos) {
    var fd = new FormData(); fd.append('csrf', tok()); Object.keys(datos || {}).forEach(function (k) { fd.append(k, datos[k]); });
    return fetch((C.base || '') + '/' + ruta, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-CSRF-Token': tok(), 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); }).catch(function () { return { ok: false, error: 'No se pudo comunicar con el servidor.' }; });
  }
  function toast(msg, err) {
    var w = $('#crmToasts'); if (!w) { w = document.createElement('div'); w.id = 'crmToasts'; w.className = 'crm-toasts'; document.body.appendChild(w); }
    var t = document.createElement('div'); t.className = 'crm-toast' + (err ? ' err' : ''); t.textContent = msg; w.appendChild(t); setTimeout(function () { t.remove(); }, 3500);
  }
  var css = getComputedStyle(document.documentElement), tx = css.getPropertyValue('--muted').trim() || '#64748b', ln = css.getPropertyValue('--line').trim() || '#e8ecf3';

  /* ---- Gráficos del panel ---- */
  if (window.Chart && C.tend) {
    var pal = ['#ff5a1f', '#3b82f6', '#10b981', '#8b5cf6', '#f59e0b', '#06b6d4', '#ef4444', '#94a3b8', '#ffb347'];
    var f = $('#chFuentes'); if (f && C.fuentes && C.fuentes.length) new Chart(f, { type: 'doughnut', data: { labels: C.fuentes.map(function (x) { return x.l; }), datasets: [{ data: C.fuentes.map(function (x) { return x.n; }), backgroundColor: pal, borderWidth: 0 }] },
      options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'right', labels: { color: tx, boxWidth: 12, usePointStyle: true } } } } });
    var meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    var t = $('#chTend'); if (t) new Chart(t, { type: 'bar', data: { labels: C.tend.map(function (x) { return meses[parseInt(x.mes.slice(5), 10) - 1]; }), datasets: [
      { label: 'Prospectos', data: C.tend.map(function (x) { return x.leads; }), backgroundColor: '#3b82f6', borderRadius: 8 },
      { label: 'Ganados', data: C.tend.map(function (x) { return x.ganados; }), backgroundColor: '#10b981', borderRadius: 8 }] },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { color: tx, usePointStyle: true } } }, scales: { x: { grid: { display: false }, ticks: { color: tx } }, y: { beginAtZero: true, ticks: { precision: 0, color: tx }, grid: { color: ln } } } } });
  }

  /* ---- Tareas: marcar hecha ---- */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-done]'); if (!b) return;
    post('crm/completar', { id: b.dataset.done }).then(function (d) {
      if (!d.ok) return toast('No se pudo completar la tarea.', true);
      var row = b.closest('.task-row'); if (row) { row.classList.add('done'); setTimeout(function () { row.remove(); }, 350); } else location.reload();
      toast('Tarea completada ✓');
    });
  });

  /* ---- Embudo: kanban ---- */
  var kb = $('#kanban'), lost = $('#lostModal'), pend = null;
  function money(n) { return window.fmtMoney ? window.fmtMoney(n) : (C.moneda || 'S/') + ' ' + Number(n).toFixed(2); }
  function recount() {
    $$('.kan-col', kb).forEach(function (col) {
      var cards = $$('.kan-card', col), sum = cards.reduce(function (a, c) { return a + (parseFloat(c.dataset.valor) || 0); }, 0);
      $('[data-n]', col).textContent = cards.length; $('[data-sum]', col).innerHTML = sum > 0 ? money(sum) : '&nbsp;';
      var body = $('[data-drop]', col), em = $('.kan-empty', body);
      if (!cards.length && !em) { var d = document.createElement('div'); d.className = 'kan-empty'; d.textContent = 'Arrastra aquí'; body.appendChild(d); } else if (cards.length && em) em.remove();
    });
  }
  function mover(id, etapa, motivo, onOk) {
    post('crm/mover', { id: id, etapa: etapa, motivo: motivo || '' }).then(function (d) {
      if (!d.ok) return toast(d.error || 'No se pudo mover.', true);
      if (d.convertir) { location.href = d.convertir; return; }
      onOk && onOk(d);
    });
  }
  function pedirMotivo(nombre, cb) {
    pend = cb; $('#lostName').textContent = nombre || ''; bootstrap.Modal.getOrCreateInstance(lost).show();
  }
  var lostOk = $('#lostOk');
  if (lostOk) lostOk.addEventListener('click', function () { var m = $('#lostMotivo').value, cb = pend; pend = null; bootstrap.Modal.getOrCreateInstance(lost).hide(); cb && cb(m); });

  if (kb) {
    var drag = null;
    kb.addEventListener('dragstart', function (e) { var c = e.target.closest('.kan-card'); if (!c) return; drag = c; c.classList.add('drag'); e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', c.dataset.id); } catch (x) {} });
    kb.addEventListener('dragend', function () { if (drag) drag.classList.remove('drag'); drag = null; $$('.kan-col', kb).forEach(function (c) { c.classList.remove('over'); }); });
    $$('.kan-col', kb).forEach(function (col) {
      col.addEventListener('dragover', function (e) { if (!drag) return; e.preventDefault(); col.classList.add('over'); });
      col.addEventListener('dragleave', function (e) { if (!col.contains(e.relatedTarget)) col.classList.remove('over'); });
      col.addEventListener('drop', function (e) {
        e.preventDefault(); col.classList.remove('over'); if (!drag) return;
        var card = drag, to = col.dataset.etapa, from = card.closest('.kan-col').dataset.etapa; if (to === from) return;
        var go = function (motivo) { mover(card.dataset.id, to, motivo, function () { $('[data-drop]', col).appendChild(card); recount(); toast('Movido a «' + $('.kan-t', col).textContent.trim() + '»'); }); };
        if (to === 'perdido') pedirMotivo(card.dataset.nombre, go); else go();
      });
    });
    kb.addEventListener('click', function (e) {
      var b = e.target.closest('[data-mv]'); if (!b) return;
      var card = b.closest('.kan-card'), to = b.dataset.mv, col = $('.kan-col[data-etapa="' + to + '"]', kb);
      mover(card.dataset.id, to, '', function () { $('[data-drop]', col).appendChild(card); recount(); location.reload(); });
    });
  }

  /* ---- Ficha del prospecto: cambiar etapa ---- */
  if (C.leadId) {
    $$('[data-stage]').forEach(function (b) {
      b.addEventListener('click', function (ev) {
        ev.preventDefault(); var to = b.dataset.stage;
        var go = function (m) { mover(C.leadId, to, m, function () { location.reload(); }); };
        if (to === 'perdido') pedirMotivo($('#lostName').textContent, go); else go();
      });
    });
  }

  /* ---- Campañas: selección y vista previa ---- */
  var cf = $('#campForm');
  if (cf) {
    var chks = $$('.camp-chk', cf), all = $('#campAll'), sel = $('#campSel');
    var upd = function () { var n = chks.filter(function (c) { return c.checked && !c.disabled; }).length; sel.textContent = n; $('button.btn-brand', cf).disabled = n === 0; };
    all.addEventListener('change', function () { chks.forEach(function (c) { if (!c.disabled) c.checked = all.checked; }); upd(); });
    chks.forEach(function (c) { c.addEventListener('change', upd); });
    $$('tr[data-prev]', cf).forEach(function (tr) { tr.addEventListener('click', function () { $('#campPrev').textContent = tr.dataset.prev; }); });
    $('#campMsg').addEventListener('input', function () { $('#campPrev').textContent = this.value.replace(/\{(\w+)\}/g, function (m, k) { return { nombre: 'María', plan: 'Mensual', gimnasio: 'tu gimnasio', vence: '15/11/2026', dias: 'en 3 días', saldo: 'S/ 50.00' }[k] || m; }); });
  }

  /* ---- Campaña: marcar envíos ---- */
  $$('.camp-send').forEach(function (a) {
    a.addEventListener('click', function () {
      post('crm/marcarEnvio', { id: a.dataset.env }).then(function (d) {
        if (!d.ok) return; var td = a.closest('tr').querySelector('.env-st');
        if (td.textContent.indexOf('Enviado') === -1) { C.enviados = (C.enviados || 0) + 1; var cnt = $('#campCount'); if (cnt) cnt.textContent = C.enviados + '/' + C.total; var bar = $('#campBar'); if (bar) bar.style.width = Math.round(C.enviados / C.total * 100) + '%'; }
        td.innerHTML = '<span class="badge-soft success"><i class="bi bi-check2"></i> Enviado hoy</span>'; a.className = 'btn btn-sm btn-outline-secondary camp-send'; a.innerHTML = '<i class="bi bi-whatsapp"></i> Reenviar';
      });
    });
  });
});
