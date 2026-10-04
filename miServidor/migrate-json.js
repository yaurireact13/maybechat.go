// Importa los usuarios de users.json (versión anterior, con contraseñas ya hasheadas) a SQLite.
// Los contactos y mensajes de data.json no se importan: eran de un chat con IA y no tienen equivalente.
// Es idempotente. Uso: npm run migrate (también corre solo al primer arranque si la base está vacía)
const fs = require('fs');
const path = require('path');

function importLegacyJson(store, dir = __dirname) {
    let users;
    try {
        users = JSON.parse(fs.readFileSync(path.join(dir, 'users.json'), 'utf8'));
    } catch (e) {
        return null;
    }
    if (!Array.isArray(users)) return null;

    const stats = { users: 0 };
    store.db.transaction(() => {
        for (const u of users) {
            if (u && typeof u.username === 'string' && u.username.trim() &&
                typeof u.passwordHash === 'string' && !store.getUser(u.username)) {
                store.createUser(u.username, u.passwordHash);
                stats.users++;
            }
        }
    })();
    return stats;
}

module.exports = { importLegacyJson };

if (require.main === module) {
    const stats = importLegacyJson(require('./db'));
    console.log(stats ? `Migrado: ${stats.users} usuarios` : 'No hay users.json que migrar');
}
