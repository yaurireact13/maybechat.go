// Prepara el entorno de pruebas: base SQLite temporal y servidor (HTTP + WebSocket) en un puerto libre.
// Debe cargarse antes que server.js (lee las variables de entorno al importarse).
const fs = require('fs');
const os = require('os');
const path = require('path');
const WebSocket = require('ws');

const tmpDir = fs.mkdtempSync(path.join(os.tmpdir(), 'maybechat-test-'));
process.env.DB_FILE = path.join(tmpDir, 'test.db');

async function startServer() {
    const { app, createServer } = require('../server');
    app.set('rateLimit', false); // las pruebas de límites lo reactivan donde hace falta
    const server = createServer();
    await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
    const base = `http://127.0.0.1:${server.address().port}`;
    const sockets = [];

    async function request(method, url, { token, body } = {}) {
        const res = await fetch(base + url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                ...(token ? { Authorization: `Bearer ${token}` } : {})
            },
            body: body === undefined ? undefined : JSON.stringify(body)
        });
        return { status: res.status, body: await res.json().catch(() => null), headers: res.headers };
    }

    // Registra e inicia sesión; devuelve el token
    async function newUser(username, password = '123456') {
        await request('POST', '/register', { body: { username, password } });
        const { body } = await request('POST', '/login', { body: { username, password } });
        return body.token;
    }

    // Conecta un WebSocket. `events` acumula lo recibido; `next(type)` espera el siguiente evento de ese tipo.
    function connect(token = null, { auth = true } = {}) {
        return new Promise((resolve, reject) => {
            const ws = new WebSocket(base.replace('http', 'ws') + '/ws');
            sockets.push(ws);
            const events = [];
            const waiters = [];
            const client = {
                ws, events,
                closed: new Promise(r => ws.on('close', (code) => r(code))),
                send: data => ws.send(JSON.stringify(data)),
                next(type, timeout = 2000) {
                    const i = events.findIndex(e => e.type === type);
                    if (i >= 0) return Promise.resolve(events.splice(i, 1)[0]);
                    return new Promise((res, rej) => {
                        const w = { type, res };
                        waiters.push(w);
                        setTimeout(() => {
                            const k = waiters.indexOf(w);
                            if (k >= 0) { waiters.splice(k, 1); rej(new Error(`timeout esperando "${type}"`)); }
                        }, timeout);
                    });
                },
                // Verifica que NO llegue un evento de ese tipo en un rato corto
                async expectNone(type, ms = 150) {
                    await new Promise(r => setTimeout(r, ms));
                    assert(!events.some(e => e.type === type), `no debía llegar "${type}"`);
                },
                close: () => ws.close()
            };
            const assert = (cond, msg) => { if (!cond) throw new Error(msg); };
            ws.on('message', data => {
                const e = JSON.parse(data.toString());
                const k = waiters.findIndex(w => w.type === e.type);
                if (k >= 0) waiters.splice(k, 1)[0].res(e); else events.push(e);
            });
            ws.on('error', reject);
            ws.on('open', async () => {
                if (auth) {
                    client.send({ type: 'auth', token });
                    await client.next('ready').catch(reject);
                }
                resolve(client);
            });
        });
    }

    async function close() {
        for (const ws of sockets) ws.terminate();
        // espera a que el servidor procese los cierres antes de cerrar la base de datos
        const { wss } = app.locals.realtime;
        while (wss.clients.size > 0) await new Promise(r => setTimeout(r, 10));
        await new Promise(r => setTimeout(r, 20));
        await new Promise(resolve => server.close(resolve));
        require('../db').db.close();
        fs.rmSync(tmpDir, { recursive: true, force: true });
    }

    return { request, newUser, connect, close, tmpDir, base, app };
}

module.exports = { startServer };
