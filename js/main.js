// js/main.js
import { api, endSession } from './api.js';
import { state } from './state.js';
import { loadChats, setupContactManagement } from './contacts.js';
import { setupChat, handleRealtimeEvent, resync } from './chat.js';
import { setupUI } from './ui.js';
import { connectRealtime } from './realtime.js';

document.addEventListener('DOMContentLoaded', async () => {
    // Sin sesión, volver al login (el servidor igualmente valida el token)
    if (!localStorage.getItem('token')) {
        window.location.href = 'Session/login.html';
        return;
    }

    try {
        state.me = (await api('/api/me')).username;
    } catch (error) {
        return endSession();
    }
    document.getElementById('me-label').textContent = `Conectado como ${state.me}`;

    setupUI();
    setupContactManagement();
    setupChat();
    await loadChats();
    connectRealtime({ onEvent: handleRealtimeEvent, onReconnect: resync });
});
