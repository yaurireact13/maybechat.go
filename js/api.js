// js/api.js
// Llamadas autenticadas al servidor. Si la sesión expiró, vuelve al login.
export async function api(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('token')}`
        },
        body: options.body === undefined ? undefined : JSON.stringify(options.body)
    });
    if (response.status === 401) {
        localStorage.removeItem('token');
        window.location.href = 'Session/login.html';
        throw new Error('Sesión expirada');
    }
    if (!response.ok) throw new Error(`Error ${response.status} en ${url}`);
    return response.json();
}
