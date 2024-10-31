document.addEventListener("DOMContentLoaded", () => {
    const chatList = document.getElementById("chat-list");
    const filterButtons = document.querySelectorAll(".filter-button");

    // Cargar contactos
    function loadContacts() {
        const contacts = [
            { id: 1, name: "+51 921 876 815 (Tú)", image: "img/perfil1.jpg", status: "Pan, pollo, pan", unread: false, unreadCount: 0, favorite: true, group: false },
            { id: 2, name: "Sheyla Rosa", image: "img/perfil2.jpg", status: "Hola, como estas?", unread: true, unreadCount: 2, favorite: false, group: false },
            { id: 3, name: "Juan Pedro", image: "img/perfil3.jpg", status: "Te elimino!", unread: false, unreadCount: 0, favorite: true, group: false },
            { id: 4, name: "Tote Cornelio", image: "img/perfil4.jpg", status: "Escribiendo...", unread: true, unreadCount: 5, favorite: false, group: false },
            { id: 5, name: "Juandavid Madeley", image: "img/perfil5.jpg", status: "¿Qué haces?", unread: true, unreadCount: 1, favorite: true, group: false },
            { id: 6, name: "Grupo de Amigos", image: "img/grupo1.png", status: "Último mensaje del grupo", unread: false, unreadCount: 0, favorite: false, group: true },
            { id: 7, name: "Trabajo en equipo", image: "img/grupo2.png", status: "Conversación activa", unread: false, unreadCount: 0, favorite: false, group: true }
        ];
        

        chatList.innerHTML = ""; // Limpia la lista antes de agregar contactos

        contacts.forEach(contact => {
            const contactElement = document.createElement("div");
            contactElement.classList.add("chat");
            if (contact.unread) contactElement.classList.add("unread");
            if (contact.favorite) contactElement.classList.add("favorite");
            if (contact.group) contactElement.classList.add("group");

            contactElement.innerHTML = `
                <img src="${contact.image}" alt="Contact" class="contact-pic">
                <div class="chat-info">
                    <h2>${contact.name}</h2>
                    <p>${contact.status}</p>
                </div>
                ${contact.unread ? `<span class="unread-indicator">${contact.unreadCount}</span>` : ""}
                ${contact.favorite ? `<span class="favorite-indicator">★</span>` : ""}
                <div class="action-icons">
                    <i class="fas fa-trash-alt delete-contact"></i>
                    <i class="fas fa-pencil-alt edit-contact"></i>
                </div>
            `;
            chatList.appendChild(contactElement);
        });
    }

    // Filtros de contactos
    function filterContacts(filter) {
        const contacts = chatList.querySelectorAll(".chat");

        contacts.forEach(contact => {
            if (filter === "no leídos") {
                contact.style.display = contact.classList.contains("unread") ? "flex" : "none";
            } else if (filter === "favoritos") {
                contact.style.display = contact.classList.contains("favorite") ? "flex" : "none";
            } else if (filter === "grupos") {
                contact.style.display = contact.classList.contains("group") ? "flex" : "none";
            } else {
                contact.style.display = "flex";
            }
        });
    }

    // Event listener para botones de filtro
    filterButtons.forEach(button => {
        button.addEventListener("click", () => {
            filterButtons.forEach(btn => btn.classList.remove("active"));
            button.classList.add("active");
            const filter = button.textContent.toLowerCase();
            filterContacts(filter);
        });
    });

    loadContacts(); // Cargar contactos al iniciar
});
