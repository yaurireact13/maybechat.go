// js/toast.js
// Aviso breve en pantalla
export function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.setAttribute('role', 'alert');
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
}
