const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { startServer } = require('./helpers');

let t;
before(async () => { t = await startServer(); });
after(() => t.close());

test('registro rechaza datos inválidos', async () => {
    for (const body of [{}, { username: '', password: '123456' }, { username: '  ', password: '123456' },
        { username: 'a', password: '123' }, { username: 'a', password: 123456 }, { username: ['a'], password: '123456' }]) {
        const r = await t.request('POST', '/register', { body });
        assert.equal(r.status, 400, JSON.stringify(body));
    }
});

test('registro y login correctos devuelven token', async () => {
    assert.equal((await t.request('POST', '/register', { body: { username: 'ana', password: '123456' } })).status, 200);
    const r = await t.request('POST', '/login', { body: { username: 'ana', password: '123456' } });
    assert.equal(r.status, 200);
    assert.match(r.body.token, /^[0-9a-f]{64}$/);
});

test('no permite registrar un usuario repetido', async () => {
    await t.request('POST', '/register', { body: { username: 'repetido', password: '123456' } });
    const r = await t.request('POST', '/register', { body: { username: 'repetido', password: 'otraclave' } });
    assert.equal(r.status, 400);
});

test('login falla con clave incorrecta, usuario inexistente o tipos raros', async () => {
    await t.request('POST', '/register', { body: { username: 'luis', password: '123456' } });
    for (const body of [{ username: 'luis', password: 'mala' }, { username: 'nadie', password: '123456' },
        { username: 'luis' }, { username: { $ne: 1 }, password: '123456' }, {}]) {
        const r = await t.request('POST', '/login', { body });
        assert.equal(r.status, 401, JSON.stringify(body));
        assert.equal(r.body.token, undefined);
    }
});

test('la contraseña se guarda con hash, no en texto plano', async () => {
    await t.request('POST', '/register', { body: { username: 'hash', password: 'secreta123' } });
    const row = require('../db').getUser('hash');
    assert.notEqual(row.password_hash, 'secreta123');
    assert.match(row.password_hash, /^\$2[aby]\$/);
});

test('todas las rutas /api exigen token válido', async () => {
    const rutas = [['GET', '/api/contacts'], ['PUT', '/api/contacts'], ['GET', '/api/conversations'],
        ['POST', '/api/conversations/1/messages'], ['DELETE', '/api/conversations/1'], ['POST', '/api/chat']];
    for (const [method, url] of rutas) {
        assert.equal((await t.request(method, url, { body: method === 'GET' ? undefined : {} })).status, 401, `${method} ${url}`);
        assert.equal((await t.request(method, url, { token: 'falso', body: method === 'GET' ? undefined : {} })).status, 401, `${method} ${url} token falso`);
    }
});
