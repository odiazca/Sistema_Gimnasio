(function () {
  if (!window.Chart || !window.DASH) return;
  var D = window.DASH, CUR = window.CUR || 'S/';
  var charts = [];
  var css = function (v) { return getComputedStyle(document.documentElement).getPropertyValue(v).trim(); };
  var fmt = function (n) { return window.fmtMoney ? window.fmtMoney(n, 0) : CUR + ' ' + Math.round(n); };

  function theme() {
    var dark = document.documentElement.getAttribute('data-theme') === 'dark';
    return { grid: dark ? 'rgba(255,255,255,.06)' : 'rgba(15,23,42,.06)', text: css('--muted'), card: css('--card'), dark: dark };
  }

  function grad(ctx, color, h) {
    var g = ctx.createLinearGradient(0, 0, 0, h || 300);
    g.addColorStop(0, color + '66'); g.addColorStop(1, color + '00');
    return g;
  }

  function baseOpts(t, money) {
    return {
      responsive: true, maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: t.dark ? '#1e293b' : '#0f172a', padding: 12, cornerRadius: 10, titleFont: { weight: '600' },
          callbacks: money ? { label: function (c) { return ' ' + c.dataset.label + ': ' + fmt(c.parsed.y); } } : {}
        }
      },
      scales: {
        x: { grid: { display: false }, border: { display: false }, ticks: { color: t.text, font: { size: 11 }, maxRotation: 0, autoSkipPadding: 8 } },
        y: { beginAtZero: true, grid: { color: t.grid }, border: { display: false }, ticks: { color: t.text, font: { size: 11 }, callback: money ? function (v) { return v >= 1000 ? (v / 1000) + 'k' : v; } : undefined } }
      }
    };
  }

  function build() {
    charts.forEach(function (c) { c.destroy(); }); charts = [];
    var t = theme(), brand = '#ff5a1f', indigo = '#6366f1', red = '#ef4444', green = '#10b981';
    Chart.defaults.font.family = "'Inter', sans-serif";

    // Finanzas
    var el = document.getElementById('chFinanzas');
    if (el) {
      var ctx = el.getContext('2d'), f = D.finanzas;
      var o = baseOpts(t, true);
      o.scales.x.stacked = true; o.scales.y.stacked = false;
      charts.push(new Chart(ctx, {
        data: {
          labels: f.labels,
          datasets: [
            { type: 'bar', label: 'Membresías', data: f.membresias, backgroundColor: brand, borderRadius: 8, borderSkipped: false, stack: 'ing', maxBarThickness: 28 },
            { type: 'bar', label: 'Tienda', data: f.tienda, backgroundColor: indigo, borderRadius: 8, borderSkipped: false, stack: 'ing', maxBarThickness: 28 },
            { type: 'line', label: 'Gastos', data: f.gastos, borderColor: red, backgroundColor: red, borderWidth: 2.5, tension: .4, pointRadius: 3, pointHoverRadius: 6, pointBackgroundColor: t.card, pointBorderWidth: 2 }
          ]
        }, options: o
      }));
    }

    // Planes (donut)
    el = document.getElementById('chPlanes');
    if (el) {
      var p = D.planes, total = p.data.reduce(function (a, b) { return a + b; }, 0);
      document.getElementById('donutTotal').textContent = total;
      charts.push(new Chart(el, {
        type: 'doughnut',
        data: { labels: p.labels, datasets: [{ data: p.data, backgroundColor: p.colors, borderColor: t.card, borderWidth: 4, hoverOffset: 8 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '72%', plugins: { legend: { display: false } } }
      }));
      var lg = document.getElementById('planLegend');
      lg.innerHTML = p.labels.map(function (l, i) {
        var pc = total ? Math.round(p.data[i] / total * 100) : 0;
        return '<div><i style="background:' + p.colors[i] + '"></i>' + l + '<span>' + p.data[i] + ' <small class="text-muted fw-normal">(' + pc + '%)</small></span></div>';
      }).join('');
    }

    // Asistencia diaria (área)
    el = document.getElementById('chAsistencia');
    if (el) {
      var c2 = el.getContext('2d');
      charts.push(new Chart(c2, {
        type: 'line',
        data: { labels: D.asistencia.labels, datasets: [{ label: 'Asistencias', data: D.asistencia.data, borderColor: indigo, backgroundColor: grad(c2, indigo, 250), fill: true, tension: .4, borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 6, pointHoverBackgroundColor: indigo }] },
        options: baseOpts(t)
      }));
    }

    // Horas pico (barras, resalta las más altas)
    el = document.getElementById('chHoras');
    if (el) {
      var h = D.horas, max = Math.max.apply(null, h.data);
      charts.push(new Chart(el, {
        type: 'bar',
        data: { labels: h.labels, datasets: [{ label: 'Ingresos promedio', data: h.data, borderRadius: 6, borderSkipped: false, backgroundColor: h.data.map(function (v) { return v >= max * .75 ? brand : (t.dark ? '#2a3550' : '#cbd5e1'); }) }] },
        options: baseOpts(t)
      }));
    }

    // Altas de socios
    el = document.getElementById('chAltas');
    if (el) {
      var c3 = el.getContext('2d');
      charts.push(new Chart(c3, {
        type: 'line',
        data: { labels: D.altas.labels, datasets: [{ label: 'Nuevos socios', data: D.altas.data, borderColor: green, backgroundColor: grad(c3, green, 250), fill: true, tension: .4, borderWidth: 2.5, pointRadius: 4, pointBackgroundColor: t.card, pointBorderColor: green, pointBorderWidth: 2, pointHoverRadius: 7 }] },
        options: baseOpts(t)
      }));
    }

    // Sparklines de KPIs
    document.querySelectorAll('canvas[data-spark]').forEach(function (cv) {
      var data = D.spark[cv.dataset.spark] || [];
      var color = getComputedStyle(cv.closest('.kpi')).getPropertyValue('--c').trim() || brand;
      var cx = cv.getContext('2d');
      charts.push(new Chart(cx, {
        type: 'line',
        data: { labels: data.map(function (_, i) { return i; }), datasets: [{ data: data, borderColor: color, backgroundColor: grad(cx, color, 38), fill: true, tension: .4, borderWidth: 2, pointRadius: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { enabled: false } }, scales: { x: { display: false }, y: { display: false, beginAtZero: true } }, events: [] }
      }));
    });
  }

  // Contadores animados
  document.querySelectorAll('[data-count]').forEach(function (el) {
    var end = parseFloat(el.dataset.count) || 0, pre = el.dataset.prefix || '', dur = 900, t0 = null;
    function step(ts) {
      if (!t0) t0 = ts;
      var p = Math.min((ts - t0) / dur, 1), e = 1 - Math.pow(1 - p, 3);
      var v = Math.round(end * e); el.textContent = pre && window.fmtMoney ? window.fmtMoney(v, 0) : pre + (window.fmtNum ? window.fmtNum(v, 0) : v);
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  });

  build();
  document.addEventListener('themechange', build);
})();
