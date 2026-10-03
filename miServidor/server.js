const express = require('express');
const cors = require('cors');
const path = require('path');
const fs = require('fs');
const crypto = require('crypto');
const bcrypt = require('bcryptjs');
const app = express();
const PORT = process.env.PORT || 3000;

// Configuración de Ollama (API HTTP oficial)
const OLLAMA_URL = (process.env.OLLAMA_URL || 'http://127.0.0.1:11434').replace(/\/+$/, '');
const OLLAMA_MODEL = process.env.OLLAMA_MODEL || 'llama2';
const OLLAMA_TIMEOUT_MS = Number(process.env.OLLAMA_TIMEOUT_MS) || 60000;

const store = require('./db');
const { importLegacyJson } = require('./migrate-json');

// Primera ejecución con SQLite: importa users.json / data.json si existen
if (store.countUsers() === 0) {
    const stats = importLegacyJson(store);
    if (stats) console.log(`Migrado desde JSON: ${stats.users} usuarios, ${stats.contacts} contactos, ${stats.messages} mensajes`);
}

// Sesiones en memoria: token -> { username, expires }
const sessions = new Map();
const SESSION_TTL = 1000 * 60 * 60 * 12;

function requireAuth(req, res, next) {
    const token = (req.headers.authorization || '').replace(/^Bearer /, '');
    const session = sessions.get(token);
    if (!session || session.expires < Date.now()) {
        sessions.delete(token);
        return res.status(401).json({ error: 'No autorizado' });
    }
    req.user = session.username;
    next();
}

// Middleware
app.use(cors()); // Permite que el frontend acceda al backend
app.use(express.json({ limit: '1mb' })); // Permite procesar datos en formato JSON

// Servir archivos estáticos desde el directorio raíz del proyecto
app.use(express.static(path.join(__dirname, '..')));

// Ruta raíz para redirigir a la página de registro
app.get('/', (req, res) => {
    res.redirect('/Session/register.html');
});

// Ruta de registro
app.post('/register', (req, res) => {
    const { username, password } = req.body;

    if (typeof username !== 'string' || typeof password !== 'string' || !username.trim() || password.length < 6) {
        return res.status(400).json({ error: 'Usuario requerido y contraseña de al menos 6 caracteres' });
    }

    // Verificar si el usuario ya existe
    if (store.getUser(username)) {
        return res.status(400).json({ error: 'El nombre de usuario ya existe' });
    }

    // Guardar el nuevo usuario con la contraseña hasheada
    store.createUser(username, bcrypt.hashSync(password, 10));

    res.json({ message: 'Registro exitoso' });
});

// Ruta de login
app.post('/login', (req, res) => {
    const { username, password } = req.body;
    
    // Buscar al usuario y verificar la contraseña
    const user = typeof username === 'string' ? store.getUser(username) : undefined;

    if (user && typeof password === 'string' && bcrypt.compareSync(password, user.password_hash)) {
        const token = crypto.randomBytes(32).toString('hex');
        sessions.set(token, { username, expires: Date.now() + SESSION_TTL });
        res.json({ message: 'Inicio de sesión exitoso', token });
    } else {
        res.status(401).json({ error: 'Credenciales incorrectas' });
    }
});

const MAX_CONTACTS = 500;
const isStr = (v, max) => typeof v === 'string' && v.length <= max;

function sanitizeContact(c) {
    if (!c || !isStr(c.id, 100) || !c.id || !isStr(c.name, 100) || !c.name.trim() ||
        !isStr(c.status ?? '', 300) || !isStr(c.image ?? '', 500)) return null;
    return {
        id: c.id,
        name: c.name,
        status: c.status ?? '',
        image: c.image || 'img/contactundefined.jpg',
        group: c.group === true,
        favorite: c.favorite === true,
        unread: c.unread === true,
        unreadCount: Number.isInteger(c.unreadCount) && c.unreadCount > 0 ? c.unreadCount : 0
    };
}

app.use('/api', requireAuth);

