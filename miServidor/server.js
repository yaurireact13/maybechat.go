const { Ollama } = require('ollama-node');
const express = require('express');
const cors = require('cors');
const path = require('path');
const fs = require('fs');
const crypto = require('crypto');
const bcrypt = require('bcryptjs');
const app = express();
const PORT = 3000;

// Usuarios persistidos en un JSON (ignorado por git); contraseñas con hash bcrypt
const USERS_FILE = path.join(__dirname, 'users.json');
let users = [];
try {
    users = JSON.parse(fs.readFileSync(USERS_FILE, 'utf8'));
} catch (e) {
    users = [];
}
const saveUsers = () => fs.writeFileSync(USERS_FILE, JSON.stringify(users, null, 2));

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
    if (users.find(user => user.username === username)) {
        return res.status(400).json({ error: 'El nombre de usuario ya existe' });
    }

    // Guardar el nuevo usuario con la contraseña hasheada
    users.push({ username, passwordHash: bcrypt.hashSync(password, 10) });
    saveUsers();

    res.json({ message: 'Registro exitoso' });
});

// Ruta de login
app.post('/login', (req, res) => {
    const { username, password } = req.body;
    
    // Buscar al usuario y verificar la contraseña
    const user = users.find(u => u.username === username);

    if (user && typeof password === 'string' && bcrypt.compareSync(password, user.passwordHash)) {
        const token = crypto.randomBytes(32).toString('hex');
        sessions.set(token, { username, expires: Date.now() + SESSION_TTL });
        res.json({ message: 'Inicio de sesión exitoso', token });
    } else {
        res.status(401).json({ error: 'Credenciales incorrectas' });
    }
});

// Datos por usuario (contactos y conversaciones), persistidos en un JSON ignorado por git
const DATA_FILE = path.join(__dirname, 'data.json');
const userData = new Map(); // username -> { contacts: [] | null, conversations: { [contactId]: [] } }
try {
    for (const [name, value] of Object.entries(JSON.parse(fs.readFileSync(DATA_FILE, 'utf8')))) {
        userData.set(name, value);
    }
} catch (e) {
    // sin datos todavía
}
function saveData() {
    const tmp = DATA_FILE + '.tmp';
    fs.writeFileSync(tmp, JSON.stringify(Object.fromEntries(userData), null, 2));
    fs.renameSync(tmp, DATA_FILE);
}
function getUserData(username) {
    if (!userData.has(username)) userData.set(username, { contacts: null, conversations: {} });
    return userData.get(username);
}

const MAX_CONTACTS = 500;
const MAX_STORED_MESSAGES = 200;
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
    const data = getUserData(req.user);
    if (data.contacts === null) {
        try {
            const seed = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'contacts.json'), 'utf8'));
            data.contacts = seed.map(c => sanitizeContact({ ...c, id: String(c.id) })).filter(Boolean);
        } catch (e) {
            data.contacts = [];
        }
        saveData();
    }
    res.json(data.contacts);
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
    const data = getUserData(req.user);
    data.contacts = contacts;
    // Los chats de contactos eliminados se borran con ellos
    for (const id of Object.keys(data.conversations)) {
        if (!ids.includes(id)) delete data.conversations[id];
    }
    saveData();
    res.json({ ok: true });
});

// Conversaciones por contacto
app.get('/api/conversations', (req, res) => {
    res.json(getUserData(req.user).conversations);
});

app.put('/api/conversations/:contactId', (req, res) => {
    const data = getUserData(req.user);
    const { contactId } = req.params;
    const messages = req.body;
    const valid = Array.isArray(messages) && messages.length <= MAX_STORED_MESSAGES &&
        messages.every(m => m && isStr(m.sender, 100) && isStr(m.text, 10000) && isStr(m.time, 30));
    if (!valid) return res.status(400).json({ error: 'Conversación inválida' });
    if (!data.contacts || !data.contacts.some(c => c.id === contactId)) {
        return res.status(404).json({ error: 'Contacto no encontrado' });
    }
    data.conversations[contactId] = messages.map(m => ({ sender: m.sender, text: m.text, time: m.time }));
    saveData();
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
    const transcript = messages.slice(-MAX_MESSAGES)
        .map(m => `${m.role === 'user' ? 'Usuario' : name}: ${m.content}`)
        .join('\n');
    const prompt = `Eres ${name}, un contacto en un chat de mensajería. Responde en español, de forma breve y natural, como en una conversación por chat.\n\n${transcript}\n${name}:`;

    try {
        // Asegúrate de que Ollama esté en ejecución
        const ollama = new Ollama();
        await ollama.setModel('llama2');
        const response = await ollama.generate(prompt);

        if (response && response.output) {
            res.json({ reply: response.output.trim() });
        } else {
            throw new Error('No se recibió una respuesta válida del modelo de IA.');
        }
    } catch (error) {
        console.error('Error al conectar con Ollama:', error);
        res.status(500).json({ error: 'No se pudo obtener una respuesta de la IA. Asegúrate de que Ollama esté instalado y en ejecución.' });
    }
});

app.listen(PORT, () => {
    console.log(`Servidor en funcionamiento en http://localhost:${PORT}`);
});
