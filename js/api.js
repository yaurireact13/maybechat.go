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
        endSession();
        throw new Error('Sesión expirada');
    }
    const data = await response.json().catch(() => null);
    if (!response.ok) throw new Error((data && data.error) || `Error ${response.status}`);
    return data;
}

export function endSession() {
    localStorage.removeItem('token');
    localStorage.removeItem('username');
    window.location.href = 'Session/login.html';
}
