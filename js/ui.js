// js/ui.js
const chatList = document.getElementById('chat-list');
const chatBody = document.getElementById('chat-body');
const logoutButton = document.getElementById('logout-button');
const pageTitle = document.getElementById('page-title');
const loadingSpinner = document.getElementById('loading-spinner');
const searchInput = document.getElementById('search-input');
const filterButtons = document.querySelectorAll(".filter-button");
const clearChatButton = document.getElementById('clear-chat-button');

export function setupUI() {
    setupLogout();
    setupPageReload();
    setupSearch();
    setupFilterContacts();
    setupClearChat();
}

function setupLogout() {
    logoutButton.addEventListener('click', () => {
        localStorage.removeItem('username');
        localStorage.removeItem('password');
        window.location.href = 'Session/login.html';
    });
}

function setupPageReload() {
    loadingSpinner.style.display = 'none';
    pageTitle.addEventListener('click', () => {
        loadingSpinner.style.display = 'flex';
        setTimeout(() => {
            loadingSpinner.style.display = 'none';
        }, 2000);
    });
}

function setupSearch() {
    searchInput.addEventListener('input', () => {
        const searchText = searchInput.value.trim().toLowerCase();
        chatList.querySelectorAll('.chat').forEach(chat => {
            const contactName = chat.querySelector('.chat-info h2').textContent.toLowerCase();
            chat.style.display = contactName.includes(searchText) ? 'flex' : 'none';
        });
    });
}

function setupFilterContacts() {
    filterButtons.forEach(button => {
        button.addEventListener("click", () => {
            filterButtons.forEach(btn => btn.classList.remove("active"));
            button.classList.add("active");
            const filter = button.textContent.toLowerCase();
            filterContacts(filter);
        });
    });
}

function filterContacts(filter) {
    const contacts = chatList.querySelectorAll(".chat");
    contacts.forEach(contact => {
        let show = true;
        if (filter === "no leídos" && !contact.classList.contains("unread")) {
            show = false;
        } else if (filter === "favoritos" && !contact.classList.contains("favorite")) {
            show = false;
        } else if (filter === "grupos" && !contact.classList.contains("group")) {
            show = false;
        }
        contact.style.display = show ? "flex" : "none";
    });
}

function setupClearChat() {
    clearChatButton.addEventListener('click', () => {
        if (confirm('¿Estás seguro de que deseas borrar este chat?')) {
            chatBody.innerHTML = '';
        }
    });
}
