const express = require('express');
const cors = require('cors');
const app = express();
const PORT = 3000;

// Middleware
app.use(cors()); // Permite que el frontend acceda al backend
app.use(express.json()); // Permite procesar datos en formato JSON

// Ruta de registro
app.post('/register', (req, res) => {
    const { username, password } = req.body;
    // Aquí podrías agregar lógica para almacenar el usuario en una base de datos

    res.json({ message: 'Registro exitoso' });
});

// Ruta de login
app.post('/login', (req, res) => {
    const { username, password } = req.body;
    // Lógica para verificar las credenciales del usuario
    // Ejemplo de respuesta de éxito
    if (username === "testuser" && password === "123456") { 
        res.json({ message: 'Inicio de sesión exitoso' });
    } else {
        res.status(401).json({ error: 'Credenciales incorrectas' });
    }
});

app.listen(PORT, () => {
    console.log(`Servidor en funcionamiento en http://localhost:${PORT}`);
});
