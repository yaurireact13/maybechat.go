// SQLite: usuarios, sesiones, chats (por usuario) y mensajes entre usuarios
const path = require('path');
const crypto = require('crypto');
const Database = require('better-sqlite3');

const DB_FILE = process.env.DB_FILE || path.join(__dirname, 'maybechat.db');
const SCHEMA_VERSION = 2;

const db = new Database(DB_FILE);
db.pragma('journal_mode = WAL');
db.pragma('foreign_keys = ON');

function migrateSchema() {
    const version = db.pragma('user_version', { simple: true });
    if (version >= SCHEMA_VERSION) return;

    db.transaction(() => {
        // Versión 1 (chat con IA): los contactos y mensajes de ejemplo se descartan; los usuarios se conservan
        db.exec('DROP TABLE IF EXISTS messages; DROP TABLE IF EXISTS contacts;');
        const hasOldUsers = db.prepare("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'users'").get();
        if (hasOldUsers) db.exec('ALTER TABLE users RENAME TO users_old');

        db.exec(`
        CREATE TABLE users (
            username TEXT PRIMARY KEY COLLATE NOCASE,
            password_hash TEXT NOT NULL,
            created_at INTEGER NOT NULL DEFAULT 0
        );
        CREATE TABLE sessions (
            token_hash TEXT PRIMARY KEY,
            username TEXT NOT NULL COLLATE NOCASE REFERENCES users(username) ON DELETE CASCADE,
            expires_at INTEGER NOT NULL
        );
        -- Lista de chats de cada usuario. cleared_id: ese usuario no ve mensajes con id <= cleared_id
        CREATE TABLE contacts (
            owner TEXT NOT NULL COLLATE NOCASE REFERENCES users(username) ON DELETE CASCADE,
            peer TEXT NOT NULL COLLATE NOCASE REFERENCES users(username) ON DELETE CASCADE,
            favorite INTEGER NOT NULL DEFAULT 0,
            hidden INTEGER NOT NULL DEFAULT 0,
            cleared_id INTEGER NOT NULL DEFAULT 0,
            PRIMARY KEY (owner, peer)
        );
        CREATE TABLE messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sender TEXT NOT NULL COLLATE NOCASE REFERENCES users(username) ON DELETE CASCADE,
            recipient TEXT NOT NULL COLLATE NOCASE REFERENCES users(username) ON DELETE CASCADE,
            text TEXT NOT NULL,
            created_at INTEGER NOT NULL,
            read_at INTEGER
        );
        CREATE INDEX idx_messages_pair ON messages(sender, recipient, id);
        CREATE INDEX idx_messages_unread ON messages(recipient, sender, read_at);
        CREATE INDEX idx_sessions_expires ON sessions(expires_at);
        `);

        if (hasOldUsers) {
            db.exec('INSERT OR IGNORE INTO users (username, password_hash) SELECT username, password_hash FROM users_old');
            db.exec('DROP TABLE users_old');
        }
        db.pragma(`user_version = ${SCHEMA_VERSION}`);
    })();
}
migrateSchema();

const q = {
    getUser: db.prepare('SELECT username, password_hash FROM users WHERE username = ?'),
    insertUser: db.prepare('INSERT INTO users (username, password_hash, created_at) VALUES (?, ?, ?)'),
    countUsers: db.prepare('SELECT COUNT(*) AS n FROM users'),
    searchUsers: db.prepare(`
        SELECT username FROM users
        WHERE username LIKE ? ESCAPE '\\' AND username <> ?
        ORDER BY username LIMIT ?`),

    insertSession: db.prepare('INSERT INTO sessions (token_hash, username, expires_at) VALUES (?, ?, ?)'),
    getSession: db.prepare('SELECT username, expires_at FROM sessions WHERE token_hash = ?'),
    deleteSession: db.prepare('DELETE FROM sessions WHERE token_hash = ?'),
    purgeSessions: db.prepare('DELETE FROM sessions WHERE expires_at < ?'),

    showChat: db.prepare(`
        INSERT INTO contacts (owner, peer) VALUES (?, ?)
        ON CONFLICT(owner, peer) DO UPDATE SET hidden = 0`),
    getContact: db.prepare('SELECT favorite, hidden, cleared_id FROM contacts WHERE owner = ? AND peer = ?'),
    setFavorite: db.prepare('UPDATE contacts SET favorite = ? WHERE owner = ? AND peer = ?'),
    visibleContacts: db.prepare('SELECT peer, favorite, cleared_id FROM contacts WHERE owner = ? AND hidden = 0'),
    hideChat: db.prepare('UPDATE contacts SET hidden = 1, favorite = 0, cleared_id = ? WHERE owner = ? AND peer = ?'),
    clearChat: db.prepare('UPDATE contacts SET cleared_id = ? WHERE owner = ? AND peer = ?'),
    watchers: db.prepare('SELECT owner FROM contacts WHERE peer = ? AND hidden = 0'),

    maxMessageId: db.prepare('SELECT COALESCE(MAX(id), 0) AS id FROM messages'),
    insertMessage: db.prepare('INSERT INTO messages (sender, recipient, text, created_at) VALUES (?, ?, ?, ?)'),
    getMessage: db.prepare('SELECT * FROM messages WHERE id = ?'),
    lastMessage: db.prepare(`
        SELECT * FROM messages
        WHERE id > @after AND ((sender = @a AND recipient = @b) OR (sender = @b AND recipient = @a))
        ORDER BY id DESC LIMIT 1`),
    unreadCount: db.prepare(`
        SELECT COUNT(*) AS n FROM messages
        WHERE id > ? AND recipient = ? AND sender = ? AND read_at IS NULL`),
    messages: db.prepare(`
        SELECT * FROM messages
        WHERE id > @after AND id < @before AND ((sender = @a AND recipient = @b) OR (sender = @b AND recipient = @a))
        ORDER BY id DESC LIMIT @limit`),
    markRead: db.prepare(`
        UPDATE messages SET read_at = ?
        WHERE recipient = ? AND sender = ? AND read_at IS NULL AND id > ?`)
};

