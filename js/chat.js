// js/chat.js
// Conversación abierta: mensajes, envío y eventos en tiempo real
import { api } from './api.js';
import { state, avatarFor, findChat } from './state.js';
import { renderChatList, upsertChat, loadChats } from './contacts.js';
import { showToast } from './toast.js';

const PAGE_SIZE = 100;

const chatBody = document.getElementById('chat-body');
const inputField = document.getElementById('message-input');
const sendButton = document.getElementById('send-button');
const clearButton = document.getElementById('clear-chat-button');
const headerName = document.getElementById('chat-header-name');
const headerStatus = document.getElementById('chat-header-status');
const headerPic = document.getElementById('chat-header-pic');

const same = (a, b) => a && b && a.toLowerCase() === b.toLowerCase();
const chatPath = username => `/api/chats/${encodeURIComponent(username)}`;
let sending = false;

function formatTime(timestamp) {
    const date = new Date(timestamp);
    const time = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    return date.toDateString() === new Date().toDateString()
        ? time
        : `${date.toLocaleDateString([], { day: '2-digit', month: '2-digit' })} ${time}`;
}

function renderMessage(message) {
    const div = document.createElement('div');
    div.classList.add('message', message.from === state.me ? 'sent' : 'received');
    div.dataset.id = message.id;
    const p = document.createElement('p');
    p.textContent = message.text;
    const time = document.createElement('span');
    time.className = 'time';
    time.textContent = formatTime(message.createdAt);
    div.append(p, time);
    return div;
}

const scrollToBottom = () => { chatBody.scrollTop = chatBody.scrollHeight; };
const hasMessage = id => !!chatBody.querySelector(`.message[data-id="${id}"]`);

function appendMessage(message) {
    if (hasMessage(message.id)) return;
    chatBody.querySelector('.empty-state')?.remove();
    chatBody.appendChild(renderMessage(message));
    scrollToBottom();
}

function showEmptyState(text) {
    const p = document.createElement('p');
    p.className = 'empty-state';
    p.textContent = text;
    chatBody.replaceChildren(p);
}

function renderHeader() {
    const chat = state.active && findChat(state.active);
    const open = !!state.active;
    headerPic.hidden = !open;
    clearButton.hidden = !open;
    inputField.disabled = !open;
    sendButton.disabled = !open;
    if (!open) {
        headerName.textContent = 'MaybeChat';
        headerStatus.textContent = 'Selecciona un chat';
        return;
    }
    headerName.textContent = state.active;
    headerPic.src = avatarFor(state.active);
    headerStatus.textContent = chat && chat.online ? 'En línea 🟢' : 'Desconectado';
}

// Botón para traer mensajes anteriores cuando hay más de una página
function showLoadMore(oldestId) {
    chatBody.querySelector('.load-more')?.remove();
    const button = document.createElement('button');
    button.className = 'load-more';
    button.type = 'button';
    button.textContent = 'Cargar mensajes anteriores';
    button.addEventListener('click', async () => {
        const username = state.active;
        try {
            const older = await api(`${chatPath(username)}/messages?before=${oldestId}`);
            if (!same(username, state.active)) return;
            const previousHeight = chatBody.scrollHeight;
            button.remove();
            chatBody.prepend(...older.map(renderMessage));
            if (older.length === PAGE_SIZE) showLoadMore(older[0].id);
            chatBody.scrollTop = chatBody.scrollHeight - previousHeight;
        } catch (error) {
            showToast(error.message);
        }
    });
    chatBody.prepend(button);
}

async function markRead(username) {
    const chat = findChat(username);
    if (!chat || chat.unreadCount === 0) return;
    chat.unreadCount = 0;
    renderChatList();
    try {
        await api(`${chatPath(username)}/read`, { method: 'POST' });
    } catch (error) {
        console.error('Error al marcar como leído:', error);
    }
}

