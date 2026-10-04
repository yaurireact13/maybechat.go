const express = require('express');
const http = require('http');
const path = require('path');
const bcrypt = require('bcryptjs');
const store = require('./db');
const { createLimiter } = require('./ratelimit');
const { attach } = require('./realtime');
const { importLegacyJson } = require('./migrate-json');

const app = express();
const PORT = process.env.PORT || 3000;
const ROOT = path.join(__dirname, '..');

// Detrás de un proxy (Render, Fly...) hay que decir cuántos hay para que req.ip sea la IP real
if (process.env.TRUST_PROXY) {
    app.set('trust proxy', Number(process.env.TRUST_PROXY) || process.env.TRUST_PROXY);
}

const SESSION_TTL = 1000 * 60 * 60 * 24 * 7; // 7 días
const USERNAME_RE = /^[A-Za-z0-9_.-]{3,20}$/;
const MAX_MESSAGE_LENGTH = 2000;
const PAGE_SIZE = 100;
const DUMMY_HASH = bcrypt.hashSync('contraseña-inexistente', 10); // para que login tarde igual con usuarios que no existen

// Primera ejecución con SQLite: importa los usuarios de users.json si existe
if (store.countUsers() === 0) {
    const stats = importLegacyJson(store);
    if (stats) console.log(`Usuarios importados desde users.json: ${stats.users}`);
}
store.purgeExpiredSessions();

// ---- Cabeceras de seguridad
app.disable('x-powered-by');
app.use((req, res, next) => {
    res.set({
        'X-Content-Type-Options': 'nosniff',
        'X-Frame-Options': 'DENY',
        'Referrer-Policy': 'no-referrer',
        'Content-Security-Policy': [
            "default-src 'self'",
            "script-src 'self'",
            "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com",
            'font-src https://cdnjs.cloudflare.com',
            "img-src 'self' data:",
            "connect-src 'self' ws: wss:",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'"
        ].join('; ')
    });
    next();
});

app.use(express.json({ limit: '20kb' }));

// ---- Archivos del frontend: solo los necesarios (nunca la carpeta del servidor ni la base de datos)
const sendFile = file => (req, res) => res.sendFile(path.join(ROOT, file));
app.get(['/', '/index.html'], sendFile('index.html'));
app.get('/style.css', sendFile('style.css'));
for (const dir of ['js', 'img', 'Session']) {
    app.use('/' + dir, express.static(path.join(ROOT, dir)));
}

// ---- Límites de peticiones
const authLimiter = createLimiter({
    windowMs: 10 * 60 * 1000, max: 30,
    message: 'Demasiados intentos. Espera unos minutos e inténtalo de nuevo.'
});
const byUser = req => req.user;
const messageLimiter = createLimiter({ windowMs: 60 * 1000, max: 60, key: byUser, message: 'Estás enviando mensajes demasiado rápido' });
const searchLimiter = createLimiter({ windowMs: 60 * 1000, max: 60, key: byUser });

// ---- Registro y login
app.post('/register', authLimiter, (req, res) => {
    const { username, password } = req.body || {};

    if (typeof username !== 'string' || !USERNAME_RE.test(username)) {
        return res.status(400).json({ error: 'El usuario debe tener de 3 a 20 caracteres: letras, números, punto, guion o guion bajo' });
    }
    if (typeof password !== 'string' || password.length < 6 || Buffer.byteLength(password) > 72) {
        return res.status(400).json({ error: 'La contraseña debe tener entre 6 y 72 caracteres' });
    }
    if (store.getUser(username)) {
        return res.status(400).json({ error: 'El nombre de usuario ya existe' });
    }

    store.createUser(username, bcrypt.hashSync(password, 10));
    res.json({ message: 'Registro exitoso' });
});

app.post('/login', authLimiter, (req, res) => {
    const { username, password } = req.body || {};
    const user = typeof username === 'string' ? store.getUser(username) : undefined;
    const valid = typeof password === 'string' &&
        bcrypt.compareSync(password, user ? user.password_hash : DUMMY_HASH);

    if (user && valid) {
        const token = store.createSession(user.username, SESSION_TTL);
        res.json({ message: 'Inicio de sesión exitoso', token, username: user.username });
    } else {
        res.status(401).json({ error: 'Credenciales incorrectas' });
    }
});

// ---- Todo lo demás de /api exige sesión
function requireAuth(req, res, next) {
    const token = (req.headers.authorization || '').replace(/^Bearer /, '');
    const username = token ? store.getSessionUser(token) : null;
    if (!username) return res.status(401).json({ error: 'No autorizado' });
    req.user = username;
    req.token = token;
    next();
}
app.use('/api', requireAuth);

const realtime = () => app.locals.realtime;
const isOnline = username => realtime()?.isOnline(username) ?? false;
const withOnline = chat => chat && { ...chat, online: isOnline(chat.username) };

