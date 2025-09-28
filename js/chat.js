// js/chat.js
const chatBody = document.getElementById('chat-body');
const inputField = document.getElementById('message-input');
const sendButton = document.getElementById('send-button');
const chatHeaderName = document.getElementById('chat-header-name');
const chatHeaderPic = document.getElementById('chat-header-pic');
const chatList = document.getElementById('chat-list');

let responseSent = false;
let activeContact = null;

// 🔹 Historial de mensajes por contacto
const conversations = {};

export function setupChatListeners() {
    chatList.addEventListener('click', event => {
        const chat = event.target.closest('.chat');
        if (chat) {
            const contactName = chat.querySelector('.chat-info h2').textContent;
            const contactPicSrc = chat.querySelector('.contact-pic').src;

            activeContact = contactName;
            chatHeaderPic.src = contactPicSrc;
            chatHeaderName.textContent = contactName;

            // Mostrar historial si ya existe
            chatBody.innerHTML = '';
            if (conversations[contactName]) {
                conversations[contactName].forEach(msg =>
                    renderMessage(msg.sender, msg.text, msg.time)
                );
            } else {
                // Si no existe historial, lo creamos y mostramos saludo inicial
                conversations[contactName] = [];
                simulateMessages(contactName);
            }
        }
    });

    sendButton.addEventListener('click', sendMessage);
    inputField.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            sendMessage();
        }
    });
}

function simulateMessages(contactName) {
    const messages = [{ sender: contactName, text: 'Hola, ¿Puedes decirme algo nuevo?' }];
    messages.forEach(message => appendMessage(message.sender, message.text));
}

async function appendMessage(sender, text) {
    const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    // 🔹 Guardar en el historial del contacto activo
    if (activeContact) {
        conversations[activeContact].push({ sender, text, time });
    }

    // Renderizar en pantalla
    renderMessage(sender, text, time);

    if (sender === 'Tú' && !responseSent) {
        responseSent = true;
        showTypingStatus();
        try {
            const responseText = await getAIResponse(text);
            const typingStatus = document.getElementById('typing-status');
            if (typingStatus) typingStatus.remove();
            await appendMessage(activeContact, responseText); // IA responde como el contacto
        } catch (error) {
            console.error("Error al obtener respuesta de la IA:", error);
            const typingStatus = document.getElementById('typing-status');
            if (typingStatus) typingStatus.remove();
            await appendMessage(activeContact, 'Lo siento, no puedo responder en este momento.');
        }
    }
}

function renderMessage(sender, text, time) {
    const messageDiv = document.createElement('div');
    messageDiv.classList.add('message', sender === 'Tú' ? 'sent' : 'received');
    messageDiv.innerHTML = `
        <p>${text}</p>
        <span class="time">${time}</span>
    `;
    chatBody.appendChild(messageDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

function showTypingStatus() {
    const typingDiv = document.createElement('div');
    typingDiv.classList.add('message', 'received');
    typingDiv.id = 'typing-status';
    typingDiv.innerHTML = `<p><em>Escribiendo...</em></p>`;
    chatBody.appendChild(typingDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

async function getAIResponse(message) {
    try {
        const response = await fetch('http://localhost:3000/api/chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: message })
        });
        if (!response.ok) throw new Error('La respuesta de la red no fue correcta');
        const data = await response.json();
        return data.reply;
    } catch (error) {
        console.error('Error en getAIResponse:', error);
        return 'Error al conectar con la IA.';
    }
}

function sendMessage() {
    const messageText = inputField.value.trim();
    if (messageText !== '' && activeContact) {
        appendMessage('Tú', messageText);
        responseSent = false;
        inputField.value = '';
    }
}
