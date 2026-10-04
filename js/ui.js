// js/ui.js
import { api, endSession } from './api.js';
import { setFilter, setSearch } from './contacts.js';

const logoutButton = document.getElementById('logout-button');
const pageTitle = document.getElementById('page-title');
const loadingSpinner = document.getElementById('loading-spinner');
const searchInput = document.getElementById('search-input');
const filterButtons = document.querySelectorAll('.filter-button');

export function setupUI() {
    setupLogout();
    setupPageReload();
    setupSearch();
    setupFilterContacts();
}

function setupLogout() {
    logoutButton.addEventListener('click', async () => {
        try { await api('/api/logout', { method: 'POST' }); } catch (e) { /* aun así se cierra la sesión local */ }
        endSession();
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
    searchInput.addEventListener('input', () => setSearch(searchInput.value));
}

function setupFilterContacts() {
    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            setFilter(button.textContent.trim().toLowerCase());
        });
    });
}
