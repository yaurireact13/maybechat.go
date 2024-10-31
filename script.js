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
        document.getElementById('add-contact-button').addEventListener('click', () => {
            const name = prompt('Ingrese el nombre del nuevo contacto:');
            const image = prompt('Ingrese la URL de la imagen del nuevo contacto:');
            if (name && image) {
                addContact(name, image);
            } else {
                alert('Por favor, ingrese el nombre y la URL de la imagen del nuevo contacto.');
            }
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
    function addContact(name, image) {
        const newContact = document.createElement('div');
        newContact.classList.add('chat');
        newContact.dataset.contact = chatList.children.length + 1;
        newContact.innerHTML = `
            <img src="${image}" alt="Contact" class="contact-pic">
            <div class="chat-info">
                <h2>${name}</h2>
                <p>Último mensaje...</p>
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

    // Simulación de mensajes
    function simulateMessages(contactName) {
        const messages = [
            { sender: contactName, text: 'Hola, ¿cómo estás?' },
            { sender: 'Tú', text: `Hola, me llamo ${contactName}.` },
            { sender: contactName, text: '¿Qué tal tu día?' }
        ];

        messages.forEach(message => appendMessage(message.sender, message.text));
    }

    // Crear y añadir un mensaje al chat
    function appendMessage(sender, text) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message', sender === 'Tú' ? 'sent' : 'received');
        messageDiv.innerHTML = `
            <p>${text}</p>
            <span class="time">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
        `;
        chatBody.appendChild(messageDiv);
        chatBody.scrollTop = chatBody.scrollHeight;

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

    // Muestra el estado "Escribiendo..."
    function showTypingStatus() {
        const typingDiv = document.createElement('div');
        typingDiv.classList.add('message', 'received');
        typingDiv.innerHTML = `<p><em>Escribiendo...</em></p>`;
        typingDiv.id = 'typing-status';
        chatBody.appendChild(typingDiv);
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    // Genera una respuesta automática aleatoria
    function generateResponse() {
        const responses = [
            'Gracias por tu mensaje. Estoy ocupado en este momento. Te responderé pronto.',
            '¡Hola! ¿En qué puedo ayudarte?',
            'Estoy aquí para responder cualquier consulta.',
            'Déjame revisar eso y te doy una respuesta.',
            'Interesante, cuéntame más sobre eso.',
            'Voy a verificarlo, un momento por favor.'
        ];
        return responses[Math.floor(Math.random() * responses.length)];
    }

    // Enviar un mensaje
    function sendMessage() {
        const messageText = inputField.value.trim();
        if (messageText !== '') {
            appendMessage('Tú', messageText);
            responseSent = false;
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
        optionsIcon.addEventListener('click', () => {
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

