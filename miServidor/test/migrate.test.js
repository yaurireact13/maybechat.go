const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('fs');
const path = require('path');
const { startServer } = require('./helpers');

let t, store, importLegacyJson, dir;
before(async () => {
    t = await startServer(); // deja listo DB_FILE temporal
    store = require('../db');
    ({ importLegacyJson } = require('../migrate-json'));
    dir = path.join(t.tmpDir, 'legacy');
    fs.mkdirSync(dir);
    fs.writeFileSync(path.join(dir, 'users.json'), JSON.stringify([
        { username: 'vieja', passwordHash: require('bcryptjs').hashSync('123456', 4) },
        { username: 'VIEJA', passwordHash: 'duplicado-por-mayusculas' },
        { username: 'sin-hash' },
        { username: 5, passwordHash: 'malo' }
    ]));
});
after(() => t.close());

test('importa los usuarios válidos y omite duplicados y datos inválidos', () => {
    assert.deepEqual(importLegacyJson(store, dir), { users: 1 });
    assert.equal(store.getUser('vieja').username, 'vieja');
});

test('es idempotente: repetirla no duplica nada', () => {
    assert.deepEqual(importLegacyJson(store, dir), { users: 0 });
});

test('el usuario importado puede iniciar sesión con su contraseña anterior', async () => {
    const r = await t.request('POST', '/login', { body: { username: 'vieja', password: '123456' } });
    assert.equal(r.status, 200);
});

test('sin users.json devuelve null', () => {
    assert.equal(importLegacyJson(store, path.join(t.tmpDir, 'no-existe')), null);
});

test('una base de datos de la versión anterior (chat con IA) conserva los usuarios y descarta lo demás', () => {
    const Database = require('better-sqlite3');
    const file = path.join(t.tmpDir, 'v1.db');
    const old = new Database(file);
    old.exec(`
        CREATE TABLE users (username TEXT PRIMARY KEY, password_hash TEXT NOT NULL, contacts_seeded INTEGER NOT NULL DEFAULT 0);
        CREATE TABLE contacts (username TEXT, id TEXT, name TEXT, PRIMARY KEY (username, id));
        CREATE TABLE messages (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, contact_id TEXT, text TEXT);
        INSERT INTO users (username, password_hash) VALUES ('antigua', 'hash1'), ('Antigua', 'hash2');
        INSERT INTO contacts VALUES ('antigua', '1', 'Pepe');
        INSERT INTO messages (username, contact_id, text) VALUES ('antigua', '1', 'hola');
    `);
    old.close();

    // Se abre con el módulo real en un proceso aparte para que aplique la migración a esa base
    const { execFileSync } = require('child_process');
    const out = execFileSync(process.execPath, ['-e', `
        const s = require('./db');
        console.log(JSON.stringify({
            version: s.db.pragma('user_version', { simple: true }),
            users: s.db.prepare('SELECT username FROM users ORDER BY username').all().map(u => u.username),
            chats: s.listChats('antigua'),
            tables: s.db.prepare("SELECT name FROM sqlite_master WHERE type='table'").all().map(r => r.name).sort()
        }));
    `], { cwd: path.join(__dirname, '..'), env: { ...process.env, DB_FILE: file } }).toString();
    const r = JSON.parse(out);
    assert.equal(r.version, 2);
    assert.deepEqual(r.users, ['antigua'], 'los usuarios con el mismo nombre en distinta capitalización se unifican (se conserva el primero)');
    assert.deepEqual(r.chats, []);
    assert.ok(!r.tables.includes('users_old'));
    assert.ok(r.tables.includes('messages') && r.tables.includes('contacts') && r.tables.includes('sessions'));
});
