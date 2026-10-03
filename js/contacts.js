// js/contacts.js
import { api } from './api.js';

const chatList = document.getElementById('chat-list');

// Lista de contactos en memoria; el servidor guarda la copia por usuario
let contacts = [];

function escapeHTML(value) {
    return String(value ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
}

function saveContacts() {
    api('/api/contacts', { method: 'PUT', body: contacts })
        .catch(error => console.error('Error al guardar los contactos:', error));
}

function renderContacts() {
    chatList.innerHTML = '';
    contacts.forEach(contact => {
        const contactElement = document.createElement('div');
        contactElement.classList.add('chat');
        if (contact.group) contactElement.classList.add('group');
        if (contact.favorite) contactElement.classList.add('favorite');
        if (contact.unread) contactElement.classList.add('unread');

        contactElement.dataset.contactId = contact.id;
        contactElement.innerHTML = `
            <img src="${escapeHTML(contact.image)}" alt="Contact" class="contact-pic">
            <div class="chat-info">
                <h2>${escapeHTML(contact.name)}</h2>
                <p>${escapeHTML(contact.status)}</p>
            </div>
            <div class="chat-status">
                ${contact.unread ? `<span class="unread-indicator">${escapeHTML(contact.unreadCount)}</span>` : ''}
                ${contact.favorite ? `<span class="favorite-indicator">★</span>` : ''}
            </div>
            <div class="action-icons">
                <i class="fas fa-trash-alt delete-contact"></i>
                <i class="fas fa-pencil-alt edit-contact"></i>
            </div>
        `;
        chatList.appendChild(contactElement);
    });
}

export async function loadContacts() {
    try {
        contacts = await api('/api/contacts');
        renderContacts();
    } catch (error) {
        console.error('Error al cargar los contactos:', error);
    }
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
        const name = document.getElementById('contact-name').value.trim();
        const phone = document.getElementById('contact-phone').value.trim();
        const image = document.getElementById('contact-img').value.trim() || 'img/contactundefined.jpg';
        if (!name) return;
        addContact(name, phone, image);
        modal.style.display = 'none';
        form.reset();
    });

    chatList.addEventListener('click', event => {
        const chat = event.target.closest('.chat');
        if (!chat) return;
        if (event.target.classList.contains('delete-contact')) {
            deleteContact(chat.dataset.contactId);
        } else if (event.target.classList.contains('edit-contact')) {
            editContact(chat.dataset.contactId);
        }
    });
}

function addContact(name, phone, image) {
    contacts.push({
        id: `new-${Date.now()}`,
        name,
        status: phone,
        image,
        group: false,
        favorite: false,
        unread: false,
        unreadCount: 0
    });
    renderContacts();
    saveContacts();
}

function deleteContact(id) {
    contacts = contacts.filter(c => c.id !== id);
    renderContacts();
    saveContacts();
}

function editContact(id) {
    const contact = contacts.find(c => c.id === id);
    if (!contact) return;
    const newName = prompt('Ingrese el nuevo nombre:', contact.name);
    const newImage = prompt('Ingrese la nueva URL de la imagen:', contact.image);
    if (newName && newName.trim()) contact.name = newName.trim();
    if (newImage && newImage.trim()) contact.image = newImage.trim();
    renderContacts();
    saveContacts();
}
