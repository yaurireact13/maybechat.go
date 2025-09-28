// js/main.js
import { loadContacts, setupContactManagement } from './contacts.js';
import { setupChatListeners } from './chat.js';
import { setupUI } from './ui.js';

document.addEventListener("DOMContentLoaded", () => {
    // Inicializar todos los módulos
    loadContacts();
    setupContactManagement();
    setupChatListeners();
    setupUI();
});
