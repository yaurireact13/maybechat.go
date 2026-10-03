const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { startServer, contact } = require('./helpers');

let t, ana, beto;
before(async () => {
    t = await startServer();
    ana = await t.newUser('ana');
    beto = await t.newUser('beto');
});
after(() => t.close());

const msg = (text, sender = 'Tú') => ({ sender, text, time: '10:00' });

test('la primera vez se siembran los contactos desde contacts.json', async () => {
    const r = await t.request('GET', '/api/contacts', { token: ana });
    assert.equal(r.status, 200);
    assert.ok(r.body.length >= 1);
    assert.ok(r.body.every(c => typeof c.id === 'string' && typeof c.name === 'string'));
    // segunda lectura: no vuelve a sembrar
    const again = await t.request('GET', '/api/contacts', { token: ana });
    assert.deepEqual(again.body, r.body);
});

test('PUT /api/contacts guarda lista y orden, y completa valores por defecto', async () => {
    const lista = [contact('b'), contact('a', { favorite: true, unread: true, unreadCount: 3, image: '' })];
    assert.equal((await t.request('PUT', '/api/contacts', { token: ana, body: lista })).status, 200);
    const r = await t.request('GET', '/api/contacts', { token: ana });
    assert.deepEqual(r.body.map(c => c.id), ['b', 'a']);
    assert.equal(r.body[1].favorite, true);
    assert.equal(r.body[1].unreadCount, 3);
    assert.equal(r.body[1].image, 'img/contactundefined.jpg');
});

test('PUT /api/contacts rechaza listas inválidas y no modifica nada', async () => {
    const antes = (await t.request('GET', '/api/contacts', { token: ana })).body;
    const malos = [null, 'x', {}, [contact('a'), contact('a')], [contact('a', { name: '  ' })],
        [contact('a', { name: 'x'.repeat(101) })], [{ id: 5, name: 'num' }], Array.from({ length: 501 }, (_, i) => contact(String(i)))];
    for (const body of malos) {
        const r = await t.request('PUT', '/api/contacts', { token: ana, body });
        assert.equal(r.status, 400, JSON.stringify(body).slice(0, 80));
    }
    assert.deepEqual((await t.request('GET', '/api/contacts', { token: ana })).body, antes);
});

test('guarda mensajes en orden y los devuelve por contacto', async () => {
    await t.request('PUT', '/api/contacts', { token: ana, body: [contact('1'), contact('2')] });
    for (const m of [msg('uno'), msg('dos', 'Contacto 1'), msg('tres')]) {
        assert.equal((await t.request('POST', '/api/conversations/1/messages', { token: ana, body: m })).status, 200);
    }
    const r = await t.request('GET', '/api/conversations', { token: ana });
    assert.deepEqual(r.body['1'].map(m => m.text), ['uno', 'dos', 'tres']);
    assert.equal(r.body['2'], undefined);
});

test('rechaza mensajes inválidos y contactos inexistentes', async () => {
    const malos = [{}, { sender: 'Tú' }, { sender: '', text: 'x', time: '1' }, { sender: 'Tú', text: 5, time: '1' },
        { sender: 'Tú', text: 'x'.repeat(10001), time: '1' }];
    for (const body of malos) {
        assert.equal((await t.request('POST', '/api/conversations/1/messages', { token: ana, body })).status, 400);
    }
    assert.equal((await t.request('POST', '/api/conversations/nope/messages', { token: ana, body: msg('x') })).status, 404);
});

test('conserva solo los últimos 200 mensajes por chat', async () => {
    await t.request('PUT', '/api/contacts', { token: ana, body: [contact('tope')] });
    for (let i = 1; i <= 205; i++) {
        await t.request('POST', '/api/conversations/tope/messages', { token: ana, body: msg(`m${i}`) });
    }
    const chat = (await t.request('GET', '/api/conversations', { token: ana })).body.tope;
    assert.equal(chat.length, 200);
    assert.equal(chat[0].text, 'm6');
    assert.equal(chat.at(-1).text, 'm205');
});

