// Prepara el entorno de pruebas: base SQLite temporal, Ollama simulado y servidor en un puerto libre.
// Debe cargarse antes que server.js (lee las variables de entorno al importarse).
const fs = require('fs');
const os = require('os');
const path = require('path');
const http = require('http');

const tmpDir = fs.mkdtempSync(path.join(os.tmpdir(), 'maybechat-test-'));
process.env.DB_FILE = path.join(tmpDir, 'test.db');

// Ollama simulado: guarda las peticiones; con ollama.status != 200 simula un fallo (p. ej. modelo no descargado)
const ollama = { requests: [], reply: ' hola desde ollama ', status: 200 };
const ollamaServer = http.createServer((req, res) => {
    let body = '';
    req.on('data', d => body += d);
    req.on('end', () => {
        const json = JSON.parse(body);
        ollama.requests.push({ url: req.url, body: json });
        if (ollama.status !== 200) {
            res.statusCode = ollama.status;
            return res.end('{"error":"model not found"}');
        }
        res.setHeader('content-type', 'application/json');
        res.end(JSON.stringify({ message: { role: 'assistant', content: ollama.reply } }));
    });
});

async function startServer() {
    await new Promise(resolve => ollamaServer.listen(0, '127.0.0.1', resolve));
    process.env.OLLAMA_URL = `http://127.0.0.1:${ollamaServer.address().port}`;
    process.env.OLLAMA_MODEL = 'modelo-test';

    const app = require('../server');
    const server = await new Promise(resolve => {
        const s = app.listen(0, '127.0.0.1', () => resolve(s));
    });
    const base = `http://127.0.0.1:${server.address().port}`;

    async function request(method, url, { token, body } = {}) {
        const res = await fetch(base + url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                ...(token ? { Authorization: `Bearer ${token}` } : {})
            },
            body: body === undefined ? undefined : JSON.stringify(body)
        });
        return { status: res.status, body: await res.json().catch(() => null) };
    }

    // Registra e inicia sesión; devuelve el token
    async function newUser(username, password = '123456') {
        await request('POST', '/register', { body: { username, password } });
        const { body } = await request('POST', '/login', { body: { username, password } });
        return body.token;
    }

    async function close() {
        await new Promise(resolve => server.close(resolve));
        await new Promise(resolve => ollamaServer.close(resolve));
        require('../db').db.close();
        fs.rmSync(tmpDir, { recursive: true, force: true });
    }

    return { request, newUser, close, ollama, tmpDir, base };
}

const contact = (id, extra = {}) => ({
    id, name: `Contacto ${id}`, status: '', image: 'img/x.jpg',
    group: false, favorite: false, unread: false, unreadCount: 0, ...extra
});

module.exports = { startServer, contact };
