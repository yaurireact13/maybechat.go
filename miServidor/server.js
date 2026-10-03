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
app.use(express.json()); // Permite procesar datos en formato JSON

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

// Nueva ruta para el chat con IA usando Ollama
app.post('/api/chat', requireAuth, async (req, res) => {
    const { message } = req.body;

    if (!message) {
        return res.status(400).json({ error: 'El mensaje es requerido' });
    }

    try {
        // Conectarse a Ollama (asegúrate de que Ollama esté en ejecución)
        const ollama = new Ollama();
        
        // Usar el modelo 'llama2'
        await ollama.setModel('llama2');

        const response = await ollama.generate(message);

        if (response && response.output) {
            res.json({ reply: response.output });
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