// Resuelve al otro usuario (por :username o por el cuerpo) con su nombre canónico
function resolvePeer(source) {
    return (req, res, next) => {
        const name = source === 'body' ? (req.body || {}).username : req.params.username;
        const peer = typeof name === 'string' ? store.getUser(name) : undefined;
        if (!peer) return res.status(404).json({ error: 'Usuario no encontrado' });
        if (peer.username.toLowerCase() === req.user.toLowerCase()) {
            return res.status(400).json({ error: 'No puedes chatear contigo mismo' });
        }
        req.peer = peer.username;
        next();
    };
}

app.get('/api/me', (req, res) => res.json({ username: req.user }));

app.post('/api/logout', (req, res) => {
    store.deleteSession(req.token);
    res.json({ ok: true });
});

// Buscar usuarios por prefijo (para iniciar un chat)
app.get('/api/users', searchLimiter, (req, res) => {
    const q = typeof req.query.q === 'string' ? req.query.q.trim().slice(0, 20) : '';
    if (q.length < 2) return res.json([]);
    res.json(store.searchUsers(q, req.user).map(username => ({ username, online: isOnline(username) })));
});

// ---- Chats
app.get('/api/chats', (req, res) => {
    res.json(store.listChats(req.user).map(withOnline));
});

// Agrega a un usuario a tu lista (o reabre un chat oculto)
app.post('/api/chats', resolvePeer('body'), (req, res) => {
    store.openChat(req.user, req.peer);
    res.json(withOnline(store.chatSummary(req.user, req.peer)));
});

app.patch('/api/chats/:username', resolvePeer('params'), (req, res) => {
    const { favorite } = req.body || {};
    if (typeof favorite !== 'boolean') return res.status(400).json({ error: 'favorite debe ser true o false' });
    store.setFavorite(req.user, req.peer, favorite);
    res.json(withOnline(store.chatSummary(req.user, req.peer)));
});

// Elimina el chat solo para ti
app.delete('/api/chats/:username', resolvePeer('params'), (req, res) => {
    store.hideChat(req.user, req.peer);
    res.json({ ok: true });
});

// Vacía el historial solo para ti
app.post('/api/chats/:username/clear', resolvePeer('params'), (req, res) => {
    store.clearChat(req.user, req.peer);
    res.json({ ok: true });
});

// ---- Mensajes
app.get('/api/chats/:username/messages', resolvePeer('params'), (req, res) => {
    const before = Number.parseInt(req.query.before, 10);
    const limit = Math.min(Number.parseInt(req.query.limit, 10) || PAGE_SIZE, PAGE_SIZE);
    res.json(store.getMessages(req.user, req.peer, {
        before: Number.isSafeInteger(before) && before > 0 ? before : undefined,
        limit: Math.max(limit, 1)
    }));
});

app.post('/api/chats/:username/messages', messageLimiter, resolvePeer('params'), (req, res) => {
    const text = typeof (req.body || {}).text === 'string' ? req.body.text.trim() : '';
    if (!text || text.length > MAX_MESSAGE_LENGTH) {
        return res.status(400).json({ error: `El mensaje debe tener entre 1 y ${MAX_MESSAGE_LENGTH} caracteres` });
    }
    const message = store.sendMessage(req.user, req.peer, text);
    const rt = realtime();
    if (rt) {
        rt.sendTo(req.peer, { type: 'message', message });
        rt.sendTo(req.user, { type: 'message', message }); // tus otras pestañas
    }
    res.json(message);
});

// Marca como leídos los mensajes de ese usuario
app.post('/api/chats/:username/read', resolvePeer('params'), (req, res) => {
    if (store.markRead(req.user, req.peer)) {
        realtime()?.sendTo(req.user, { type: 'read', peer: req.peer }); // tus otras pestañas
    }
    res.json({ ok: true });
});

app.use('/api', (req, res) => res.status(404).json({ error: 'Ruta no encontrada' }));

// JSON mal formado o demasiado grande: error limpio en JSON en vez de volcar el stack
app.use((err, req, res, next) => {
    if (err.type === 'entity.parse.failed') return res.status(400).json({ error: 'JSON inválido' });
    if (err.type === 'entity.too.large') return res.status(413).json({ error: 'Petición demasiado grande' });
    console.error(err);
    res.status(500).json({ error: 'Error interno del servidor' });
});

// Servidor HTTP con WebSocket (tiempo real) en /ws
function createServer() {
    const server = http.createServer(app);
    app.locals.realtime = attach(server);
    return server;
}

if (require.main === module) {
    createServer().listen(PORT, () => {
        console.log(`Servidor en funcionamiento en http://localhost:${PORT}`);
    });
}

module.exports = { app, createServer };
