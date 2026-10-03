const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('fs');
const path = require('path');
const { startServer, contact } = require('./helpers');

let t, store, importLegacyJson, dir;
before(async () => {
    t = await startServer(); // deja listo DB_FILE temporal
    store = require('../db');
    ({ importLegacyJson } = require('../migrate-json'));
    dir = path.join(t.tmpDir, 'legacy');
    fs.mkdirSync(dir);
    fs.writeFileSync(path.join(dir, 'users.json'), JSON.stringify([
        { username: 'vieja', passwordHash: require('bcryptjs').hashSync('123456', 4) },
        { username: 'sin-datos', passwordHash: 'hash' },
        { username: 5, passwordHash: 'malo' }
    ]));
    fs.writeFileSync(path.join(dir, 'data.json'), JSON.stringify({
        vieja: {
            contacts: [contact('7'), contact('8')],
            conversations: {
                7: [{ sender: 'Tú', text: 'hola', time: '10:00' }, { sender: 'Pepe', text: 'qué tal', time: '10:01' }],
                99: [{ sender: 'x', text: 'huérfano', time: '1' }]
            }
        },
        fantasma: { contacts: [contact('1')], conversations: {} }
    }));
});
after(() => t.close());

test('importa usuarios, contactos y mensajes, omitiendo lo inválido', () => {
    const stats = importLegacyJson(store, dir);
    assert.deepEqual(stats, { users: 2, contacts: 2, messages: 2 });
    assert.deepEqual(store.getContacts('vieja').map(c => c.id), ['7', '8']);
    assert.deepEqual(store.getConversations('vieja'), {
        7: [{ sender: 'Tú', text: 'hola', time: '10:00' }, { sender: 'Pepe', text: 'qué tal', time: '10:01' }]
    });
    assert.equal(store.getUser('fantasma'), undefined, 'no crea usuarios que no estaban en users.json');
});

test('es idempotente: repetirla no duplica nada', () => {
    assert.deepEqual(importLegacyJson(store, dir), { users: 0, contacts: 0, messages: 0 });
    assert.equal(store.getConversations('vieja')['7'].length, 2);
});

test('el usuario migrado puede iniciar sesión con su contraseña anterior', async () => {
    const r = await t.request('POST', '/login', { body: { username: 'vieja', password: '123456' } });
    assert.equal(r.status, 200);
});

test('sin archivos JSON devuelve null', () => {
    assert.equal(importLegacyJson(store, path.join(t.tmpDir, 'no-existe')), null);
});
