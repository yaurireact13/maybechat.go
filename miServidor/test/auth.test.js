const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { startServer } = require('./helpers');

let t;
before(async () => { t = await startServer(); });
after(() => t.close());

test('registro rechaza usuarios y contraseñas inválidos', async () => {
    const malos = [{}, { username: '', password: '123456' }, { username: 'ab', password: '123456' },
        { username: 'a'.repeat(21), password: '123456' }, { username: 'con espacio', password: '123456' },
        { username: '<b>x</b>', password: '123456' }, { username: 'ñandú', password: '123456' },
        { username: 'ana', password: '123' }, { username: 'ana', password: 'x'.repeat(73) },
        { username: 'ana', password: 123456 }, { username: ['ana'], password: '123456' }];
    for (const body of malos) {
        assert.equal((await t.request('POST', '/register', { body })).status, 400, JSON.stringify(body));
    }
});

test('registro y login correctos devuelven token y el nombre canónico', async () => {
    assert.equal((await t.request('POST', '/register', { body: { username: 'Ana_01', password: '123456' } })).status, 200);
    const r = await t.request('POST', '/login', { body: { username: 'ana_01', password: '123456' } });
    assert.equal(r.status, 200);
    assert.match(r.body.token, /^[0-9a-f]{64}$/);
    assert.equal(r.body.username, 'Ana_01');
});

test('no permite registrar un usuario repetido, ni con otras mayúsculas', async () => {
    await t.request('POST', '/register', { body: { username: 'repetido', password: '123456' } });
    for (const username of ['repetido', 'REPETIDO', 'Repetido']) {
        const r = await t.request('POST', '/register', { body: { username, password: 'otraclave' } });
        assert.equal(r.status, 400, username);
    }
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

test('la contraseña se guarda con hash y el token solo como hash', async () => {
    await t.request('POST', '/register', { body: { username: 'hash', password: 'secreta123' } });
    const row = require('../db').getUser('hash');
    assert.notEqual(row.password_hash, 'secreta123');
    assert.match(row.password_hash, /^\$2[aby]\$/);

    const token = await t.newUser('tok');
    const guardados = require('../db').db.prepare('SELECT token_hash FROM sessions').all().map(s => s.token_hash);
    assert.ok(!guardados.includes(token), 'el token no debe guardarse en claro');
});

test('/api/me devuelve el usuario y logout invalida el token', async () => {
    const token = await t.newUser('sesion');
    assert.deepEqual((await t.request('GET', '/api/me', { token })).body, { username: 'sesion' });
    assert.equal((await t.request('POST', '/api/logout', { token })).status, 200);
    assert.equal((await t.request('GET', '/api/me', { token })).status, 401);
});

test('las sesiones expiradas no sirven', async () => {
    const token = await t.newUser('expira');
    require('../db').db.prepare('UPDATE sessions SET expires_at = 1').run();
    assert.equal((await t.request('GET', '/api/me', { token })).status, 401);
});

test('todas las rutas /api exigen token válido', async () => {
    const rutas = [['GET', '/api/me'], ['POST', '/api/logout'], ['GET', '/api/users?q=ab'], ['GET', '/api/chats'],
        ['POST', '/api/chats'], ['PATCH', '/api/chats/x'], ['DELETE', '/api/chats/x'], ['POST', '/api/chats/x/clear'],
        ['GET', '/api/chats/x/messages'], ['POST', '/api/chats/x/messages'], ['POST', '/api/chats/x/read']];
    for (const [method, url] of rutas) {
        const body = method === 'GET' ? undefined : {};
        assert.equal((await t.request(method, url, { body })).status, 401, `${method} ${url}`);
        assert.equal((await t.request(method, url, { token: 'falso', body })).status, 401, `${method} ${url} token falso`);
    }
});

test('JSON mal formado o demasiado grande responde un error limpio', async () => {
    const raw = async body => {
        const res = await fetch(`${t.base}/login`, { method: 'POST', body, headers: { 'Content-Type': 'application/json' } });
        return { status: res.status, body: await res.json() };
    };
    assert.deepEqual(await raw('{no es json'), { status: 400, body: { error: 'JSON inválido' } });
    assert.equal((await raw(JSON.stringify({ username: 'x'.repeat(30 * 1024) }))).status, 413);
});

test('el límite de intentos bloquea con 429 y Retry-After', async () => {
    t.app.set('rateLimit', true);
    try {
        let ultimo;
        for (let i = 0; i < 40; i++) {
            ultimo = await t.request('POST', '/login', { body: { username: 'nadie', password: 'x' } });
            if (ultimo.status === 429) break;
        }
        assert.equal(ultimo.status, 429);
        assert.ok(Number(ultimo.headers.get('retry-after')) > 0);
    } finally {
        t.app.set('rateLimit', false);
    }
});

test('solo se publican los archivos del frontend (no el servidor ni la base de datos)', async () => {
    const status = async url => (await fetch(t.base + url)).status;
    for (const url of ['/', '/index.html', '/style.css', '/js/chat.js', '/Session/login.html', '/img/sinfondo-maybe.png']) {
        assert.equal(await status(url), 200, url);
    }
    for (const url of ['/miServidor/server.js', '/miServidor/package.json', '/miServidor/maybechat.db', '/miServidor/users.json',
        '/package.json', '/.git/config', '/contacts.json', '/js/../miServidor/server.js', '/Session/../miServidor/server.js']) {
        assert.notEqual(await status(url), 200, url);
    }
});

test('las respuestas llevan cabeceras de seguridad', async () => {
    const res = await fetch(t.base + '/index.html');
    assert.equal(res.headers.get('x-content-type-options'), 'nosniff');
    assert.match(res.headers.get('content-security-policy'), /script-src 'self'/);
    assert.equal(res.headers.get('x-powered-by'), null);
});