test('DELETE borra el historial del chat sin tocar el contacto ni otros chats', async () => {
    await t.request('PUT', '/api/contacts', { token: ana, body: [contact('x'), contact('y')] });
    await t.request('POST', '/api/conversations/x/messages', { token: ana, body: msg('a') });
    await t.request('POST', '/api/conversations/y/messages', { token: ana, body: msg('b') });
    assert.equal((await t.request('DELETE', '/api/conversations/x', { token: ana })).status, 200);
    const conv = (await t.request('GET', '/api/conversations', { token: ana })).body;
    assert.equal(conv.x, undefined);
    assert.equal(conv.y.length, 1);
    const ids = (await t.request('GET', '/api/contacts', { token: ana })).body.map(c => c.id);
    assert.deepEqual(ids, ['x', 'y']);
});

test('quitar un contacto borra también sus mensajes (cascada)', async () => {
    await t.request('PUT', '/api/contacts', { token: ana, body: [contact('k1'), contact('k2')] });
    await t.request('POST', '/api/conversations/k1/messages', { token: ana, body: msg('a') });
    await t.request('PUT', '/api/contacts', { token: ana, body: [contact('k2')] });
    assert.deepEqual((await t.request('GET', '/api/conversations', { token: ana })).body, {});
    // si el contacto vuelve a crearse no reaparece el historial viejo
    await t.request('PUT', '/api/contacts', { token: ana, body: [contact('k1'), contact('k2')] });
    assert.deepEqual((await t.request('GET', '/api/conversations', { token: ana })).body, {});
});

test('cada usuario ve solo sus datos', async () => {
    await t.request('PUT', '/api/contacts', { token: ana, body: [contact('compartido', { name: 'De Ana' })] });
    await t.request('POST', '/api/conversations/compartido/messages', { token: ana, body: msg('secreto de ana') });

    const contactosBeto = (await t.request('GET', '/api/contacts', { token: beto })).body;
    assert.ok(!contactosBeto.some(c => c.name === 'De Ana'));
    assert.deepEqual((await t.request('GET', '/api/conversations', { token: beto })).body, {});
    // Beto no puede escribir en un contacto de Ana ni borrar su chat
    assert.equal((await t.request('POST', '/api/conversations/compartido/messages', { token: beto, body: msg('intruso') })).status, 404);
    await t.request('DELETE', '/api/conversations/compartido', { token: beto });
    const chatAna = (await t.request('GET', '/api/conversations', { token: ana })).body.compartido;
    assert.deepEqual(chatAna.map(m => m.text), ['secreto de ana']);
});

test('JSON mal formado o demasiado grande responde error limpio', async () => {
    const raw = async (body) => {
        const res = await fetch(`${t.base}/api/contacts`, {
            method: 'PUT', body,
            headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${ana}` }
        });
        return { status: res.status, body: await res.json() };
    };
    assert.deepEqual(await raw('{no es json'), { status: 400, body: { error: 'JSON inválido' } });
    assert.equal((await raw(JSON.stringify([contact('a', { status: 'x'.repeat(2 * 1024 * 1024) })]))).status, 413);
});

test('guarda texto con HTML y comillas tal cual (se escapa al pintar, no al guardar)', async () => {
    await t.request('PUT', '/api/contacts', { token: ana, body: [contact('h', { name: `<img src=x onerror=alert(1)> 'Ñandú'` })] });
    await t.request('POST', '/api/conversations/h/messages', { token: ana, body: msg(`<b>"hola"</b> 'ñ' 😀`) });
    assert.equal((await t.request('GET', '/api/contacts', { token: ana })).body[0].name, `<img src=x onerror=alert(1)> 'Ñandú'`);
    assert.equal((await t.request('GET', '/api/conversations', { token: ana })).body.h[0].text, `<b>"hola"</b> 'ñ' 😀`);
});
