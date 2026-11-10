/* Chat IA: burbuja flotante en todas las pantallas (administrador) o página completa (#chatPage) */
(() => {
  if (window.__chatIA || !window.IA || !window.IAU) return; window.__chatIA = true;
  const { esc, fmt, money, ico, api, toast } = IAU;
  const SUG = ['¿Cuánto vendimos hoy?', '¿Qué productos tienen stock bajo?', '¿Qué clientes nos deben más?', '¿Qué proveedor nos surte más?', '¿Cómo registro una compra?'];
  const page = document.getElementById('chatPage');
  let ST = null, enviando = false, root, box, inp, vistos = false;

  const fmtTxt = (t) => esc(t).replace(/\*\*(.+?)\*\*/g, '<b>$1</b>').replace(/\n/g, '<br>');
  const MONEY = /(total|importe|ingreso|venta|monto|utilidad|costo|precio|valor|deuda|gasto|margen|saldo|promedio)/i;
  const cel = (v, c) => v === null ? '—' : typeof v === 'number' ? (MONEY.test(c) ? money(v) : fmt(v, Number.isInteger(v) ? 0 : 2)) : esc(String(v));

  function build() {
    if (root) return;
    root = document.createElement('div'); root.className = 'chat-root' + (page ? ' chat-page' : '');
    root.innerHTML = `<div class="chat-panel" id="chatPanel"${page ? '' : ' hidden'}>
      <div class="chat-head"><div class="chat-av">${ico('sparkles', 'w-5 h-5')}</div><div class="flex-grow"><b>Asistente</b><small id="chatSub">Pregúntame sobre tu negocio</small></div>
        <button type="button" id="chatClr" title="Nueva conversación">${ico('refresh')}</button>${page ? '' : `<button type="button" id="chatX" title="Cerrar">${ico('x')}</button>`}</div>
      <div class="chat-msgs" id="chatMsgs"></div>
      <form class="chat-in" id="chatForm"><textarea id="chatInp" rows="1" maxlength="400" placeholder="Escribe tu pregunta…" aria-label="Mensaje"></textarea><button id="chatGo" aria-label="Enviar">${ico('send', 'w-5 h-5')}</button></form></div>
      ${page ? '' : `<button type="button" class="chat-fab" id="chatFab" aria-label="Abrir chat con IA">${ico('chat', 'w-7 h-7')}</button>`}`;
    (page || document.body).appendChild(root);
    box = root.querySelector('#chatMsgs'); inp = root.querySelector('#chatInp');
    root.querySelector('#chatFab')?.addEventListener('click', toggle); root.querySelector('#chatX')?.addEventListener('click', toggle);
    root.querySelector('#chatClr').onclick = limpiar;
    root.querySelector('#chatForm').onsubmit = (e) => { e.preventDefault(); enviar(); };
    inp.addEventListener('keydown', (e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); enviar(); } });
    inp.addEventListener('input', () => { inp.style.height = 'auto'; inp.style.height = Math.min(110, inp.scrollHeight) + 'px'; });
    box.addEventListener('click', (e) => {
      const c = e.target.closest('.chat-sug'); if (c) { inp.value = c.textContent; enviar(); return; }
      const t = e.target.closest('[data-tg]'); if (t) { const n = t.nextElementSibling; n.hidden = !n.hidden; t.classList.toggle('on', !n.hidden); }
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !page && !root.querySelector('#chatPanel').hidden) toggle(); });
    if (page) cargar();
  }
  async function toggle() {
    const p = root.querySelector('#chatPanel'); p.hidden = !p.hidden; document.body.classList.toggle('chat-open', !p.hidden);
    if (!p.hidden) { if (!vistos) await cargar(); setTimeout(() => inp.focus(), 100); }
  }
  async function cargar() {
    vistos = true; box.innerHTML = '';
    try { ST = await api(IA.u.chatEstado); } catch (x) { return bot('No pude conectarme al sistema: ' + x.message); }
    root.querySelector('#chatSub').textContent = ST.listo ? ST.proveedor : 'Falta activar la IA';
    if (!ST.listo) return setup();
    if (!ST.historial.length) { bot(`¡Hola! Soy el asistente de **${ST.empresa}**. Puedo consultar tus productos, ventas, clientes, proveedores, compras y cuentas por cobrar, o explicarte cómo usar el sistema.`); sugerencias(); }
    else { ST.historial.forEach((h) => { user(h.q); bot(h.a); }); }
    scroll();
  }
  function setup() {
    const d = document.createElement('div'); d.className = 'chat-m bot';
    d.innerHTML = `<div class="chat-b"><b>Activa la IA gratis en 1 minuto</b><br>1. Entra a <a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener" class="underline text-teal-dark">aistudio.google.com/apikey</a> y crea una clave gratuita.<br>2. Pégala aquí:
      <form class="chat-key mt-2"><input type="password" class="ia-input" placeholder="Clave de Gemini" autocomplete="new-password"><button class="ia-btn w-full mt-2">Activar</button><div class="text-xs text-red-600 mt-1" data-err></div></form></div>`;
    box.appendChild(d);
    d.querySelector('form').onsubmit = async (e) => {
      e.preventDefault(); const f = e.target, k = f.querySelector('input').value.trim(), er = f.querySelector('[data-err]'), b = f.querySelector('button'); er.textContent = '';
      if (k.length < 8) { er.textContent = 'Pega la clave completa.'; return; } b.disabled = true; b.textContent = 'Probando…';
      try { await IAU.activar(k); toast('IA activada'); cargar(); } catch (x) { er.textContent = x.message; b.disabled = false; b.textContent = 'Activar'; }
    };
  }
  function scroll() { box.scrollTop = box.scrollHeight; }
  function user(t) { const d = document.createElement('div'); d.className = 'chat-m me'; d.innerHTML = `<div class="chat-b">${esc(t)}</div>`; box.appendChild(d); }
  function bot(t, extra = '') { const d = document.createElement('div'); d.className = 'chat-m bot'; d.innerHTML = `<div class="chat-b">${fmtTxt(t)}${extra}</div>`; box.appendChild(d); return d; }
  function sugerencias() { const d = document.createElement('div'); d.className = 'chat-sugs'; d.innerHTML = SUG.map((s) => `<button type="button" class="chat-sug">${esc(s)}</button>`).join(''); box.appendChild(d); }
  function tabla(t) {
    if (!t || !t.filas.length || (t.filas.length === 1 && t.columnas.length === 1)) return '';
    return `<button type="button" class="chat-tg" data-tg>${ico('table', 'w-3.5 h-3.5 inline -mt-0.5')} Ver datos (${t.total})</button><div class="chat-tbl" hidden><div class="overflow-x-auto"><table class="ia-t"><thead><tr>${t.columnas.map((c) => `<th>${esc(c.replace(/_/g, ' '))}</th>`).join('')}</tr></thead><tbody>${t.filas.map((f) => `<tr>${f.map((v, i) => `<td class="${typeof v === 'number' ? 'r' : ''}">${cel(v, t.columnas[i])}</td>`).join('')}</tr>`).join('')}</tbody></table></div>${t.total > t.filas.length ? `<div class="text-xs text-slate-500 px-2 pb-1">Se muestran ${t.filas.length} de ${t.total}. Para el detalle completo usa el Asistente de reportes.</div>` : ''}</div>`;
  }
  async function enviar() {
    const m = inp.value.trim(); if (!m || enviando) return;
    if (ST && !ST.listo) { toast('Primero activa la IA', 'warning'); return; }
    box.querySelector('.chat-sugs')?.remove(); user(m); inp.value = ''; inp.style.height = 'auto'; enviando = true; root.querySelector('#chatGo').disabled = true;
    const t = bot(''); t.querySelector('.chat-b').innerHTML = '<span class="ia-dots"><span></span><span></span><span></span></span>'; scroll();
    try { const r = await api(IA.u.chatEnviar, { mensaje: m }); t.querySelector('.chat-b').innerHTML = fmtTxt(r.texto) + tabla(r.tabla); }
    catch (x) { t.querySelector('.chat-b').innerHTML = `<span class="text-red-600">${ico('warn', 'w-4 h-4 inline -mt-0.5 mr-1')}${esc(x.message)}</span>`; }
    enviando = false; root.querySelector('#chatGo').disabled = false; scroll(); inp.focus();
  }
  async function limpiar() { try { await api(IA.u.chatLimpiar, {}); } catch (x) { /* ignorar */ } box.innerHTML = ''; vistos = false; cargar(); }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', build); else build();
})();
