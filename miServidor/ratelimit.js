// Limitador de peticiones en memoria (ventana fija). Suficiente para una sola instancia.
function createLimiter({ windowMs, max, key = req => req.ip, message = 'Demasiadas peticiones, inténtalo más tarde' }) {
    const hits = new Map(); // clave -> { count, resetAt }

    const timer = setInterval(() => {
        const now = Date.now();
        for (const [k, v] of hits) if (v.resetAt <= now) hits.delete(k);
    }, windowMs);
    timer.unref();

    return function limiter(req, res, next) {
        // las pruebas pueden desactivarlo con app.set('rateLimit', false)
        if (req.app.get('rateLimit') === false) return next();

        const now = Date.now();
        const k = key(req);
        let entry = hits.get(k);
        if (!entry || entry.resetAt <= now) {
            entry = { count: 0, resetAt: now + windowMs };
            hits.set(k, entry);
        }
        entry.count++;
        if (entry.count > max) {
            res.set('Retry-After', String(Math.ceil((entry.resetAt - now) / 1000)));
            return res.status(429).json({ error: message });
        }
        next();
    };
}

module.exports = { createLimiter };
