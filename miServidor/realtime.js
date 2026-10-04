// Tiempo real: WebSocket en /ws. El cliente se autentica con su token en el primer mensaje.
const { WebSocketServer } = require('ws');
const store = require('./db');

const AUTH_TIMEOUT_MS = 10000;
const HEARTBEAT_MS = 30000;

function attach(server) {
    const wss = new WebSocketServer({ server, path: '/ws', maxPayload: 4096 });
    const sockets = new Map(); // usuario (canónico) -> Set<ws>

    const isOnline = username => (sockets.get(username)?.size ?? 0) > 0;

    function sendTo(username, event) {
        const payload = JSON.stringify(event);
        for (const ws of sockets.get(username) || []) {
            if (ws.readyState === ws.OPEN) ws.send(payload);
        }
    }

    function announcePresence(username, online) {
        for (const watcher of store.getWatchers(username)) {
            sendTo(watcher, { type: 'presence', username, online });
        }
    }

    wss.on('connection', ws => {
        let user = null;
        ws.isAlive = true;
        ws.on('pong', () => { ws.isAlive = true; });

        const authTimer = setTimeout(() => { if (!user) ws.close(4001, 'auth timeout'); }, AUTH_TIMEOUT_MS);

        ws.on('message', data => {
            if (user) return; // después de autenticar el cliente no necesita enviar nada
            let msg;
            try { msg = JSON.parse(data.toString()); } catch (e) { return ws.close(4000, 'bad message'); }
            const username = msg && msg.type === 'auth' && typeof msg.token === 'string'
                ? store.getSessionUser(msg.token) : null;
            if (!username) return ws.close(4001, 'unauthorized');

            user = username;
            clearTimeout(authTimer);
            const wasOnline = isOnline(user);
            if (!sockets.has(user)) sockets.set(user, new Set());
            sockets.get(user).add(ws);
            ws.send(JSON.stringify({ type: 'ready', username: user }));
            if (!wasOnline) announcePresence(user, true);
        });

        ws.on('close', () => {
            clearTimeout(authTimer);
            if (!user) return;
            const set = sockets.get(user);
            set.delete(ws);
            if (set.size === 0) {
                sockets.delete(user);
                announcePresence(user, false);
            }
        });
        ws.on('error', () => ws.terminate());
    });

    // Cierra conexiones muertas
    const heartbeat = setInterval(() => {
        for (const ws of wss.clients) {
            if (!ws.isAlive) { ws.terminate(); continue; }
            ws.isAlive = false;
            ws.ping();
        }
    }, HEARTBEAT_MS);
    heartbeat.unref();
    wss.on('close', () => clearInterval(heartbeat));

    return { wss, sendTo, isOnline };
}

module.exports = { attach };
