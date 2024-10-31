document.addEventListener("DOMContentLoaded", () => {
    const chatList = document.getElementById('chat-list');

    // Ejemplo de contactos con indicador de no leído
    function loadContacts() {
        const contacts = [
            { id: 1, name: '+51 921 876 815 (Tú)', image: 'img/perfil1.jpg', status: 'Pan, pollo, pan', unread: false, unreadCount: 0 },
            { id: 2, name: 'Sheyla Rosa', image: 'img/perfil2.jpg', status: 'Hola, como estas?', unread: true, unreadCount: 2 },
            { id: 3, name: 'Juan Pedro', image: 'img/perfil3.jpg', status: 'Te elimino!', unread: false, unreadCount: 0 },
            { id: 4, name: 'Tote Cornelio', image: 'img/perfil4.jpg', status: 'Escribiendo...', unread: true, unreadCount: 5 },
            { id: 5, name: 'Juandavid Madeley', image: 'img/perfil5.jpg', status: '¿Qué haces?', unread: true, unreadCount: 1 }
        ];

        contacts.forEach(contact => {
            const contactElement = document.createElement('div');
            contactElement.classList.add('chat');
            if (contact.unread) contactElement.classList.add('unread'); // Añadir clase "unread" si es no leído
            contactElement.innerHTML = `
                <img src="${contact.image}" alt="Contact" class="contact-pic">
                <div class="chat-info">
                    <h2>${contact.name}</h2>
                    <p>${contact.status}</p>
                </div>
                ${contact.unread ? `<span class="unread-indicator">${contact.unreadCount}</span>` : ''}
                <div class="action-icons">
                    <i class="fas fa-trash-alt delete-contact"></i>
                    <i class="fas fa-pencil-alt edit-contact"></i>
                </div>
            `;
            chatList.appendChild(contactElement);
        });
    }

    loadContacts(); // Cargar contactos al inicio
});



document.addEventListener("DOMContentLoaded", () => {
    const filterButtons = document.querySelectorAll(".filter-button");
    const chatList = document.getElementById("chat-list");

    filterButtons.forEach(button => {
        button.addEventListener("click", () => {
            filterButtons.forEach(btn => btn.classList.remove("active"));
            button.classList.add("active");

            const filter = button.textContent.toLowerCase();
            filterContacts(filter);
        });
    });

    function filterContacts(filter) {
        const contacts = chatList.querySelectorAll(".chat");

        contacts.forEach(contact => {
            if (filter === "no leídos") {
                contact.style.display = contact.classList.contains("unread") ? "flex" : "none";
            } else if (filter === "todos") {
                contact.style.display = "flex";
            } else {
                contact.style.display = "none"; 
            }
        });
    }
});
