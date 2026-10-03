// js/chat.js
import { api } from './api.js';

const chatBody = document.getElementById('chat-body');
const inputField = document.getElementById('message-input');
const sendButton = document.getElementById('send-button');
const chatHeaderName = document.getElementById('chat-header-name');
const chatHeaderPic = document.getElementById('chat-header-pic');
const chatList = document.getElementById('chat-list');

const MAX_HISTORY = 20;

// Contacto activo y historial de mensajes por contactId
let activeContact = null; // { id, name }
const conversations = {};
// Contactos con una respuesta de la IA en curso
const pending = new Set();

export function setupChatListeners() {
    chatList.addEventListener('click', event => {
        if (event.target.closest('.action-icons')) return;
        const chat = event.target.closest('.chat');
        if (!chat) return;

        const name = chat.querySelector('.chat-info h2').textContent;
        activeContact = { id: chat.dataset.contactId, name };
        chatHeaderPic.src = chat.querySelector('.contact-pic').src;
        chatHeaderName.textContent = name;

        const history = conversations[activeContact.id] ??= [];
        chatBody.innerHTML = '';
        if (history.length === 0) {
            addMessage(activeContact.id, name, 'Hola, ¿Puedes decirme algo nuevo?');
        } else {
            history.forEach(msg => renderMessage(msg.sender, msg.text, msg.time));
        }
        if (pending.has(activeContact.id)) showTypingStatus();
    });

    sendButton.addEventListener('click', sendMessage);
    inputField.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            sendMessage();
        }
    });
}

export async function loadConversations() {
    try {
        Object.assign(conversations, await api('/api/conversations'));
    } catch (error) {
        console.error('Error al cargar las conversaciones:', error);
    }
}

function saveMessage(contactId, message) {
    api(`/api/conversations/${encodeURIComponent(contactId)}/messages`, { method: 'POST', body: message })
        .catch(error => console.error('Error al guardar el mensaje:', error));
}

export function clearActiveChat() {
    if (!activeContact) return;
    conversations[activeContact.id] = [];
    api(`/api/conversations/${encodeURIComponent(activeContact.id)}`, { method: 'DELETE' })
        .catch(error => console.error('Error al borrar la conversación:', error));
}

// Guarda el mensaje en el historial del contacto y lo pinta si ese chat está abierto
function addMessage(contactId, sender, text) {
    const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const message = { sender, text, time };
    (conversations[contactId] ??= []).push(message);
    if (activeContact?.id === contactId) renderMessage(sender, text, time);
    saveMessage(contactId, message);
}

function renderMessage(sender, text, time) {
    const messageDiv = document.createElement('div');
    messageDiv.classList.add('message', sender === 'Tú' ? 'sent' : 'received');
    const p = document.createElement('p');
    p.textContent = text;
    const timeSpan = document.createElement('span');
    timeSpan.classList.add('time');
    timeSpan.textContent = time;
    messageDiv.append(p, timeSpan);
    chatBody.appendChild(messageDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

function showTypingStatus() {
    hideTypingStatus();
    const typingDiv = document.createElement('div');
    typingDiv.classList.add('message', 'received');
    typingDiv.id = 'typing-status';
    const p = document.createElement('p');
    const em = document.createElement('em');
    em.textContent = 'Escribiendo...';
    p.appendChild(em);
    typingDiv.appendChild(p);
    chatBody.appendChild(typingDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

function hideTypingStatus() {
    document.getElementById('typing-status')?.remove();
}

// Historial en el formato que espera el servidor
function buildPayload(contact) {
    return (conversations[contact.id] || []).slice(-MAX_HISTORY).map(msg => ({
        role: msg.sender === 'Tú' ? 'user' : 'assistant',
        content: msg.text
    }));
}

async function getAIResponse(contact) {
    const data = await api('/api/chat', {
        method: 'POST',
        body: { contactName: contact.name, messages: buildPayload(contact) }
    });
    return data.reply;
}

async function sendMessage() {
    const text = inputField.value.trim();
    if (!text || !activeContact) return;

    const contact = activeContact; // el usuario puede cambiar de chat mientras espera
    if (pending.has(contact.id)) return; // una respuesta a la vez por contacto

    inputField.value = '';
    addMessage(contact.id, 'Tú', text);

    pending.add(contact.id);
    showTypingStatus();
    let reply;
    try {
        reply = await getAIResponse(contact);
    } catch (error) {
        console.error('Error al obtener respuesta de la IA:', error);
        reply = 'Lo siento, no puedo responder en este momento.';
    }
    pending.delete(contact.id);
    if (activeContact?.id === contact.id) hideTypingStatus();
    addMessage(contact.id, contact.name, reply);
}
