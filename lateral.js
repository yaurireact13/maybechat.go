document.addEventListener("DOMContentLoaded", () => {
    const chatList = document.getElementById('chat-list');
    const contactSidebar = document.getElementById('contact-details-sidebar');
    const contactInfoPic = document.getElementById('details-contact-pic');
    const contactInfoName = document.getElementById('details-contact-name');
    const contactInfoStatus = document.querySelector('.details-contact-status');

    let selectedChat = null; // Variable para almacenar el chat seleccionado

    function loadContacts() {
        const contacts = [
            { id: 1, name: "+51 921 876 815 (Tú)", image: "img/perfil1.jpg", status: "Pan, pollo, pan" },
            { id: 2, name: "Sheyla Rosa", image: "img/perfil2.jpg", status: "Hola, como estas?" },
        ];

        contacts.forEach(contact => {
            const contactElement = document.createElement('div');
            contactElement.classList.add('chat');
            contactElement.innerHTML = `
                <img src="${contact.image}" alt="Contact" class="contact-pic" onerror="this.src='img/default.jpg'">
                <div class="chat-info">
                    <h2>${contact.name}</h2>
                    <p>${contact.status}</p>
                </div>
            `;
            chatList.appendChild(contactElement);

            // Asignar evento click a cada contacto individualmente
            contactElement.addEventListener('click', () => {
                openContactDetails(contact, contactElement);
            });
        });
    }

    function openContactDetails(contact, element) {
        // Remover la clase 'selected' del chat previamente seleccionado
        if (selectedChat) {
            selectedChat.classList.remove('selected');
        }
        // Agregar la clase 'selected' al nuevo chat seleccionado
        element.classList.add('selected');
        selectedChat = element;

        // Mostrar el panel de detalles y actualizar la información
        contactSidebar.classList.add('active');
        contactInfoPic.src = contact.image;
        contactInfoName.textContent = contact.name;
        contactInfoStatus.textContent = contact.status;
    }

    window.closeContactDetails = function () {
        contactSidebar.classList.remove('active');
        if (selectedChat) selectedChat.classList.remove('selected'); // Quita el resaltado al cerrar
    };

    loadContacts();
});
