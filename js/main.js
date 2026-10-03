// js/main.js
import { loadContacts, setupContactManagement } from './contacts.js';
import { setupChatListeners, loadConversations } from './chat.js';
import { setupUI } from './ui.js';

document.addEventListener("DOMContentLoaded", async () => {
    // Sin sesión, volver al login (el servidor igualmente valida el token)
    if (!localStorage.getItem('token')) {
        window.location.href = 'Session/login.html';
        return;
    }

    // Inicializar todos los módulos
    await Promise.all([loadContacts(), loadConversations()]);
    setupContactManagement();
    setupChatListeners();
    setupUI();
});