async function loadMessages(username) {
    const messages = await api(`${chatPath(username)}/messages`);
    if (!same(username, state.active)) return; // el usuario ya abrió otro chat
    chatBody.replaceChildren();
    if (messages.length === 0) showEmptyState(`Empieza la conversación con ${username}`);
    messages.forEach(m => chatBody.appendChild(renderMessage(m)));
    if (messages.length === PAGE_SIZE) showLoadMore(messages[0].id);
    scrollToBottom();
}

async function openChat(username) {
    const chat = findChat(username);
    state.active = chat ? chat.username : username;
    renderHeader();
    renderChatList();
    chatBody.replaceChildren();
    try {
        await loadMessages(state.active);
        await markRead(state.active);
    } catch (error) {
        showToast(error.message);
    }
    inputField.focus();
}

function closeChat() {
    state.active = null;
    renderHeader();
    renderChatList();
    showEmptyState('Selecciona un chat o busca a alguien con el botón de arriba');
}

async function sendMessage() {
    const text = inputField.value.trim();
    if (!text || !state.active || sending) return;
    const peer = state.active;
    sending = true;
    sendButton.disabled = true;
    try {
        const message = await api(`${chatPath(peer)}/messages`, { method: 'POST', body: { text } });
        inputField.value = '';
        const chat = findChat(peer);
        if (chat) { chat.lastMessage = message; renderChatList(); }
        if (same(peer, state.active)) appendMessage(message);
    } catch (error) {
        showToast(error.message); // el texto se conserva para reintentar
    } finally {
        sending = false;
        sendButton.disabled = !state.active;
        inputField.focus();
    }
}

async function clearActiveChat() {
    const peer = state.active;
    if (!peer || !confirm(`¿Vaciar el chat con ${peer}? Solo se borra para ti.`)) return;
    try {
        await api(`${chatPath(peer)}/clear`, { method: 'POST' });
        const chat = findChat(peer);
        if (chat) { chat.lastMessage = null; chat.unreadCount = 0; renderChatList(); }
        showEmptyState(`Empieza la conversación con ${peer}`);
    } catch (error) {
        showToast(error.message);
    }
}

// Eventos del servidor (WebSocket)
export async function handleRealtimeEvent(event) {
    if (event.type === 'message') {
        const message = event.message;
        const peer = message.from === state.me ? message.to : message.from;
        const known = !!findChat(peer);
        if (!known) await loadChats(); // alguien nuevo te escribió: el servidor ya cuenta este mensaje
        const chat = findChat(peer);
        if (!chat) return;
        chat.lastMessage = message;

        const viewing = same(peer, state.active);
        if (viewing) appendMessage(message);
        if (message.from !== state.me) {
            if (viewing && !document.hidden) {
                renderChatList();
                markRead(chat.username);
                return;
            }
            if (known) chat.unreadCount++;
        }
        renderChatList();
    } else if (event.type === 'presence') {
        const chat = findChat(event.username);
        if (chat) {
            chat.online = event.online;
            if (same(event.username, state.active)) renderHeader();
        }
    } else if (event.type === 'read') {
        const chat = findChat(event.peer);
        if (chat && chat.unreadCount) { chat.unreadCount = 0; renderChatList(); }
    }
}

// Tras reconectar el WebSocket se recarga lo que pudo perderse
export async function resync() {
    await loadChats();
    if (state.active) {
        renderHeader();
        try { await loadMessages(state.active); await markRead(state.active); } catch (error) { console.error(error); }
    }
}

export function setupChat() {
    document.addEventListener('chat:select', event => openChat(event.detail));
    document.addEventListener('chat:closed', closeChat);
    // al volver a la pestaña, el chat abierto se marca como leído
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && state.active) markRead(state.active);
    });

    sendButton.addEventListener('click', sendMessage);
    clearButton.addEventListener('click', clearActiveChat);
    inputField.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            sendMessage();
        }
    });
    closeChat();
}
