const { Ollama } = require('ollama-node');
const express = require('express');
const cors = require('cors');
const path = require('path');
const app = express();
const PORT = 3000;

// "Base de datos" en memoria para almacenar usuarios
let users = [];

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

    // Verificar si el usuario ya existe
    if (users.find(user => user.username === username)) {
        return res.status(400).json({ error: 'El nombre de usuario ya existe' });
    }

    // Guardar el nuevo usuario
    const newUser = { username, password };
    users.push(newUser);
    console.log('Usuarios registrados:', users); // Para depuración

    res.json({ message: 'Registro exitoso' });
});

// Ruta de login
app.post('/login', (req, res) => {
    const { username, password } = req.body;
    
    // Buscar al usuario y verificar la contraseña
    const user = users.find(u => u.username === username && u.password === password);

    if (user) { 
        res.json({ message: 'Inicio de sesión exitoso' });
    } else {
        res.status(401).json({ error: 'Credenciales incorrectas' });
    }
});

// Nueva ruta para el chat con IA usando Ollama
app.post('/api/chat', async (req, res) => {
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
