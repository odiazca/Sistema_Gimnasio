document.addEventListener('DOMContentLoaded', function () {
  const $ = id => document.getElementById(id), IA = window.IA;
  let estado = null, actual = null, chart = null, previo = null;
  const modalCfg = () => bootstrap.Modal.getOrCreateInstance($('iaCfgModal'));

  function pintarListas(d) {
    const li = (x, fav) => '<div class="ia-q" data-id="' + x.id + '"><button class="ia-q-main" data-run="' + x.id + '" title="' + IA.esc(x.pregunta) + '"><b>' + IA.esc(x.titulo || x.pregunta) + '</b><small>' + IA.esc(x.pregunta) + '</small></button>' +
      '<button class="ia-ic' + (x.favorito ? ' on' : '') + '" data-fav="' + x.id + '" title="Favorita"><i class="bi bi-star' + (x.favorito ? '-fill' : '') + '"></i></button>' +
      '<button class="ia-ic" data-del="' + x.id + '" title="Eliminar"><i class="bi bi-trash"></i></button></div>';
    $('iaFavs').innerHTML = d.favoritas.length ? d.favoritas.map(li).join('') : '<div class="text-muted small p-2">Marca con la estrella tus consultas frecuentes.</div>';
    $('iaRec').innerHTML = d.recientes.length ? d.recientes.map(li).join('') : '<div class="text-muted small p-2">Aún no hay consultas.</div>';
  }
  function pintarConfig() {
    const c = estado.config; $('iaSetup').classList.toggle('d-none', c.listo);
    $('cfProv').value = c.proveedor; $('cfUrl').value = c.url; $('cfModelo').value = c.modelo; $('cfResumen').checked = c.resumen;
    $('cfClave').value = ''; $('cfClave').placeholder = c.clave_puesta ? '•••••••• (guardada; deja vacío para conservarla)' : 'Pega tu API key';
    $('cfBorrar').classList.toggle('d-none', !c.clave_puesta); $('cfAyuda').textContent = estado.presets[c.proveedor].ayuda;
    $('iaEstadoPill').innerHTML = c.listo ? '<i class="bi bi-circle-fill text-success"></i> IA conectada · ' + IA.esc(c.modelo) : '<i class="bi bi-circle-fill text-warning"></i> Falta configurar';
  }
  async function cargar() { const d = await IA.get('asistente/estado'); if (!d.ok) return IA.toast(d.error || 'Error', 'err'); estado = d; pintarConfig(); pintarListas(d); }

  function ocupado(on) { $('iaBtn').disabled = on; $('iaBtn').innerHTML = on ? '<span class="spinner-border spinner-border-sm"></span> Pensando…' : '<i class="bi bi-stars"></i> Preguntar'; if (on) { $('iaOut').innerHTML = '<div class="ia-card ia-load"><div class="spinner-border text-warning"></div><p>Analizando tu pregunta y consultando los datos…</p></div>'; } }

  async function preguntar(q) {
    q = (q || $('iaQ').value).trim(); if (q.length < 3) return IA.toast('Escribe una pregunta.', 'err');
    ocupado(true);
    const d = await IA.api('asistente/preguntar', { pregunta: q, previo: previo ? JSON.stringify(previo) : '' }); ocupado(false);
    if (!d.ok) { $('iaOut').innerHTML = '<div class="ia-card ia-err"><i class="bi bi-exclamation-triangle-fill"></i> ' + IA.esc(d.error || 'Error') + '</div>'; return; }
    if (d.mensaje) { $('iaOut').innerHTML = '<div class="ia-card"><i class="bi bi-info-circle-fill text-warning"></i> ' + IA.esc(d.mensaje) + '</div>'; return; }
    mostrar(d); pintarListas(d);
    if (estado.config.resumen && d.filas.length) resumen(d);
  }
  async function correr(id) {
    ocupado(true); const d = await IA.api('asistente/ejecutar', { id }); ocupado(false);
    if (!d.ok) { $('iaOut').innerHTML = '<div class="ia-card ia-err"><i class="bi bi-exclamation-triangle-fill"></i> ' + IA.esc(d.error) + '</div>'; return; }
    $('iaQ').value = d.pregunta; mostrar(d); pintarListas(d);
  }
  async function resumen(d) {
    const el = $('iaRes'); if (!el) return; el.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Resumiendo…';
    const r = await IA.api('asistente/resumir', { pregunta: d.pregunta, columnas: d.columnas, filas: d.filas.slice(0, 25) });
    const e2 = $('iaRes'); if (e2 && actual === d) e2.innerHTML = r.ok && r.resumen ? '<i class="bi bi-chat-quote-fill"></i> ' + IA.esc(r.resumen) : '';
  }

  function elegirGrafico(d) {
    let g = Object.assign({}, d.grafico), cols = d.columnas;
    const ix = g.x ? cols.indexOf(g.x) : -1, iy = g.y ? cols.indexOf(g.y) : -1;
    if (g.tipo === 'none' || ix < 0 || iy < 0 || d.filas.length < 2) return null;
    if (!d.filas.some(r => typeof r[iy] === 'number')) return null;
    return { tipo: g.tipo, ix, iy };
  }
  function dibujar(d) {
    if (chart) { chart.destroy(); chart = null; }
    const g = elegirGrafico(d), cv = $('iaChart'); if (!cv) return;
    if (!g || typeof Chart === 'undefined') { cv.closest('.ia-chartbox').classList.add('d-none'); return; }
    const rows = d.filas.slice(0, g.tipo === 'pie' ? 8 : 40), labels = rows.map(r => String(r[g.ix]).slice(0, 28)), data = rows.map(r => Number(r[g.iy]) || 0);
    const pal = ['#ff5a1f', '#ffb347', '#3b82f6', '#10b981', '#8b5cf6', '#ef4444', '#06b6d4', '#f59e0b'];
    const dinero = IA.moneyRe.test(d.columnas[g.iy]);
    const largo = g.tipo === 'bar' && (rows.length > 8 || labels.some(l => l.length > 12));
    const css = getComputedStyle(document.documentElement), tx = css.getPropertyValue('--muted').trim() || '#64748b', ln = css.getPropertyValue('--line').trim() || '#e8ecf3';
    chart = new Chart(cv, {
      type: g.tipo === 'pie' ? 'doughnut' : g.tipo,
      data: { labels, datasets: [{ label: d.columnas[g.iy].replace(/_/g, ' '), data, backgroundColor: g.tipo === 'pie' ? pal : (g.tipo === 'line' ? 'rgba(255,90,31,.15)' : '#ff5a1f'), borderColor: g.tipo === 'line' ? '#ff5a1f' : undefined, borderWidth: g.tipo === 'line' ? 3 : 0, borderRadius: 8, fill: g.tipo === 'line', tension: .35, pointRadius: g.tipo === 'line' ? 3 : 0 }] },
      options: { indexAxis: largo ? 'y' : 'x', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: g.tipo === 'pie', position: 'bottom', labels: { color: tx } }, tooltip: { callbacks: { label: c => ' ' + (dinero ? IA.money(c.parsed.y ?? c.parsed.x ?? c.parsed) : IA.num(c.parsed.y ?? c.parsed.x ?? c.parsed)) } } },
        scales: g.tipo === 'pie' ? {} : { x: { grid: { display: false }, ticks: { color: tx } }, y: { grid: { color: ln }, ticks: { color: tx } } } }
    });
  }
  function mostrar(d) {
    actual = d; previo = { pregunta: d.pregunta, sql: d.sql };
    const g = elegirGrafico(d);
    $('iaOut').innerHTML = '<div class="ia-card"><div class="ia-head"><div><h5>' + IA.esc(d.titulo) + '</h5><small class="text-muted">' + d.filas.length + ' fila' + (d.filas.length === 1 ? '' : 's') + (d.truncado ? ' (límite alcanzado)' : '') + ' · ' + d.ms + ' ms · ' + IA.esc(d.pregunta) + '</small></div>' +
      '<div class="ia-acts"><button class="ia-ic' + (d.favorito ? ' on' : '') + '" id="iaFavBtn" title="Favorita"><i class="bi bi-star' + (d.favorito ? '-fill' : '') + '"></i></button><button class="ia-ic" id="iaCsv" title="Descargar CSV"><i class="bi bi-filetype-csv"></i></button><button class="ia-ic" id="iaSqlBtn" title="Ver SQL"><i class="bi bi-code-slash"></i></button></div></div>' +
      '<div class="ia-sum" id="iaRes"></div><pre class="ia-sql d-none" id="iaSql">' + IA.esc(d.sql) + '</pre>' +
      (g ? '<div class="ia-chartbox"><canvas id="iaChart"></canvas></div>' : '') +
      (d.filas.length ? IA.tabla(d.columnas, d.filas) : '<p class="text-muted p-3 mb-0">La consulta no devolvió resultados.</p>') + '</div>';
    dibujar(d);
    $('iaFavBtn').onclick = () => fav(d.id); $('iaSqlBtn').onclick = () => $('iaSql').classList.toggle('d-none');
    $('iaCsv').onclick = () => { const f = document.createElement('form'); f.method = 'post'; f.action = IA.base + '/asistente/csv'; f.innerHTML = '<input name="csrf" value="' + IA.csrf + '"><input name="id" value="' + d.id + '">'; document.body.appendChild(f); f.submit(); f.remove(); };
    $('iaOut').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
  async function fav(id) { const d = await IA.api('asistente/favorito', { id }); if (d.ok) { pintarListas(d); const f = d.favoritas.some(x => x.id == id); const b = $('iaFavBtn'); if (b) { b.classList.toggle('on', f); b.innerHTML = '<i class="bi bi-star' + (f ? '-fill' : '') + '"></i>'; } } }

  document.addEventListener('click', async e => {
    const t = e.target.closest('[data-run],[data-fav],[data-del],[data-chip]'); if (!t) return;
    if (t.dataset.run) { previo = null; correr(t.dataset.run); }
    else if (t.dataset.fav) fav(t.dataset.fav);
    else if (t.dataset.del) { const d = await IA.api('asistente/borrar', { id: t.dataset.del }); if (d.ok) pintarListas(d); }
    else if (t.dataset.chip) { $('iaQ').value = t.dataset.chip; previo = null; preguntar(t.dataset.chip); }
  });
  $('iaBtn').onclick = () => preguntar();
  $('iaQ').addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); preguntar(); } });
  $('iaNueva').onclick = () => { previo = null; $('iaQ').value = ''; $('iaOut').innerHTML = ''; $('iaQ').focus(); };
  document.querySelectorAll('[data-cfg]').forEach(b => b.onclick = () => modalCfg().show());
  $('cfProv').onchange = () => { const p = estado.presets[$('cfProv').value]; $('cfUrl').value = p.url; $('cfModelo').value = p.modelo; $('cfAyuda').textContent = p.ayuda; };
  $('cfBorrar').onclick = async () => { if (!confirm('¿Borrar la clave guardada?')) return; const d = await IA.api('asistente/guardarconfig', Object.assign(datosCfg(), { clave: '__borrar__' })); if (d.ok) { estado.config = d.config; pintarConfig(); IA.toast('Clave borrada'); } };
  function datosCfg() { return { proveedor: $('cfProv').value, url: $('cfUrl').value, modelo: $('cfModelo').value, resumen: $('cfResumen').checked ? 1 : 0, clave: $('cfClave').value }; }
  async function guardar(probar) {
    const d = await IA.api('asistente/guardarconfig', datosCfg()); if (!d.ok) return IA.toast(d.error, 'err');
    estado.config = d.config; pintarConfig();
    if (probar) { const p = await IA.api('asistente/probar'); IA.toast(p.ok ? p.mensaje : p.error, p.ok ? 'ok' : 'err'); } else IA.toast('Configuración guardada');
    return d;
  }
  $('cfGuardar').onclick = async () => { await guardar(false); modalCfg().hide(); };
  $('cfProbar').onclick = () => guardar(true);
  $('stGuardar').onclick = async () => {
    const k = $('stClave').value.trim(); if (!k) return IA.toast('Pega tu clave de Gemini.', 'err');
    const p = estado.presets.gemini; $('cfProv').value = 'gemini'; $('cfUrl').value = p.url; $('cfModelo').value = p.modelo; $('cfClave').value = k;
    const d = await guardar(true); if (d) $('stClave').value = '';
  };
  $('iaVer').onclick = async () => { const d = await IA.get('asistente/esquema'); $('iaEsq').textContent = d.esquema || ''; bootstrap.Modal.getOrCreateInstance($('iaEsqModal')).show(); };
  cargar();
});
