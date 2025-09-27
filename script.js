// Escuchar cuando el DOM esté completamente cargado
document.addEventListener("DOMContentLoaded", () => {
    // Variables principales
    const chatList = document.getElementById('chat-list');
    const chatBody = document.getElementById('chat-body');
    const inputField = document.getElementById('message-input');
    const sendButton = document.getElementById('send-button');
    const chatHeaderName = document.getElementById('chat-header-name');
    const chatHeaderPic = document.getElementById('chat-header-pic');
    const logoutButton = document.getElementById('logout-button');
    const optionsIcon = document.getElementById('options-icon');
    const pageTitle = document.getElementById('page-title');
    let responseSent = false;

    // Inicializar funcionalidades
    loadContacts();         // Cargar y renderizar contactos desde JSON
    setupChatListeners();    // Configuración de mensajes y chat
    setupContactManagement(); // Gestión de contactos (agregar, editar, eliminar)
    setupSearch();           // Búsqueda de contactos
    setupLogout();           // Cierre de sesión
    setupPageReload();       // Recarga de la página
    setupClearChat();        // Limpieza del chat
    setupFilterContacts();   // Filtro de contactos

    /* ======================== FUNCIONES ======================== */

    /* ==== Funciones de Carga y Gestión de Contactos ==== */

    // Cargar y renderizar contactos desde JSON
    function loadContacts() {
        fetch('contacts.json')
            .then(response => response.json())
            .then(contacts => {
                contacts.forEach(contact => {
                    const contactElement = document.createElement("div");
                    contactElement.classList.add("chat");
                    contactElement.dataset.contact = contact.id;
                    contactElement.innerHTML = `
                        <img src="${contact.image}" alt="Contact" class="contact-pic">
                        <div class="chat-info">
                            <h2>${contact.name}</h2>
                            <p>${contact.status}</p>
                        </div>
                        <div class="action-icons">
                            <i class="fas fa-trash-alt delete-contact"></i>
                            <i class="fas fa-pencil-alt edit-contact"></i>
                        </div>
                    `;
                    chatList.appendChild(contactElement);
                });
            })
            .catch(error => console.error("Error al cargar los contactos:", error));
    }

    // Configuración de la gestión de contactos
    function setupContactManagement() {
        const modal = document.getElementById('add-contact-modal');
        const addButton = document.getElementById('add-contact-button');
        const closeButton = document.querySelector('.modal .close-button');
        const form = document.getElementById('add-contact-form');

        // Abrir el modal
        addButton.addEventListener('click', () => {
            modal.style.display = 'block';
        });

        // Cerrar el modal con el botón 'X'
        closeButton.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        // Cerrar el modal al hacer clic fuera de él
        window.addEventListener('click', event => {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        });

        // Manejar el envío del formulario del modal
        form.addEventListener('submit', event => {
            event.preventDefault();
            const name = document.getElementById('contact-name').value;
            const phone = document.getElementById('contact-phone').value;
            let image = document.getElementById('contact-img').value;

            if (!image) {
                image = 'img/contactundefined.jpg'; // Imagen por defecto
            }

            addContact(name, phone, image);
            
            modal.style.display = 'none';
            form.reset();
        });

        document.addEventListener('click', event => {
            if (event.target.classList.contains('delete-contact')) {
                deleteContact(event.target.closest('.chat'));
            } else if (event.target.classList.contains('edit-contact')) {
                editContact(event.target.closest('.chat'));
            }
        });
    }

    // Agregar un nuevo contacto
    function addContact(name, phone, image) {
        const newContact = document.createElement('div');
        newContact.classList.add('chat');
        newContact.dataset.contact = chatList.children.length + 1;
        newContact.innerHTML = `
            <img src="${image}" alt="Contact" class="contact-pic">
            <div class="chat-info">
                <h2>${name}</h2>
                <p>${phone}</p>
            </div>
            <div class="action-icons">
                <i class="fas fa-trash-alt delete-contact"></i>
                <i class="fas fa-pencil-alt edit-contact"></i>
            </div>
        `;
        chatList.appendChild(newContact);
    }

    // Eliminar un contacto
    function deleteContact(contact) {
        contact.remove();
    }

    // Editar un contacto
    function editContact(contact) {
        const name = prompt('Ingrese el nuevo nombre del contacto:');
        const image = prompt('Ingrese la nueva URL de la imagen del contacto:');
        if (name) contact.querySelector('h2').textContent = name;
        if (image) contact.querySelector('.contact-pic').src = image;
    }

    /* ==== Funciones de Mensajes ==== */

    // Configuración de eventos de chat
    function setupChatListeners() {
        chatList.addEventListener('click', event => {
            const chat = event.target.closest('.chat');
            if (chat) {
                const contactName = chat.querySelector('.chat-info h2').textContent;
                const contactPicSrc = chat.querySelector('.contact-pic').src;
                chatHeaderPic.src = contactPicSrc;
                chatHeaderName.textContent = contactName;
                chatBody.innerHTML = '';
                simulateMessages(contactName);
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

    // Simulación de mensajes iniciales
    function simulateMessages(contactName) {
        const messages = [
            { sender: contactName, text: 'Hola, ¿cómo estás?' },
        ];
        messages.forEach(message => appendMessage(message.sender, message.text));
    }

    // Crear y añadir un mensaje al chat (versión con IA)
    async function appendMessage(sender, text) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message', sender === 'Tú' ? 'sent' : 'received');
        messageDiv.innerHTML = `
            <p>${text}</p>
            <span class="time">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
        `;
        chatBody.appendChild(messageDiv);
        chatBody.scrollTop = chatBody.scrollHeight;

        // Si el mensaje es del usuario, busca una respuesta de la IA
        if (sender === 'Tú' && !responseSent) {
            responseSent = true; // Bloquea para evitar respuestas múltiples
            showTypingStatus();
            try {
                const responseText = await getAIResponse(text);
                const typingStatus = document.getElementById('typing-status');
                if (typingStatus) typingStatus.remove();
                await appendMessage('Contacto', responseText);
            } catch (error) {
                console.error("Error al obtener respuesta de la IA:", error);
                const typingStatus = document.getElementById('typing-status');
                if (typingStatus) typingStatus.remove();
                await appendMessage('Contacto', 'Lo siento, no puedo responder en este momento.');
            }
        }
    }

    // Muestra el estado "Escribiendo..."
    function showTypingStatus() {
        const typingDiv = document.createElement('div');
        typingDiv.classList.add('message', 'received');
        typingDiv.id = 'typing-status';
        typingDiv.innerHTML = `<p><em>Escribiendo...</em></p>`;
        chatBody.appendChild(typingDiv);
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    // Nueva función para obtener respuesta de la IA desde el backend
    async function getAIResponse(message) {
        try {
            const response = await fetch('http://localhost:3000/api/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message: message })
            });

            if (!response.ok) {
                throw new Error('La respuesta de la red no fue correcta');
            }

            const data = await response.json();
            return data.reply;
        } catch (error) {
            console.error('Error en getAIResponse:', error);
            return 'Error al conectar con la IA.';
        }
    }

    // Enviar un mensaje
    function sendMessage() {
        const messageText = inputField.value.trim();
        if (messageText !== '') {
            appendMessage('Tú', messageText);
            responseSent = false; // Resetea para permitir la siguiente respuesta
            inputField.value = '';
        }
    }

    /* ==== Funciones de Interfaz y Utilidades ==== */

    // Búsqueda de contactos
    function setupSearch() {
        const searchInput = document.getElementById('search-input');
        searchInput.addEventListener('input', () => {
            const searchText = searchInput.value.trim().toLowerCase();
            chatList.querySelectorAll('.chat').forEach(chat => {
                const contactName = chat.querySelector('.chat-info h2').textContent.toLowerCase();
                chat.style.display = contactName.includes(searchText) ? 'block' : 'none';
            });
        });
    }

    // Configurar el cierre de sesión
    function setupLogout() {
        logoutButton.addEventListener('click', () => {
            localStorage.removeItem('username');
            localStorage.removeItem('password');
            window.location.href = 'Session/login.html';
        });
    }

// Mostrar spinner cuando se hace clic en "MaybeChat"
function setupPageReload() {
    const pageTitle = document.getElementById('page-title');
    const loadingSpinner = document.getElementById('loading-spinner');

    // Asegúrate de que el spinner esté oculto inicialmente
    loadingSpinner.style.display = 'none';

    pageTitle.addEventListener('click', () => {
        // Mostrar el spinner al hacer clic
        loadingSpinner.style.display = 'flex';

        // Ocultar el spinner después de 2 segundos para simular una carga
        setTimeout(() => {
            loadingSpinner.style.display = 'none';
        }, 2000);
    });
}

// Asegúrate de llamar a la función setupPageReload
document.addEventListener("DOMContentLoaded", () => {
    setupPageReload();
});



    // Limpieza del chat
    function setupClearChat() {
        const clearChatButton = document.getElementById('clear-chat-button');
        clearChatButton.addEventListener('click', () => {
            if (confirm('¿Estás seguro de que deseas borrar este chat?')) {
                chatBody.innerHTML = '';
            }
        });
    }

    // Configuración de filtros de contacto
    function setupFilterContacts() {
        const filterButtons = document.querySelectorAll(".filter-button");

        filterButtons.forEach(button => {
            button.addEventListener("click", () => {
                filterButtons.forEach(btn => btn.classList.remove("active"));
                button.classList.add("active");
                const filter = button.textContent.toLowerCase();
                filterContacts(filter);
            });
        });
    }

    // Aplicación de filtro de contactos
    function filterContacts(filter) {
        const contacts = chatList.querySelectorAll(".chat");
        contacts.forEach(contact => {
            contact.style.display = "flex";
            if (filter === "no leídos" && !contact.classList.contains("unread")) {
                contact.style.display = "none";
            } else if (filter === "favoritos" && !contact.classList.contains("favorite")) {
                contact.style.display = "none";
            } else if (filter === "grupos" && !contact.classList.contains("group")) {
                contact.style.display = "none";
            }
        });
    }
});


function appendMessage(sender, text) {
    const messageDiv = document.createElement('div');
    messageDiv.classList.add('message', sender === 'Tú' ? 'sent' : 'received');
    messageDiv.innerHTML = `
        <p>${text}</p>
        <span class="time">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
    `;
    chatBody.appendChild(messageDiv);
    chatBody.scrollTop = chatBody.scrollHeight;

    if (sender !== 'Tú') {
        // Agrega la clase de mensaje no leído al contacto en la lista
        const contactChat = document.querySelector(`[data-contact="${sender}"]`);
        if (contactChat) contactChat.classList.add('new-message');
    }
    
    if (sender === 'Tú' && !responseSent) {
        showTypingStatus();
        setTimeout(() => {
            const typingStatus = document.getElementById('typing-status');
            if (typingStatus) typingStatus.remove();
            const responseText = generateResponse();
            appendMessage('Contacto', responseText);
            responseSent = true;
        }, 2000);
    }
}
chatList.addEventListener('click', event => {
    const chat = event.target.closest('.chat');
    if (chat) {
        chat.classList.remove('new-message'); // Quita la notificación de nuevo mensaje
        const contactName = chat.querySelector('.chat-info h2').textContent;
        const contactPicSrc = chat.querySelector('.contact-pic').src;
        chatHeaderPic.src = contactPicSrc;
        chatHeaderName.textContent = contactName;
        chatBody.innerHTML = '';
        simulateMessages(contactName);
    }
});