const toMessage = r => ({
    id: r.id, from: r.sender, to: r.recipient, text: r.text, createdAt: r.created_at, read: r.read_at !== null
});
const hashToken = token => crypto.createHash('sha256').update(String(token)).digest('hex');
const escapeLike = s => s.replace(/[\\%_]/g, c => '\\' + c);

module.exports = {
    db,

    // ---- usuarios (los nombres no distinguen mayúsculas; getUser devuelve el nombre canónico)
    getUser: username => q.getUser.get(username),
    countUsers: () => q.countUsers.get().n,
    createUser: (username, passwordHash) => q.insertUser.run(username, passwordHash, Date.now()),
    searchUsers: (prefix, exclude, limit = 10) =>
        q.searchUsers.all(escapeLike(prefix) + '%', exclude, limit).map(r => r.username),

    // ---- sesiones (se guarda el hash del token, nunca el token)
    createSession(username, ttlMs) {
        const token = crypto.randomBytes(32).toString('hex');
        q.insertSession.run(hashToken(token), username, Date.now() + ttlMs);
        return token;
    },
    getSessionUser(token) {
        const hash = hashToken(token);
        const s = q.getSession.get(hash);
        if (!s) return null;
        if (s.expires_at < Date.now()) {
            q.deleteSession.run(hash);
            return null;
        }
        return s.username;
    },
    deleteSession: token => q.deleteSession.run(hashToken(token)),
    purgeExpiredSessions: () => q.purgeSessions.run(Date.now()),

    // ---- chats
    // Muestra (o crea) el chat de owner con peer
    openChat: (owner, peer) => q.showChat.run(owner, peer),

    setFavorite(owner, peer, favorite) {
        q.showChat.run(owner, peer);
        q.setFavorite.run(favorite ? 1 : 0, owner, peer);
    },

    // "Eliminar chat": se oculta y owner deja de ver el historial; el otro usuario no se entera
    hideChat: db.transaction((owner, peer) => {
        q.hideChat.run(q.maxMessageId.get().id, owner, peer);
    }),

    // "Borrar chat": se vacía el historial para owner, el chat sigue en su lista
    clearChat: db.transaction((owner, peer) => {
        q.showChat.run(owner, peer);
        q.clearChat.run(q.maxMessageId.get().id, owner, peer);
    }),

    // Usuarios que tienen a `username` en su lista (para avisarles de su presencia)
    getWatchers: username => q.watchers.all(username).map(r => r.owner),

    chatSummary(owner, peer) {
        const c = q.getContact.get(owner, peer);
        if (!c || c.hidden) return null;
        const last = q.lastMessage.get({ after: c.cleared_id, a: owner, b: peer });
        return {
            username: peer,
            favorite: !!c.favorite,
            lastMessage: last ? toMessage(last) : null,
            unreadCount: q.unreadCount.get(c.cleared_id, owner, peer).n
        };
    },

    // Chats visibles, ordenados por actividad reciente (los vacíos al final por nombre)
    listChats(owner) {
        return q.visibleContacts.all(owner).map(c => {
            const last = q.lastMessage.get({ after: c.cleared_id, a: owner, b: c.peer });
            return {
                username: c.peer,
                favorite: !!c.favorite,
                lastMessage: last ? toMessage(last) : null,
                unreadCount: q.unreadCount.get(c.cleared_id, owner, c.peer).n
            };
        }).sort((x, y) =>
            (y.lastMessage?.id ?? 0) - (x.lastMessage?.id ?? 0) || x.username.localeCompare(y.username));
    },

    // ---- mensajes
    sendMessage: db.transaction((sender, recipient, text) => {
        const { lastInsertRowid } = q.insertMessage.run(sender, recipient, text, Date.now());
        // el mensaje hace visible el chat para ambos
        q.showChat.run(sender, recipient);
        q.showChat.run(recipient, sender);
        return toMessage(q.getMessage.get(lastInsertRowid));
    }),

    // Últimos `limit` mensajes (en orden cronológico); `before` pagina hacia atrás
    getMessages(owner, peer, { before = Number.MAX_SAFE_INTEGER, limit = 100 } = {}) {
        const c = q.getContact.get(owner, peer);
        const after = c ? c.cleared_id : 0;
        return q.messages.all({ after, before, limit, a: owner, b: peer }).reverse().map(toMessage);
    },

    // Marca como leídos los mensajes de `peer` hacia `owner`; devuelve cuántos cambió
    markRead(owner, peer) {
        const c = q.getContact.get(owner, peer);
        return q.markRead.run(Date.now(), owner, peer, c ? c.cleared_id : 0).changes;
    }
};
