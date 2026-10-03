// js/state.js
// Estado compartido de la interfaz
export const state = {
    me: null,          // nombre del usuario conectado
    chats: [],         // [{ username, favorite, online, unreadCount, lastMessage }]
    active: null,      // username del chat abierto
    filter: 'todos',   // 'todos' | 'no leídos' | 'favoritos'
    search: ''
};

// Avatar estable para cada usuario (los usuarios no suben foto)
export function avatarFor(username) {
    let hash = 0;
    for (const c of username.toLowerCase()) hash = (hash * 31 + c.charCodeAt(0)) >>> 0;
    return `img/perfil${(hash % 10) + 1}.jpg`;
}

export const findChat = username =>
    state.chats.find(c => c.username.toLowerCase() === username.toLowerCase());

// Misma regla que el servidor: actividad reciente primero, luego por nombre
export function sortChats() {
    state.chats.sort((a, b) =>
        (b.lastMessage?.id ?? 0) - (a.lastMessage?.id ?? 0) || a.username.localeCompare(b.username));
}
