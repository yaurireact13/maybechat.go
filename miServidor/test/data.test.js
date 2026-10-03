// Lista de chats: búsqueda de usuarios, agregar, favoritos, eliminar y vaciar (cada usuario ve lo suyo)
const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { startServer } = require('./helpers');

let t, ana, beto, carla;
before(async () => {
    t = await startServer();
    ana = await t.newUser('Ana');
    beto = await t.newUser('Beto');
    carla = await t.newUser('Carla');
    await t.newUser('Carlos');
});
after(() => t.close());

const chats = async token => (await t.request('GET', '/api/chats', { token })).body;
const send = (token, to, text) => t.request('POST', `/api/chats/${to}/messages`, { token, body: { text } });

test('un usuario nuevo empieza sin chats', async () => {
    assert.deepEqual(await chats(ana), []);
});

test('busca usuarios por prefijo sin distinguir mayúsculas y sin incluirse a sí mismo', async () => {
    const r = await t.request('GET', '/api/users?q=car', { token: ana });
    assert.deepEqual(r.body.map(u => u.username), ['Carla', 'Carlos']);
    assert.equal(r.body[0].online, false);
    assert.deepEqual((await t.request('GET', '/api/users?q=AN', { token: beto })).body.map(u => u.username), ['Ana']);
    assert.deepEqual((await t.request('GET', '/api/users?q=an', { token: ana })).body, [], 'no se lista a sí mismo');
});

test('la búsqueda exige 2+ caracteres y trata % y _ como texto', async () => {
    for (const q of ['', 'a', '%', '_', '%%', 'a%']) {
        assert.deepEqual((await t.request('GET', `/api/users?q=${encodeURIComponent(q)}`, { token: ana })).body, [], q);
    }
});

test('agrega un usuario a la lista (con el nombre canónico) y es idempotente', async () => {
    const r = await t.request('POST', '/api/chats', { token: ana, body: { username: 'beto' } });
    assert.equal(r.status, 200);
    assert.equal(r.body.username, 'Beto');
    assert.equal(r.body.unreadCount, 0);
    assert.equal(r.body.lastMessage, null);
    await t.request('POST', '/api/chats', { token: ana, body: { username: 'BETO' } });
    assert.deepEqual((await chats(ana)).map(c => c.username), ['Beto']);
    // agregar a alguien no lo agrega a la otra persona
    assert.deepEqual(await chats(beto), []);
});

test('no se puede agregar a un usuario inexistente ni a sí mismo', async () => {
    assert.equal((await t.request('POST', '/api/chats', { token: ana, body: { username: 'fantasma' } })).status, 404);
    assert.equal((await t.request('POST', '/api/chats', { token: ana, body: { username: 'ANA' } })).status, 400);
    for (const body of [{}, { username: 5 }, { username: ['Beto'] }]) {
        assert.equal((await t.request('POST', '/api/chats', { token: ana, body })).status, 404, JSON.stringify(body));
    }
});

test('favoritos: marca y desmarca, y valida el valor', async () => {
    const r = await t.request('PATCH', '/api/chats/beto', { token: ana, body: { favorite: true } });
    assert.equal(r.body.favorite, true);
    assert.equal((await chats(ana))[0].favorite, true);
    assert.equal((await t.request('PATCH', '/api/chats/Beto', { token: ana, body: { favorite: 'si' } })).status, 400);
    await t.request('PATCH', '/api/chats/Beto', { token: ana, body: { favorite: false } });
    assert.equal((await chats(ana))[0].favorite, false);
    assert.equal((await t.request('PATCH', '/api/chats/fantasma', { token: ana, body: { favorite: true } })).status, 404);
});

test('los chats se ordenan por actividad reciente', async () => {
    await t.request('POST', '/api/chats', { token: ana, body: { username: 'Carla' } });
    await send(ana, 'Beto', 'hola Beto');
    await send(ana, 'Carla', 'hola Carla');
    assert.deepEqual((await chats(ana)).map(c => c.username), ['Carla', 'Beto']);
    await send(ana, 'Beto', 'otra vez');
    assert.deepEqual((await chats(ana)).map(c => c.username), ['Beto', 'Carla']);
});

test('eliminar un chat lo oculta solo para ti y borra tu historial', async () => {
    assert.equal((await t.request('DELETE', '/api/chats/Carla', { token: ana })).status, 200);
    assert.deepEqual((await chats(ana)).map(c => c.username), ['Beto']);
    assert.deepEqual((await t.request('GET', '/api/chats/Carla/messages', { token: ana })).body, []);
    // Carla conserva el suyo
    const deCarla = await chats(carla);
    assert.equal(deCarla[0].username, 'Ana');
    assert.equal(deCarla[0].lastMessage.text, 'hola Carla');
    assert.equal((await t.request('GET', '/api/chats/Ana/messages', { token: carla })).body.length, 1);
});

test('si te escriben o reabres el chat eliminado, vuelve sin el historial viejo', async () => {
    await send(carla, 'Ana', 'volví');
    const lista = await chats(ana);
    assert.deepEqual(lista.map(c => c.username).sort(), ['Beto', 'Carla']);
    const msgs = (await t.request('GET', '/api/chats/Carla/messages', { token: ana })).body;
    assert.deepEqual(msgs.map(m => m.text), ['volví'], 'solo lo posterior a la eliminación');
});

test('vaciar el chat borra el historial para ti pero el chat sigue en tu lista', async () => {
    assert.equal((await t.request('POST', '/api/chats/Beto/clear', { token: ana })).status, 200);
    assert.deepEqual((await t.request('GET', '/api/chats/Beto/messages', { token: ana })).body, []);
    const beto1 = (await chats(ana)).find(c => c.username === 'Beto');
    assert.equal(beto1.lastMessage, null);
    assert.equal(beto1.unreadCount, 0);
    // Beto sigue viendo todo
    assert.equal((await t.request('GET', '/api/chats/Ana/messages', { token: beto })).body.length, 2);
});

test('al borrar el chat no cuentan como no leídos los mensajes anteriores', async () => {
    await send(beto, 'Ana', 'viejo');
    assert.equal((await chats(ana)).find(c => c.username === 'Beto').unreadCount, 1);
    await t.request('POST', '/api/chats/Beto/clear', { token: ana });
    assert.equal((await chats(ana)).find(c => c.username === 'Beto').unreadCount, 0);
});
