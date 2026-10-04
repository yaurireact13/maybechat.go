// Tiempo real por WebSocket
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

test('rechaza una conexión con token falso o sin autenticarse', async () => {
    const mala = await t.connect('token-falso', { auth: false });
    mala.send({ type: 'auth', token: 'token-falso' });
    assert.equal(await mala.closed, 4001);

    const basura = await t.connect(null, { auth: false });
    basura.ws.send('esto no es json');
    assert.equal(await basura.closed, 4000);

    const sinTipo = await t.connect(null, { auth: false });
    sinTipo.send({ token: ana });
    assert.equal(await sinTipo.closed, 4001);
});

test('un token cerrado con logout ya no sirve para conectarse', async () => {
    const token = await t.newUser('Temporal');
    await t.request('POST', '/api/logout', { token });
    const ws = await t.connect(null, { auth: false });
    ws.send({ type: 'auth', token });
    assert.equal(await ws.closed, 4001);
});

test('el destinatario recibe el mensaje al instante, y quien no está en el chat no recibe nada', async () => {
    const wsBeto = await t.connect(beto);
    const wsCarla = await t.connect(carla);
    const r = await send(ana, 'Beto', 'hola en vivo');
    const ev = await wsBeto.next('message');
    assert.deepEqual(ev.message, r.body);
    assert.equal(ev.message.from, 'Ana');
    await wsCarla.expectNone('message');
    wsBeto.close(); wsCarla.close();
});

test('el emisor recibe el mensaje en sus otras pestañas', async () => {
    const pestana1 = await t.connect(ana);
    const pestana2 = await t.connect(ana);
    const r = await send(ana, 'Carla', 'desde pestaña');
    assert.equal((await pestana1.next('message')).message.id, r.body.id);
    assert.equal((await pestana2.next('message')).message.id, r.body.id);
    pestana1.close(); pestana2.close();
});

test('marcar como leído avisa a las otras pestañas de quien lee', async () => {
    const a = await t.connect(carla);
    const b = await t.connect(carla);
    await send(ana, 'Carla', 'léeme');
    await a.next('message'); await b.next('message');
    await t.request('POST', '/api/chats/Ana/read', { token: carla });
    assert.equal((await b.next('read')).peer, 'Ana');
    a.close(); b.close();
});

test('presencia: avisa cuando alguien se conecta y se desconecta, solo a quienes lo tienen en su lista', async () => {
    const dani = await t.newUser('Dani');
    const eli = await t.newUser('Eli');
    const fran = await t.newUser('Fran');
    await t.request('POST', '/api/chats', { token: eli, body: { username: 'Dani' } }); // Eli tiene a Dani

    const wsEli = await t.connect(eli);
    const wsFran = await t.connect(fran); // Fran no tiene a Dani
    const wsDani = await t.connect(dani);
    assert.deepEqual(await wsEli.next('presence'), { type: 'presence', username: 'Dani', online: true });

    // en la lista de Eli, Dani figura en línea
    const lista = (await t.request('GET', '/api/chats', { token: eli })).body;
    assert.equal(lista.find(c => c.username === 'Dani').online, true);

    // una segunda pestaña de Dani no repite el aviso; cerrar solo una tampoco lo pone desconectado
    const wsDani2 = await t.connect(dani);
    wsDani.close();
    await wsEli.expectNone('presence');
    wsDani2.close();
    assert.deepEqual(await wsEli.next('presence'), { type: 'presence', username: 'Dani', online: false });
    await wsFran.expectNone('presence');
    wsEli.close(); wsFran.close();
});

test('la búsqueda de usuarios indica quién está en línea', async () => {
    const gabi = await t.newUser('Gabi');
    const ws = await t.connect(gabi);
    const r = await t.request('GET', '/api/users?q=gab', { token: ana });
    assert.deepEqual(r.body, [{ username: 'Gabi', online: true }]);
    ws.close();
});
