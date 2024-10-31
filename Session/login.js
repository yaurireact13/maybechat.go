const loginForm = document.getElementById('login-form');

loginForm.addEventListener('submit', async event => {
    event.preventDefault();

    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value.trim();

    try {
        const response = await fetch('http://localhost:3000/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username, password })
        });

        const result = await response.json();
        if (response.ok) {
            alert('Inicio de sesión exitoso!');
            localStorage.setItem('username', username);
            window.location.href = '../index.html';
        } else {
            alert(result.error || 'Nombre de usuario o contraseña incorrectos.');
        }
    } catch (error) {
        alert('Error de conexión. Inténtalo de nuevo.');
    }
});
