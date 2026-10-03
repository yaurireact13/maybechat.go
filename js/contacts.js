// js/contacts.js
// Lista de chats (barra lateral) y alta de nuevos chats con otros usuarios
import { api } from './api.js';
import { state, avatarFor, findChat, sortChats } from './state.js';
import { showToast } from './toast.js';

const chatList = document.getElementById('chat-list');

function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
}

function previewText(chat) {
    const last = chat.lastMessage;
    if (!last) return 'Sin mensajes todavía';
    return (last.from === state.me ? 'Tú: ' : '') + last.text;
}

function visible(chat) {
    if (state.filter === 'no leídos' && chat.unreadCount === 0) return false;
    if (state.filter === 'favoritos' && !chat.favorite) return false;
    return chat.username.toLowerCase().includes(state.search.toLowerCase());
}

export function renderChatList() {
    sortChats();
    chatList.replaceChildren();
    const chats = state.chats.filter(visible);

    if (chats.length === 0) {
        const empty = state.chats.length === 0
            ? 'Aún no tienes chats. Pulsa el botón de arriba para buscar a alguien.'
            : 'No hay chats que coincidan.';
        chatList.appendChild(el('p', 'list-empty', empty));
        return;
    }

    for (const chat of chats) {
        const item = el('div', 'chat');
        if (chat.unreadCount > 0) item.classList.add('unread');
        if (chat.favorite) item.classList.add('favorite');
        if (state.active && state.active.toLowerCase() === chat.username.toLowerCase()) item.classList.add('active');
        item.dataset.username = chat.username;

        const pic = el('img', 'contact-pic');
        pic.src = avatarFor(chat.username);
        pic.alt = '';

        const info = el('div', 'chat-info');
        info.append(el('h2', '', chat.username), el('p', '', previewText(chat)));

        const status = el('div', 'chat-status');
        if (chat.unreadCount > 0) status.appendChild(el('span', 'unread-indicator', String(chat.unreadCount)));
        if (chat.favorite) status.appendChild(el('span', 'favorite-indicator', '★'));

        const actions = el('div', 'action-icons');
        const star = el('i', `${chat.favorite ? 'fas' : 'far'} fa-star toggle-favorite`);
        star.title = chat.favorite ? 'Quitar de favoritos' : 'Añadir a favoritos';
        const trash = el('i', 'fas fa-trash-alt delete-contact');
        trash.title = 'Eliminar chat';
        actions.append(star, trash);

        item.append(pic, info, status, actions);
        chatList.appendChild(item);
    }
}

export async function loadChats() {
    try {
        state.chats = await api('/api/chats');
        renderChatList();
    } catch (error) {
        showToast(error.message);
    }
}

// Inserta o actualiza un chat en el estado y repinta la lista
export function upsertChat(chat) {
    const existing = findChat(chat.username);
    if (existing) Object.assign(existing, chat);
    else state.chats.push(chat);
    renderChatList();
}

export function setFilter(filter) {
    state.filter = filter;
    renderChatList();
}

export function setSearch(text) {
    state.search = text.trim();
    renderChatList();
}

// Abre el chat con ese usuario (lo crea si hace falta) y lo selecciona
async function startChat(username) {
    const chat = await api('/api/chats', { method: 'POST', body: { username } });
    upsertChat(chat);
    document.dispatchEvent(new CustomEvent('chat:select', { detail: chat.username }));
}

async function toggleFavorite(username) {
    const chat = findChat(username);
    if (!chat) return;
    try {
        upsertChat(await api(`/api/chats/${encodeURIComponent(username)}`, { method: 'PATCH', body: { favorite: !chat.favorite } }));
    } catch (error) {
        showToast(error.message);
    }
}

async function deleteChat(username) {
    if (!confirm(`¿Eliminar el chat con ${username}? Solo se borra para ti.`)) return;
    try {
        await api(`/api/chats/${encodeURIComponent(username)}`, { method: 'DELETE' });
        state.chats = state.chats.filter(c => c.username.toLowerCase() !== username.toLowerCase());
        if (state.active && state.active.toLowerCase() === username.toLowerCase()) {
            document.dispatchEvent(new CustomEvent('chat:closed'));
        }
        renderChatList();
    } catch (error) {
        showToast(error.message);
    }
}

export function setupContactManagement() {
    const modal = document.getElementById('add-contact-modal');
    const addButton = document.getElementById('add-contact-button');
    const closeButton = document.querySelector('.modal .close-button');
    const form = document.getElementById('add-contact-form');
    const input = document.getElementById('contact-name');
    const results = document.getElementById('user-results');
    const errorBox = document.getElementById('add-contact-error');
    let searchTimer = null;
    let searchSeq = 0;

    const closeModal = () => {
        modal.style.display = 'none';
        form.reset();
        results.replaceChildren();
        errorBox.textContent = '';
    };

    addButton.addEventListener('click', () => {
        modal.style.display = 'block';
        input.focus();
    });
    closeButton.addEventListener('click', closeModal);
    window.addEventListener('click', event => { if (event.target === modal) closeModal(); });

    async function begin(username) {
        errorBox.textContent = '';
        try {
            await startChat(username);
            closeModal();
        } catch (error) {
            errorBox.textContent = error.message;
        }
    }

    // Sugerencias mientras escribe
    input.addEventListener('input', () => {
        clearTimeout(searchTimer);
        errorBox.textContent = '';
        const q = input.value.trim();
        if (q.length < 2) { results.replaceChildren(); return; }
        searchTimer = setTimeout(async () => {
            const seq = ++searchSeq;
            try {
                const users = await api(`/api/users?q=${encodeURIComponent(q)}`);
                if (seq !== searchSeq) return; // llegó una búsqueda más nueva
                results.replaceChildren(...users.map(u => {
                    const li = el('li');
                    const button = el('button', 'user-result');
                    button.type = 'button';
                    button.dataset.username = u.username;
                    button.append(el('span', u.online ? 'dot online' : 'dot'), el('span', '', u.username));
                    li.appendChild(button);
                    return li;
                }));
                if (users.length === 0) results.appendChild(el('li', 'list-empty', 'No se encontró ningún usuario'));
            } catch (error) {
                errorBox.textContent = error.message;
            }
        }, 250);
    });

    results.addEventListener('click', event => {
        const button = event.target.closest('.user-result');
        if (button) begin(button.dataset.username);
    });

    form.addEventListener('submit', event => {
        event.preventDefault();
        const username = input.value.trim();
        if (username) begin(username);
    });

    chatList.addEventListener('click', event => {
        const item = event.target.closest('.chat');
        if (!item) return;
        const username = item.dataset.username;
        if (event.target.closest('.delete-contact')) deleteChat(username);
        else if (event.target.closest('.toggle-favorite')) toggleFavorite(username);
        else document.dispatchEvent(new CustomEvent('chat:select', { detail: username }));
    });
}
