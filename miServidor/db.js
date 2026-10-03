// SQLite: usuarios, contactos y mensajes
const path = require('path');
const Database = require('better-sqlite3');

const DB_FILE = process.env.DB_FILE || path.join(__dirname, 'maybechat.db');
const MAX_STORED_MESSAGES = 200;

const db = new Database(DB_FILE);
db.pragma('journal_mode = WAL');
db.pragma('foreign_keys = ON');

db.exec(`
CREATE TABLE IF NOT EXISTS users (
    username TEXT PRIMARY KEY,
    password_hash TEXT NOT NULL,
    contacts_seeded INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS contacts (
    username TEXT NOT NULL REFERENCES users(username) ON DELETE CASCADE,
    id TEXT NOT NULL,
    name TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT '',
    image TEXT NOT NULL,
    is_group INTEGER NOT NULL DEFAULT 0,
    favorite INTEGER NOT NULL DEFAULT 0,
    unread INTEGER NOT NULL DEFAULT 0,
    unread_count INTEGER NOT NULL DEFAULT 0,
    position INTEGER NOT NULL,
    PRIMARY KEY (username, id)
);
CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL,
    contact_id TEXT NOT NULL,
    sender TEXT NOT NULL,
    text TEXT NOT NULL,
    time TEXT NOT NULL,
    FOREIGN KEY (username, contact_id) REFERENCES contacts(username, id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_messages_chat ON messages(username, contact_id, id);
`);

const q = {
    getUser: db.prepare('SELECT username, password_hash, contacts_seeded FROM users WHERE username = ?'),
    insertUser: db.prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)'),
    countUsers: db.prepare('SELECT COUNT(*) AS n FROM users'),
    markSeeded: db.prepare('UPDATE users SET contacts_seeded = 1 WHERE username = ?'),
    listContacts: db.prepare('SELECT * FROM contacts WHERE username = ? ORDER BY position'),
    upsertContact: db.prepare(`
        INSERT INTO contacts (username, id, name, status, image, is_group, favorite, unread, unread_count, position)
        VALUES (@username, @id, @name, @status, @image, @group, @favorite, @unread, @unreadCount, @position)
        ON CONFLICT(username, id) DO UPDATE SET
            name = excluded.name, status = excluded.status, image = excluded.image,
            is_group = excluded.is_group, favorite = excluded.favorite, unread = excluded.unread,
            unread_count = excluded.unread_count, position = excluded.position`),
    deleteContact: db.prepare('DELETE FROM contacts WHERE username = ? AND id = ?'),
    hasContact: db.prepare('SELECT 1 FROM contacts WHERE username = ? AND id = ?'),
    listMessages: db.prepare('SELECT contact_id, sender, text, time FROM messages WHERE username = ? ORDER BY id'),
    insertMessage: db.prepare('INSERT INTO messages (username, contact_id, sender, text, time) VALUES (?, ?, ?, ?, ?)'),
    trimMessages: db.prepare(`
        DELETE FROM messages WHERE username = ? AND contact_id = ? AND id NOT IN (
            SELECT id FROM messages WHERE username = ? AND contact_id = ? ORDER BY id DESC LIMIT ?)`),
    clearMessages: db.prepare('DELETE FROM messages WHERE username = ? AND contact_id = ?')
};

const toContact = r => ({
    id: r.id, name: r.name, status: r.status, image: r.image,
    group: !!r.is_group, favorite: !!r.favorite, unread: !!r.unread, unreadCount: r.unread_count
});

module.exports = {
    db,
    MAX_STORED_MESSAGES,
    getUser: username => q.getUser.get(username),
    countUsers: () => q.countUsers.get().n,
    createUser: (username, passwordHash) => q.insertUser.run(username, passwordHash),

    getContacts: username => q.listContacts.all(username).map(toContact),

    // Reemplaza la lista completa; los contactos que ya no están se borran con sus mensajes
    setContacts: db.transaction((username, contacts) => {
        const keep = new Set(contacts.map(c => c.id));
        for (const row of q.listContacts.all(username)) {
            if (!keep.has(row.id)) q.deleteContact.run(username, row.id);
        }
        contacts.forEach((c, position) => q.upsertContact.run({
            username, id: c.id, name: c.name, status: c.status, image: c.image,
            group: +c.group, favorite: +c.favorite, unread: +c.unread, unreadCount: c.unreadCount, position
        }));
        q.markSeeded.run(username);
    }),

    hasContact: (username, id) => !!q.hasContact.get(username, id),

    getConversations(username) {
        const out = {};
        for (const m of q.listMessages.all(username)) {
            (out[m.contact_id] ??= []).push({ sender: m.sender, text: m.text, time: m.time });
        }
        return out;
    },

    addMessage: db.transaction((username, contactId, { sender, text, time }) => {
        q.insertMessage.run(username, contactId, sender, text, time);
        q.trimMessages.run(username, contactId, username, contactId, MAX_STORED_MESSAGES);
    }),

    clearMessages: (username, contactId) => q.clearMessages.run(username, contactId)
};
