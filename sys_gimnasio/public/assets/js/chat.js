/* Chat IA: modo página (#chatPage) o burbuja flotante en todas las pantallas */
(function () {
  const IA = window.IA; if (!IA) return;
  const page = document.getElementById('chatPage');
  const SUG = ['¿Cuántos socios activos tengo?', '¿Qué membresías vencen esta semana?', '¿Cuánto ingresé este mes?', '¿Cómo registro un pago?'];
  let root, box, inp, send, cargado = false, busy = false;

  const html = '<div class="chat-head"><div class="chat-av"><i class="bi bi-stars"></i></div><div class="flex-grow-1"><b>Chat IA</b><small>Pregúntame por tus datos o cómo usar el sistema</small></div>' +
    '<button class="chat-hb" id="chNew" title="Nueva conversación"><i class="bi bi-arrow-counterclockwise"></i></button>' + (page ? '' : '<button class="chat-hb" id="chClose" title="Cerrar"><i class="bi bi-x-lg"></i></button>') + '</div>' +
    '<div class="chat-box" id="chBox"></div><div class="chat-in"><textarea id="chInp" rows="1" maxlength="500" placeholder="Escribe tu pregunta…"></textarea><button id="chSend" class="chat-send" title="Enviar"><i class="bi bi-send-fill"></i></button></div>';

  if (page) { root = page; root.classList.add('chat-panel', 'chat-page'); root.innerHTML = html; }
  else {
    const fab = document.createElement('button'); fab.className = 'chat-fab'; fab.title = 'Chat IA'; fab.innerHTML = '<i class="bi bi-stars"></i>'; document.body.appendChild(fab);
    root = document.createElement('div'); root.className = 'chat-panel chat-float d-none'; root.innerHTML = html; document.body.appendChild(root);
    fab.onclick = () => { const o = root.classList.toggle('d-none'); fab.classList.toggle('open', !o); if (!o) { iniciar(); inp.focus(); } };
    root.querySelector('#chClose').onclick = () => { root.classList.add('d-none'); fab.classList.remove('open'); };
  }
  box = root.querySelector('#chBox'); inp = root.querySelector('#chInp'); send = root.querySelector('#chSend');

  const msg = (txt, quien, tabla) => {
    const d = document.createElement('div'); d.className = 'chat-m ' + quien;
    d.innerHTML = '<div class="chat-b">' + IA.esc(txt).replace(/\n/g, '<br>') + '</div>';
    if (tabla && tabla.filas && tabla.filas.length) {
      const id = 'ct' + Math.random().toString(36).slice(2, 7);
      d.innerHTML += '<button class="chat-ver" data-t="' + id + '"><i class="bi bi-table"></i> Ver datos (' + tabla.total + ')</button><div class="chat-tbl d-none" id="' + id + '">' + IA.tabla(tabla.columnas, tabla.filas) + (tabla.total > tabla.filas.length ? '<small class="text-muted">Mostrando ' + tabla.filas.length + ' de ' + tabla.total + '. Para verlo completo usa el Asistente IA.</small>' : '') + '</div>';
    }
    box.appendChild(d); box.scrollTop = box.scrollHeight; return d;
  };
  box.addEventListener('click', e => {
    const b = e.target.closest('.chat-ver'); if (b) root.querySelector('#' + b.dataset.t).classList.toggle('d-none');
    const s = e.target.closest('.chat-sug'); if (s) enviar(s.textContent);
  });
  function bienvenida(listo) {
    box.innerHTML = ''; msg('¡Hola! Soy el asistente de tu gimnasio. Puedo consultar tus datos y explicarte cómo usar el sistema.' + (listo ? '' : '\n\nAntes debes configurar la IA en «Asistente IA» → Configurar IA.'), 'bot');
    if (listo) { const s = document.createElement('div'); s.className = 'chat-sugs'; s.innerHTML = SUG.map(x => '<button class="chat-sug">' + IA.esc(x) + '</button>').join(''); box.appendChild(s); }
  }
  async function iniciar() {
    if (cargado) return; cargado = true;
    const d = await IA.get('chat/estado'); if (!d.ok) { box.innerHTML = ''; msg(d.error || 'No disponible.', 'bot'); return; }
    bienvenida(d.listo); (d.historial || []).forEach(h => { msg(h.q, 'user'); msg(h.a, 'bot', h.tabla); });
  }
  async function enviar(t) {
    t = (t || inp.value).trim(); if (!t || busy) return; busy = true; send.disabled = true; inp.value = ''; inp.style.height = '';
    const s = box.querySelector('.chat-sugs'); if (s) s.remove();
    msg(t, 'user'); const w = msg('', 'bot'); w.querySelector('.chat-b').innerHTML = '<span class="chat-dots"><i></i><i></i><i></i></span>';
    const d = await IA.api('chat/enviar', { mensaje: t }); w.remove();
    msg(d.ok ? d.texto : (d.error || 'Error'), 'bot' + (d.ok ? '' : ' err'), d.ok ? d.tabla : null);
    busy = false; send.disabled = false; inp.focus();
  }
  send.onclick = () => enviar();
  inp.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); enviar(); } });
  inp.addEventListener('input', () => { inp.style.height = 'auto'; inp.style.height = Math.min(inp.scrollHeight, 110) + 'px'; });
  root.querySelector('#chNew').onclick = async () => { await IA.api('chat/limpiar'); bienvenida(true); };
  if (page) iniciar();
})();
