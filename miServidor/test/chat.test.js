// Mensajes entre usuarios
const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { startServer } = require('./helpers');

let t, ana, beto, carla;
before(async () => {
    t = await startServer();
    ana = await t.newUser('Ana');
    beto = await t.newUser('Beto');
    carla = await t.newUser('Carla');
});
after(() => t.close());

const send = (token, to, text) => t.request('POST', `/api/chats/${to}/messages`, { token, body: { text } });
const history = async (token, peer, query = '') =>
    (await t.request('GET', `/api/chats/${peer}/messages${query}`, { token })).body;
const chats = async token => (await t.request('GET', '/api/chats', { token })).body;

test('envía un mensaje y ambos lo ven en orden', async () => {
    const m = await send(ana, 'beto', 'hola Beto');
    assert.equal(m.status, 200);
    assert.deepEqual({ from: m.body.from, to: m.body.to, text: m.body.text, read: m.body.read },
        { from: 'Ana', to: 'Beto', text: 'hola Beto', read: false });
    assert.ok(Number.isInteger(m.body.id) && m.body.createdAt > 0);
    await send(beto, 'Ana', 'hola Ana');
    await send(ana, 'Beto', 'qué tal');

    const deAna = await history(ana, 'Beto');
    const deBeto = await history(beto, 'Ana');
    assert.deepEqual(deAna.map(x => x.text), ['hola Beto', 'hola Ana', 'qué tal']);
    assert.deepEqual(deAna, deBeto);
});

test('el chat aparece automáticamente en la lista del destinatario con no leídos', async () => {
    const lista = await chats(beto);
    assert.equal(lista.length, 1);
    assert.equal(lista[0].username, 'Ana');
    assert.equal(lista[0].unreadCount, 2);
    assert.equal(lista[0].lastMessage.text, 'qué tal');
    // los propios mensajes no cuentan como no leídos
    assert.equal((await chats(ana))[0].unreadCount, 1);
});

test('marcar como leído pone el contador en 0 solo para quien lee', async () => {
    assert.equal((await t.request('POST', '/api/chats/Ana/read', { token: beto })).status, 200);
    assert.equal((await chats(beto))[0].unreadCount, 0);
    assert.equal((await chats(ana))[0].unreadCount, 1, 'los mensajes de Beto a Ana siguen sin leer');
    const msgs = await history(ana, 'Beto');
    assert.deepEqual(msgs.map(m => m.read), [true, false, true]);
});

test('los chats son privados: un tercero no ve la conversación', async () => {
    assert.deepEqual(await history(carla, 'Ana'), []);
    assert.deepEqual(await history(carla, 'Beto'), []);
    assert.deepEqual(await chats(carla), []);
    await send(carla, 'Ana', 'hola desde Carla');
    assert.deepEqual((await history(ana, 'Beto')).map(m => m.text), ['hola Beto', 'hola Ana', 'qué tal']);
    assert.deepEqual((await history(ana, 'Carla')).map(m => m.text), ['hola desde Carla']);
});

test('valida el texto del mensaje', async () => {
    for (const body of [{}, { text: '' }, { text: '   ' }, { text: 5 }, { text: ['x'] }, { text: 'x'.repeat(2001) }, null]) {
        assert.equal((await t.request('POST', '/api/chats/Beto/messages', { token: ana, body })).status, 400, JSON.stringify(body)?.slice(0, 40));
    }
    const m = await send(ana, 'Beto', '  con espacios  ');
    assert.equal(m.body.text, 'con espacios');
    assert.equal((await send(ana, 'Beto', 'x'.repeat(2000))).status, 200);
});

test('no se puede escribir a un usuario inexistente ni a uno mismo', async () => {
    assert.equal((await send(ana, 'fantasma', 'hola')).status, 404);
    assert.equal((await send(ana, 'ana', 'hola')).status, 400);
    assert.equal((await t.request('GET', '/api/chats/fantasma/messages', { token: ana })).status, 404);
    assert.equal((await t.request('POST', '/api/chats/ana/read', { token: ana })).status, 400);
});

test('pagina el historial hacia atrás con before y limit', async () => {
    const dani = await t.newUser('Dani');
    const eli = await t.newUser('Eli');
    for (let i = 1; i <= 120; i++) await send(dani, 'Eli', `m${i}`);

    const ultimos = await history(eli, 'Dani');
    assert.equal(ultimos.length, 100, 'máximo 100 por página');
    assert.equal(ultimos[0].text, 'm21');
    assert.equal(ultimos.at(-1).text, 'm120');

    const anteriores = await history(eli, 'Dani', `?before=${ultimos[0].id}`);
    assert.deepEqual(anteriores.map(m => m.text), Array.from({ length: 20 }, (_, i) => `m${i + 1}`));

    const pocos = await history(eli, 'Dani', '?limit=3');
    assert.deepEqual(pocos.map(m => m.text), ['m118', 'm119', 'm120']);
    for (const q of ['?limit=0', '?limit=-5', '?limit=abc', '?before=xyz', '?limit=100000']) {
        assert.equal((await t.request('GET', `/api/chats/Dani/messages${q}`, { token: eli })).status, 200, q);
    }
});

test('guarda el texto con HTML y comillas tal cual (se escapa al pintar, no al guardar)', async () => {
    const texto = `<b>"hola"</b> 'ñ' <img src=x onerror=alert(1)> 😀`;
    await send(ana, 'Beto', texto);
    assert.equal((await history(beto, 'Ana')).at(-1).text, texto);
});

test('el límite de mensajes por minuto responde 429', async () => {
    const x = await t.newUser('Spammer');
    t.app.set('rateLimit', true);
    try {
        let ultimo;
        for (let i = 0; i < 70; i++) {
            ultimo = await send(x, 'Ana', `spam ${i}`);
            if (ultimo.status === 429) break;
        }
        assert.equal(ultimo.status, 429);
    } finally {
        t.app.set('rateLimit', false);
    }
});