// Contactos: la primera vez se siembran desde contacts.json
app.get('/api/contacts', (req, res) => {
    if (!store.getUser(req.user).contacts_seeded) {
        let seed = [];
        try {
            const raw = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'contacts.json'), 'utf8'));
            seed = raw.map(c => sanitizeContact({ ...c, id: String(c.id) })).filter(Boolean);
        } catch (e) {
            seed = [];
        }
        store.setContacts(req.user, seed);
    }
    res.json(store.getContacts(req.user));
});

app.put('/api/contacts', (req, res) => {
    const list = req.body;
    if (!Array.isArray(list) || list.length > MAX_CONTACTS) {
        return res.status(400).json({ error: 'Lista de contactos inválida' });
    }
    const contacts = list.map(sanitizeContact);
    const ids = contacts.map(c => c && c.id);
    if (contacts.includes(null) || new Set(ids).size !== ids.length) {
        return res.status(400).json({ error: 'Contacto inválido o id duplicado' });
    }
    store.setContacts(req.user, contacts);
    res.json({ ok: true });
});

// Conversaciones por contacto
app.get('/api/conversations', (req, res) => {
    res.json(store.getConversations(req.user));
});

// Agrega un mensaje (se conservan los últimos 200 por chat)
app.post('/api/conversations/:contactId/messages', (req, res) => {
    const { sender, text, time } = req.body || {};
    if (!isStr(sender, 100) || !sender || !isStr(text, 10000) || !isStr(time, 30)) {
        return res.status(400).json({ error: 'Mensaje inválido' });
    }
    if (!store.hasContact(req.user, req.params.contactId)) {
        return res.status(404).json({ error: 'Contacto no encontrado' });
    }
    store.addMessage(req.user, req.params.contactId, { sender, text, time });
    res.json({ ok: true });
});

// Borra el historial de un chat
app.delete('/api/conversations/:contactId', (req, res) => {
    store.clearMessages(req.user, req.params.contactId);
    res.json({ ok: true });
});

// Ruta para el chat con IA usando Ollama
const MAX_MESSAGES = 20;
const MAX_CONTENT = 2000;

app.post('/api/chat', requireAuth, async (req, res) => {
    const { messages, contactName } = req.body;

    const valid = Array.isArray(messages) && messages.length > 0 &&
        messages.every(m => m && ['user', 'assistant'].includes(m.role) &&
            typeof m.content === 'string' && m.content.length <= MAX_CONTENT);
    if (!valid || messages[messages.length - 1].role !== 'user') {
        return res.status(400).json({ error: 'Se requiere un historial de mensajes válido que termine con un mensaje del usuario' });
    }

    const name = typeof contactName === 'string' ? contactName.slice(0, 50) : 'un amigo';
    const system = `Eres ${name}, un contacto en un chat de mensajería. Responde en español, de forma breve y natural, como en una conversación por chat.`;

    try {
        const response = await fetch(`${OLLAMA_URL}/api/chat`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                model: OLLAMA_MODEL,
                stream: false,
                messages: [{ role: 'system', content: system }, ...messages.slice(-MAX_MESSAGES)]
            }),
            signal: AbortSignal.timeout(OLLAMA_TIMEOUT_MS)
        });

        if (!response.ok) {
            const detail = await response.text().catch(() => '');
            throw new Error(`Ollama respondió ${response.status}: ${detail.slice(0, 200)}`);
        }

        const data = await response.json();
        const reply = data && data.message && typeof data.message.content === 'string' ? data.message.content.trim() : '';
        if (!reply) throw new Error('No se recibió una respuesta válida del modelo de IA.');
        res.json({ reply });
    } catch (error) {
        console.error('Error al conectar con Ollama:', error.message);
        res.status(502).json({ error: `No se pudo obtener una respuesta de la IA. Asegúrate de que Ollama esté en ejecución en ${OLLAMA_URL} y que el modelo "${OLLAMA_MODEL}" esté descargado (ollama pull ${OLLAMA_MODEL}).` });
    }
});

app.listen(PORT, () => {
    console.log(`Servidor en funcionamiento en http://localhost:${PORT}`);
});
