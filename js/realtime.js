// js/realtime.js
// Conexión WebSocket con reconexión automática
import { endSession } from './api.js';

export function connectRealtime({ onEvent, onReconnect }) {
    let retry = 0;
    let opened = false;

    function open() {
        const scheme = location.protocol === 'https:' ? 'wss' : 'ws';
        const ws = new WebSocket(`${scheme}://${location.host}/ws`);

        ws.addEventListener('open', () => {
            ws.send(JSON.stringify({ type: 'auth', token: localStorage.getItem('token') }));
        });

        ws.addEventListener('message', event => {
            let data;
            try { data = JSON.parse(event.data); } catch (e) { return; }
            if (data.type === 'ready') {
                retry = 0;
                if (opened) onReconnect(); // recupera lo que se perdió mientras no había conexión
                opened = true;
                return;
            }
            onEvent(data);
        });

        ws.addEventListener('close', event => {
            if (event.code === 4001) return endSession(); // token inválido o expirado
            setTimeout(open, Math.min(1000 * 2 ** retry++, 15000));
        });
    }

    open();
}
