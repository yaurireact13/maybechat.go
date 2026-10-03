// Importa users.json y data.json (versión anterior) a SQLite. Es idempotente.
// Uso: npm run migrate   (también corre solo al primer arranque si la base está vacía)
const fs = require('fs');
const path = require('path');

function importLegacyJson(store, dir = __dirname) {
    const read = file => {
        try { return JSON.parse(fs.readFileSync(path.join(dir, file), 'utf8')); } catch (e) { return null; }
    };
    const users = read('users.json');
    const data = read('data.json');
    if (!users && !data) return null;

    const stats = { users: 0, contacts: 0, messages: 0 };
    store.db.transaction(() => {
        for (const u of users || []) {
            if (u && typeof u.username === 'string' && typeof u.passwordHash === 'string' && !store.getUser(u.username)) {
                store.createUser(u.username, u.passwordHash);
                stats.users++;
            }
        }
        for (const [username, d] of Object.entries(data || {})) {
            if (!store.getUser(username) || !d || !Array.isArray(d.contacts)) continue;
            if (store.getContacts(username).length === 0) {
                store.setContacts(username, d.contacts);
                stats.contacts += d.contacts.length;
            }
            for (const [contactId, msgs] of Object.entries(d.conversations || {})) {
                if (!store.hasContact(username, contactId) || store.getConversations(username)[contactId]) continue;
                for (const m of msgs) { store.addMessage(username, contactId, m); stats.messages++; }
            }
        }
    })();
    return stats;
}

module.exports = { importLegacyJson };

if (require.main === module) {
    const stats = importLegacyJson(require('./db'));
    console.log(stats ? `Migrado: ${stats.users} usuarios, ${stats.contacts} contactos, ${stats.messages} mensajes` : 'No hay users.json ni data.json que migrar');
}
