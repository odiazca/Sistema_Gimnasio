/* Utilidades comunes del Asistente IA y del Chat IA (window.IA lo define el layout) */
(function () {
  const IA = window.IA = window.IA || {};
  IA.esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  IA.moneyRe = /(total|importe|venta|monto|utilidad|costo|precio|valor|deuda|saldo|ingreso|gasto|pagado|cobrado|margen|esperado|contado|diferencia|inicial)/i;
  IA.num = (v, d) => window.fmtNum ? window.fmtNum(v, d == null ? (Number.isInteger(Number(v)) ? 0 : 2) : d) : Number(v).toLocaleString('es-PE');
  IA.money = v => window.fmtMoney ? window.fmtMoney(v) : (IA.moneda || 'S/') + ' ' + Number(v).toFixed(2);
  IA.cell = (v, col) => {
    if (v === null || v === undefined || v === '') return '<span class="text-muted">—</span>';
    if (typeof v === 'number') return IA.moneyRe.test(col) && !/(cantidad|unidades|visitas|stock|dias|edad|id)$/i.test(col) ? IA.money(v) : IA.num(v);
    return IA.esc(v);
  };
  IA.api = async (ruta, datos) => {
    const fd = new FormData(); fd.append('csrf', IA.csrf);
    Object.entries(datos || {}).forEach(([k, v]) => fd.append(k, typeof v === 'object' ? JSON.stringify(v) : v));
    let r, j;
    try { r = await fetch(IA.base + '/' + ruta, { method: 'POST', body: fd, headers: { 'X-CSRF-Token': IA.csrf, 'Accept': 'application/json' } }); j = await r.json(); }
    catch (e) { return { ok: false, error: 'No se pudo comunicar con el servidor.' }; }
    return j;
  };
  IA.get = async ruta => { try { return await (await fetch(IA.base + '/' + ruta, { headers: { 'Accept': 'application/json' } })).json(); } catch (e) { return { ok: false, error: 'Sin conexión.' }; } };
  IA.toast = (msg, tipo) => {
    let w = document.getElementById('iaToasts');
    if (!w) { w = document.createElement('div'); w.id = 'iaToasts'; w.className = 'ia-toasts'; document.body.appendChild(w); }
    const t = document.createElement('div'); t.className = 'ia-toast ' + (tipo || 'ok'); t.textContent = msg; w.appendChild(t);
    setTimeout(() => { t.classList.add('out'); setTimeout(() => t.remove(), 300); }, 3500);
  };
  IA.tabla = (cols, filas, max) => {
    const f = max ? filas.slice(0, max) : filas;
    return '<div class="ia-tbl"><table><thead><tr>' + cols.map(c => '<th>' + IA.esc(c.replace(/_/g, ' ')) + '</th>').join('') + '</tr></thead><tbody>' +
      f.map(r => '<tr>' + r.map((v, i) => '<td class="' + (typeof v === 'number' ? 'n' : '') + '">' + IA.cell(v, cols[i]) + '</td>').join('') + '</tr>').join('') + '</tbody></table></div>';
  };
})();
