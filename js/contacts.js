// js/contacts.js
const chatList = document.getElementById('chat-list');

export function loadContacts() {
    fetch('contacts.json')
        .then(response => response.json())
        .then(contacts => {
            chatList.innerHTML = ''; // Limpiar lista antes de cargar
            contacts.forEach(contact => {
                const contactElement = document.createElement("div");
                contactElement.classList.add("chat");
                if (contact.group) contactElement.classList.add("group");
                if (contact.favorite) contactElement.classList.add("favorite");
                if (contact.unread) contactElement.classList.add("unread");

                contactElement.dataset.contactId = contact.id;
                contactElement.innerHTML = `
                    <img src="${contact.image}" alt="Contact" class="contact-pic">
                    <div class="chat-info">
                        <h2>${contact.name}</h2>
                        <p>${contact.status}</p>
                    </div>
                    <div class="chat-status">
                        ${contact.unread ? `<span class="unread-indicator">${contact.unreadCount}</span>` : ''}
                        ${contact.favorite ? `<span class="favorite-indicator">★</span>` : ''}
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

export function setupContactManagement() {
    const modal = document.getElementById('add-contact-modal');
    const addButton = document.getElementById('add-contact-button');
    const closeButton = document.querySelector('.modal .close-button');
    const form = document.getElementById('add-contact-form');

    addButton.addEventListener('click', () => modal.style.display = 'block');
    closeButton.addEventListener('click', () => modal.style.display = 'none');
    window.addEventListener('click', event => {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });

    form.addEventListener('submit', event => {
        event.preventDefault();
        const name = document.getElementById('contact-name').value;
        const phone = document.getElementById('contact-phone').value;
        let image = document.getElementById('contact-img').value || 'img/contactundefined.jpg';
        addContact(name, phone, image);
        modal.style.display = 'none';
        form.reset();
    });

    chatList.addEventListener('click', event => {
        if (event.target.classList.contains('delete-contact')) {
            deleteContact(event.target.closest('.chat'));
        } else if (event.target.classList.contains('edit-contact')) {
            editContact(event.target.closest('.chat'));
        }
    });
}

function addContact(name, phone, image) {
    const newContact = document.createElement('div');
    newContact.classList.add('chat');
    newContact.dataset.contactId = `new-${Date.now()}`;
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

function deleteContact(contactElement) {
    contactElement.remove();
}

function editContact(contactElement) {
    const nameElement = contactElement.querySelector('h2');
    const imageElement = contactElement.querySelector('.contact-pic');
    const newName = prompt('Ingrese el nuevo nombre:', nameElement.textContent);
    const newImage = prompt('Ingrese la nueva URL de la imagen:', imageElement.src);
    if (newName) nameElement.textContent = newName;
    if (newImage) imageElement.src = newImage;
}
