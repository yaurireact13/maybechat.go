const registerForm = document.getElementById('register-form');

registerForm.addEventListener('submit', async event => {
    event.preventDefault();

    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value.trim();
    const confirmPassword = document.getElementById('confirm-password').value.trim();

    if (password !== confirmPassword) {
        alert('Las contraseñas no coinciden.');
        return;
    }

    try {
        const response = await fetch('http://localhost:3000/register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username, password })
        });

        const result = await response.json();
        if (response.ok) {
            alert('¡Registro exitoso! Ahora puedes iniciar sesión.');
            window.location.href = 'login.html';
        } else {
            alert(result.error || 'Error en el registro.');
        }
    } catch (error) {
        alert('Error de conexión. Inténtalo de nuevo.');
    }
});
