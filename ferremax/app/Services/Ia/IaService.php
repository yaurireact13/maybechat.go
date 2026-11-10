<?php

namespace App\Services\Ia;

use App\Models\IaConfig;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

/**
 * Asistente de reportes con IA + Chat IA · FerreMax (multi-empresa)
 *
 * Seguridad:
 *  - La IA NUNCA toca las tablas reales: solo ve las vistas de lectura v_ia_*.
 *  - Cada v_ia_* se materializa en cada consulta como un CTE filtrado por la empresa del usuario
 *    (WITH v_ia_ventas AS (SELECT ... FROM vb_ia_ventas WHERE empresa_id = N)), por lo que es
 *    imposible ver datos de otra ferretería.
 *  - El SQL generado se valida (solo SELECT), se ejecuta en una transacción de solo lectura,
 *    con límite de tiempo y de filas.
 */
class IaService
{
    public const MAX_FILAS = 500;

    private int $empresaId;
    private string $moneda;
    private static array $colCache = [];
    private static ?array $reales = null;

    public function __construct(private User $user)
    {
        $this->empresaId = (int) $user->empresa_id;
        abort_if($this->empresaId === 0, 403, 'Tu usuario no pertenece a una empresa.');
        $this->moneda = $user->empresa->moneda ?? 'S/';
    }

    public static function presets(): array
    {
        return config('ia.presets');
    }

    /* =====================================================================
     *  Vistas de solo lectura (la «capa de inteligencia»)
     *  Cada vista base (vb_ia_*) lleva empresa_id como primera columna.
     * ===================================================================== */
    public static function vistas(): array
    {
        $dv = "FROM venta_detalles vd JOIN ventas v ON v.id=vd.venta_id JOIN productos p ON p.id=vd.producto_id "
            . "LEFT JOIN categorias cat ON cat.id=p.categoria_id LEFT JOIN marcas m ON m.id=p.marca_id "
            . "LEFT JOIN users u ON u.id=v.user_id LEFT JOIN clientes c ON c.id=v.cliente_id";

        return [
            'v_ia_ventas' => [
                "Una fila por venta (comprobante). estado: completada, pendiente o anulada; las ventas válidas son 'completada'. metodo_pago: efectivo, tarjeta, transferencia, yape, plin o credito. saldo_pendiente > 0 en ventas a crédito = dinero por cobrar.",
                "SELECT v.empresa_id, v.id AS venta_id, v.fecha, DATE(v.fecha) AS dia, COALESCE(u.name,'Sin cajero') AS cajero, COALESCE(c.nombre,'Cliente Varios') AS cliente, v.tipo_comprobante, v.numero AS comprobante, v.subtotal, v.descuento, v.impuesto, v.total, v.saldo AS saldo_pendiente, v.fecha_vencimiento, v.metodo_pago, v.estado "
                . "FROM ventas v LEFT JOIN users u ON u.id=v.user_id LEFT JOIN clientes c ON c.id=v.cliente_id",
            ],
            'v_ia_detalle_ventas' => [
                "Una fila por producto vendido en cada venta. Úsala para productos/categorías/marcas más vendidos y para utilidades. importe = ingreso de la línea; utilidad = importe - cantidad*costo_unitario (costo actual del producto). Filtra estado_venta='completada'.",
                "SELECT v.empresa_id, vd.venta_id, v.fecha, DATE(v.fecha) AS dia, p.id AS producto_id, p.codigo, p.nombre AS producto, COALESCE(cat.nombre,'Sin categoría') AS categoria, COALESCE(m.nombre,'Sin marca') AS marca, vd.cantidad, vd.precio AS precio_unitario, p.precio_compra AS costo_unitario, vd.subtotal AS importe, (vd.subtotal - vd.cantidad*p.precio_compra) AS utilidad, v.estado AS estado_venta, v.metodo_pago, COALESCE(u.name,'Sin cajero') AS cajero, COALESCE(c.nombre,'Cliente Varios') AS cliente $dv",
            ],
            'v_ia_productos' => [
                "Catálogo de productos con su stock actual. activo=1 son los productos activos. stock_bajo=1 si stock <= stock_minimo.",
                "SELECT p.empresa_id, p.id AS producto_id, p.codigo, p.codigo_barras, p.nombre AS producto, COALESCE(cat.nombre,'Sin categoría') AS categoria, COALESCE(m.nombre,'Sin marca') AS marca, COALESCE(un.abreviatura,'') AS unidad, p.precio_compra, p.precio_venta, (p.precio_venta-p.precio_compra) AS margen_unitario, p.stock, p.stock_minimo, (p.stock<=p.stock_minimo) AS stock_bajo, p.ubicacion, (p.stock*p.precio_compra) AS valor_costo, (p.stock*p.precio_venta) AS valor_venta, p.activo, p.created_at AS creado_en "
                . "FROM productos p LEFT JOIN categorias cat ON cat.id=p.categoria_id LEFT JOIN marcas m ON m.id=p.marca_id LEFT JOIN unidades un ON un.id=p.unidad_id",
            ],
            'v_ia_clientes' => [
                "Clientes con su historial de compras (solo ventas 'completada'). saldo_deuda = crédito pendiente de pago. El cliente genérico se llama 'Cliente Varios'.",
                "SELECT c.empresa_id, c.id AS cliente_id, c.nombre, c.tipo_documento, c.numero_documento, c.telefono, c.email, c.activo, c.created_at AS creado_en, "
                . "(SELECT COUNT(*) FROM ventas v WHERE v.cliente_id=c.id AND v.estado='completada') AS num_compras, "
                . "(SELECT COALESCE(SUM(v.total),0) FROM ventas v WHERE v.cliente_id=c.id AND v.estado='completada') AS total_comprado, "
                . "(SELECT MAX(v.fecha) FROM ventas v WHERE v.cliente_id=c.id AND v.estado='completada') AS ultima_compra, "
                . "(SELECT COALESCE(SUM(v.saldo),0) FROM ventas v WHERE v.cliente_id=c.id AND v.metodo_pago='credito' AND v.estado<>'anulada') AS saldo_deuda "
                . "FROM clientes c",
            ],
            'v_ia_proveedores' => [
                "Proveedores con su historial de compras (no anuladas). saldo_por_pagar = deuda pendiente con el proveedor.",
                "SELECT pr.empresa_id, pr.id AS proveedor_id, pr.razon_social, pr.ruc, pr.telefono, pr.email, pr.contacto, pr.activo, "
                . "(SELECT COUNT(*) FROM compras co WHERE co.proveedor_id=pr.id AND co.estado<>'anulada') AS num_compras, "
                . "(SELECT COALESCE(SUM(co.total),0) FROM compras co WHERE co.proveedor_id=pr.id AND co.estado<>'anulada') AS total_comprado, "
                . "(SELECT MAX(co.fecha) FROM compras co WHERE co.proveedor_id=pr.id AND co.estado<>'anulada') AS ultima_compra, "
                . "(SELECT COALESCE(SUM(co.saldo),0) FROM compras co WHERE co.proveedor_id=pr.id AND co.a_credito=1 AND co.estado<>'anulada') AS saldo_por_pagar "
                . "FROM proveedores pr",
            ],
            'v_ia_compras' => [
                "Compras a proveedores (cabecera). estado: recibida, pendiente o anulada (las válidas no son 'anulada'). a_credito=1 con saldo > 0 = deuda por pagar.",
                "SELECT co.empresa_id, co.id AS compra_id, co.fecha, co.numero AS comprobante, COALESCE(pr.razon_social,'Sin proveedor') AS proveedor, co.subtotal, co.impuesto, co.total, co.a_credito, co.saldo, co.fecha_vencimiento, co.estado "
                . "FROM compras co LEFT JOIN proveedores pr ON pr.id=co.proveedor_id",
            ],
            'v_ia_detalle_compras' => [
                "Productos comprados a proveedores (detalle de compras).",
                "SELECT co.empresa_id, cd.compra_id, co.fecha, COALESCE(pr.razon_social,'Sin proveedor') AS proveedor, p.nombre AS producto, cd.cantidad, cd.precio AS costo_unitario, cd.subtotal AS importe, co.estado "
                . "FROM compra_detalles cd JOIN compras co ON co.id=cd.compra_id JOIN productos p ON p.id=cd.producto_id LEFT JOIN proveedores pr ON pr.id=co.proveedor_id",
            ],
            'v_ia_cotizaciones' => [
                "Cotizaciones. estado: vigente, aceptada, vencida o anulada.",
                "SELECT q.empresa_id, q.id AS cotizacion_id, q.numero, q.fecha, q.vencimiento, COALESCE(c.nombre,'Cliente Varios') AS cliente, q.total, q.estado "
                . "FROM cotizaciones q LEFT JOIN clientes c ON c.id=q.cliente_id",
            ],
            'v_ia_cobros_pagos' => [
                "Abonos registrados a cuentas por cobrar y por pagar. tipo='cobro' = un cliente nos pagó; tipo='pago' = pagamos a un proveedor.",
                "SELECT pg.empresa_id, pg.id AS pago_id, pg.fecha, pg.tipo, pg.monto, pg.metodo_pago, COALESCE(c.nombre, pr.razon_social, '—') AS contraparte, pg.referencia, pg.nota "
                . "FROM pagos pg LEFT JOIN ventas v ON v.id=pg.venta_id LEFT JOIN clientes c ON c.id=v.cliente_id LEFT JOIN compras co ON co.id=pg.compra_id LEFT JOIN proveedores pr ON pr.id=co.proveedor_id",
            ],
            'v_ia_cajas' => [
                "Aperturas y cierres de caja por cajero. estado: abierta o cerrada.",
                "SELECT ca.empresa_id, ca.id AS caja_id, ca.fecha_apertura, ca.fecha_cierre, COALESCE(u.name,'Sin cajero') AS cajero, ca.monto_apertura, ca.monto_cierre, ca.estado "
                . "FROM cajas ca LEFT JOIN users u ON u.id=ca.user_id",
            ],
            'v_ia_movimientos_inventario' => [
                "Kardex: entradas, salidas y ajustes de inventario (tipo: entrada, salida o ajuste).",
                "SELECT mi.empresa_id, mi.fecha, p.nombre AS producto, mi.tipo, mi.cantidad, mi.motivo, COALESCE(u.name,'Sistema') AS usuario "
                . "FROM movimientos_inventario mi JOIN productos p ON p.id=mi.producto_id LEFT JOIN users u ON u.id=mi.user_id",
            ],
        ];
    }

    private static function base(string $vista): string
    {
        return 'vb_' . substr($vista, 2); // v_ia_x -> vb_ia_x
    }

    /** Crea (o recrea) las vistas base si faltan. Con $force siempre las recrea. */
    public static function instalarVistas(bool $force = false): void
    {
        $vistas = self::vistas();
        if (! $force) {
            $n = DB::selectOne("SELECT COUNT(*) AS c FROM information_schema.views WHERE table_schema = DATABASE() AND table_name REGEXP '^vb_ia_'");
            if ((int) ($n->c ?? 0) === count($vistas)) {
                return;
            }
        }
        foreach ($vistas as $v => [, $sql]) {
            DB::unprepared('CREATE OR REPLACE VIEW `' . self::base($v) . '` AS ' . $sql);
        }
        self::$colCache = [];
        self::$reales = null;
    }

    public static function quitarVistas(): void
    {
        foreach (array_keys(self::vistas()) as $v) {
            DB::unprepared('DROP VIEW IF EXISTS `' . self::base($v) . '`');
        }
    }

    /** Columnas de una vista base, sin empresa_id (la IA no lo necesita). */
    private function columnas(string $base): array
    {
        if (! isset(self::$colCache[$base])) {
            self::$colCache[$base] = [];
            foreach (DB::select("SHOW COLUMNS FROM `$base`") as $c) {
                if ($c->Field !== 'empresa_id') {
                    self::$colCache[$base][$c->Field] = strtolower(preg_replace('/\(.*/', '', $c->Type));
                }
            }
        }

        return self::$colCache[$base];
    }

    public function esquemaTexto(): string
    {
        $t = '';
        foreach (self::vistas() as $v => [$desc]) {
            $cols = [];
            foreach ($this->columnas(self::base($v)) as $campo => $tipo) {
                $cols[] = "$campo $tipo";
            }
            $t .= "$v: $desc\n  columnas: " . implode(', ', $cols) . "\n";
        }

        return $t;
    }

    /** CTEs que «sustituyen» cada v_ia_* por sus filas de ESTA empresa. */
    private function ctes(): string
    {
        $p = [];
        foreach (array_keys(self::vistas()) as $v) {
            $base = self::base($v);
            $cols = implode(',', array_map(fn ($c) => "`$c`", array_keys($this->columnas($base))));
            $p[] = "$v AS (SELECT $cols FROM `$base` WHERE empresa_id = {$this->empresaId})";
        }

        return implode(', ', $p);
    }

    private function envolver(string $sql): string
    {
        if (preg_match('/^\s*with\s+/i', $sql)) {
            return preg_replace('/^\s*with\s+/i', 'WITH ' . $this->ctes() . ', ', $sql, 1);
        }

        return 'WITH ' . $this->ctes() . ' ' . $sql;
    }

    /* =====================================================================
     *  Configuración (por empresa) y clave cifrada
     * ===================================================================== */
    public function cfg(): array
    {
        $presets = self::presets();
        $row = IaConfig::where('empresa_id', $this->empresaId)->first();
        $p = $row->proveedor ?? 'gemini';
        if (! isset($presets[$p])) {
            $p = 'gemini';
        }
        $clave = '';
        if ($row && $row->clave) {
            try {
                $clave = Crypt::decryptString($row->clave);
            } catch (Throwable) {
                $clave = '';
            }
        }

        return [
            'proveedor' => $p,
            'url' => $row->url ?? $presets[$p]['url'],
            'modelo' => $row->modelo ?? $presets[$p]['modelo'],
            'clave' => $clave,
            'resumen' => $row ? (bool) $row->resumen : true,
        ];
    }

    public function listo(?array $c = null): bool
    {
        $c ??= $this->cfg();

        return $c['url'] !== '' && $c['modelo'] !== '' && (! self::presets()[$c['proveedor']]['clave'] || $c['clave'] !== '');
    }

    public function guardarConfig(string $proveedor, string $url, string $modelo, bool $resumen, string $clave): void
    {
        $datos = ['proveedor' => $proveedor, 'url' => $url, 'modelo' => $modelo, 'resumen' => $resumen];
        if ($clave === '__borrar__') {
            $datos['clave'] = null;
        } elseif ($clave !== '') {
            $datos['clave'] = Crypt::encryptString($clave);
        }
        IaConfig::updateOrCreate(['empresa_id' => $this->empresaId], $datos);
    }

    /* =====================================================================
     *  Llamada al modelo (API compatible con OpenAI)
     * ===================================================================== */
    public function llamar(array $mensajes, int $maxTokens = 900): string
    {
        $c = $this->cfg();
        if ($c['url'] === '' || $c['modelo'] === '') {
            throw new RuntimeException('Configura el proveedor de IA (URL y modelo).');
        }
        if (self::presets()[$c['proveedor']]['clave'] && $c['clave'] === '') {
            throw new RuntimeException('Falta la clave (API key) del proveedor de IA. Configúrala con el botón «Configurar IA».');
        }
        $url = rtrim($c['url'], '/') . '/chat/completions';
        if (! preg_match('#^https?://#i', $url)) {
            throw new RuntimeException('La URL del proveedor debe empezar por http:// o https://');
        }

        try {
            $req = Http::timeout(60)->connectTimeout(10)->acceptJson()->asJson();
            if ($c['clave'] !== '') {
                $req = $req->withToken($c['clave']);
            }
            $res = $req->post($url, ['model' => $c['modelo'], 'messages' => $mensajes, 'temperature' => 0, 'max_tokens' => $maxTokens]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('No se pudo conectar con el proveedor de IA (' . mb_substr($e->getMessage(), 0, 140) . '). Revisa tu internet y la URL.');
        }

        $code = $res->status();
        $j = $res->json();
        if ($code === 401 || $code === 403) {
            throw new RuntimeException('El proveedor rechazó la clave (API key). Revisa que sea correcta.');
        }
        if ($code === 429) {
            throw new RuntimeException('Se alcanzó el límite del plan gratuito. Espera un minuto e inténtalo de nuevo.');
        }
        if ($code === 404) {
            throw new RuntimeException('Modelo o URL no encontrados (' . $c['modelo'] . '). Google renueva sus modelos: prueba con gemini-3.5-flash-lite o gemini-3.5-flash.');
        }
        if ($code >= 400 || ! is_array($j)) {
            $m = is_array($j) ? ($j['error']['message'] ?? ($j[0]['error']['message'] ?? '')) : '';
            throw new RuntimeException('Error del proveedor de IA (' . $code . ')' . ($m ? ': ' . mb_substr((string) $m, 0, 200) : '.'));
        }
        $txt = $j['choices'][0]['message']['content'] ?? '';
        if (is_array($txt)) {
            $txt = implode('', array_map(fn ($p) => $p['text'] ?? '', $txt));
        }
        if (trim((string) $txt) === '') {
            throw new RuntimeException('La IA no devolvió respuesta. Intenta reformular la pregunta.');
        }

        return (string) $txt;
    }

    public static function extraerJson(string $t): ?array
    {
        $t = trim($t);
        if (preg_match('/```(?:json)?\s*(.*?)```/is', $t, $m)) {
            $t = trim($m[1]);
        }
        $j = json_decode($t, true);
        if (is_array($j)) {
            return $j;
        }
        $a = strpos($t, '{');
        $b = strrpos($t, '}');
        if ($a !== false && $b > $a) {
            $j = json_decode(substr($t, $a, $b - $a + 1), true);
            if (is_array($j)) {
                return $j;
            }
        }

        return null;
    }

    /* =====================================================================
     *  Validación del SQL (defensa en profundidad)
     * ===================================================================== */
    public function validarSql(string $sql): string
    {
        $sql = rtrim(trim($sql), "; \t\r\n");
        if ($sql === '' || strlen($sql) > 4000) {
            throw new InvalidArgumentException('La consulta está vacía o es demasiado larga.');
        }
        // Quita literales de texto para analizar solo la estructura
        $s = preg_replace("/'(?:[^'\\\\]|\\\\.|'')*'/s", ' 0 ', $sql);
        $s = preg_replace('/"(?:[^"\\\\]|\\\\.|"")*"/s', ' 0 ', $s);
        if (strpbrk($s, "'\"") !== false) {
            throw new InvalidArgumentException('Comillas sin cerrar en la consulta.');
        }
        if (str_contains($s, ';')) {
            throw new InvalidArgumentException('Solo se permite una consulta.');
        }
        if (preg_match('/--|\/\*|\*\/|#|@/', $s)) {
            throw new InvalidArgumentException('No se permiten comentarios ni variables.');
        }
        $l = strtolower(str_replace('`', ' ', $s));
        if (! preg_match('/^\s*(select|with)\b/', $l)) {
            throw new InvalidArgumentException('Solo se permiten consultas SELECT.');
        }
        $prohib = 'insert|update|delete|drop|alter|create|truncate|grant|revoke|call|execute|prepare|handler|load|outfile|dumpfile|into|lock|unlock|set|rename|use|show|describe|explain|optimize|analyze|sleep|benchmark|load_file|get_lock|release_lock|information_schema|performance_schema|mysql|sys|user|current_user|session_user|system_user|database|schema|version|connection_id|row_count|found_rows|last_insert_id|uuid|procedure|kill|shutdown|flush|reset|purge|install|uninstall|import|binlog|xa|savepoint|commit|rollback|begin|start|recursive';
        if (preg_match('/\b(' . $prohib . ')\b/', $l, $m)) {
            throw new InvalidArgumentException('Palabra no permitida en la consulta: ' . strtoupper($m[1]));
        }
        if (preg_match('/for\s+update|lock\s+in\s+share/', $l)) {
            throw new InvalidArgumentException('Bloqueos no permitidos.');
        }
        // Ninguna tabla/vista real: solo los alias v_ia_*
        if (self::$reales === null) {
            self::$reales = [];
            foreach (DB::connection()->getPdo()->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM) as $r) {
                self::$reales[strtolower($r[0])] = true;
            }
            self::$reales[strtolower((string) config('database.connections.' . config('database.default') . '.database'))] = true;
        }
        if (preg_match_all('/(?<![\w.])(?:(as)\s+)?([a-z_][a-z0-9_]*)\b/', $l, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $t) {
                if (isset(self::$reales[$t[2]]) && $t[1] === '') {
                    throw new InvalidArgumentException('Solo puedes consultar las vistas v_ia_* (no la tabla «' . $t[2] . '»). Si es un alias, usa otro nombre.');
                }
            }
        }
        if (preg_match('/\b[a-z_][a-z0-9_]*\s*\.\s*[a-z_][a-z0-9_]*\s*\./', $l)) {
            throw new InvalidArgumentException('No se permiten nombres con base de datos.');
        }

        return $sql;
    }

    /** Ejecuta en solo lectura con límite de tiempo y de filas. Devuelve columnas, filas, truncado, ms y sql. */
    public function ejecutar(string $sql): array
    {
        $sql = $this->validarSql($sql);
        self::instalarVistas();
        $completo = $this->envolver($sql);
        $pdo = DB::connection()->getPdo();
        $t0 = microtime(true);
        foreach (['SET SESSION MAX_EXECUTION_TIME=8000', 'SET SESSION max_statement_time=8'] as $x) {
            try {
                $pdo->exec($x);
            } catch (Throwable) {
            }
        }
        $buf = $pdo->getAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
        try {
            $pdo->exec('START TRANSACTION READ ONLY');
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
            $st = $pdo->query($completo);
            $n = $st->columnCount();
            $cols = $tipos = [];
            for ($i = 0; $i < $n; $i++) {
                $m = $st->getColumnMeta($i);
                $cols[] = $m['name'] ?? ('col' . $i);
                $tipos[] = strtoupper((string) ($m['native_type'] ?? ''));
            }
            $filas = [];
            $trunc = false;
            while ($r = $st->fetch(PDO::FETCH_NUM)) {
                if (count($filas) >= self::MAX_FILAS) {
                    $trunc = true;
                    break;
                }
                foreach ($r as $i => $v) {
                    if ($v !== null && is_string($v) && in_array($tipos[$i], ['NEWDECIMAL', 'DECIMAL', 'LONG', 'LONGLONG', 'SHORT', 'TINY', 'INT24', 'FLOAT', 'DOUBLE'], true) && is_numeric($v)) {
                        $r[$i] = $v + 0;
                    }
                }
                $filas[] = $r;
            }
            $st->closeCursor();
        } catch (PDOException $e) {
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $buf);
            try {
                $pdo->exec('ROLLBACK');
            } catch (Throwable) {
            }
            throw new RuntimeException('Error SQL: ' . preg_replace('/^SQLSTATE\[[^\]]*\]:?\s*(\d+\s*)?/', '', $e->getMessage()));
        }
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $buf);
        try {
            $pdo->exec('ROLLBACK');
        } catch (Throwable) {
        }

        return ['columnas' => $cols, 'filas' => $filas, 'truncado' => $trunc, 'ms' => (int) round((microtime(true) - $t0) * 1000), 'sql' => $sql];
    }

    /* =====================================================================
     *  Reglas de negocio compartidas por el Asistente y el Chat
     * ===================================================================== */
    private function reglas(): string
    {
        $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        $hoy = Carbon::now();

        return "REGLAS:\n- Genera un único SELECT (o WITH ... SELECT) usando SOLO las vistas v_ia_*. Sin punto y coma, sin comentarios, sin variables.\n"
            . '- Hoy es ' . $dias[(int) $hoy->format('w')] . ' ' . $hoy->format('Y-m-d') . " (usa CURDATE()/NOW() para fechas relativas). La semana empieza el lunes. «Este mes» = desde el día 1 del mes actual hasta hoy; «mes pasado» = mes calendario anterior.\n"
            . "- Los estados y métodos de pago están en minúscula. Ventas válidas: estado='completada' (en v_ia_detalle_ventas: estado_venta='completada'). Compras válidas: estado<>'anulada'.\n"
            . "- «Más vendidos» se mide por SUM(cantidad) salvo que pidan por monto. Productos activos: activo=1. Cuentas por cobrar: ventas con metodo_pago='credito', estado<>'anulada' y saldo_pendiente>0. Cuentas por pagar: compras con a_credito=1, estado<>'anulada' y saldo>0.\n"
            . "- Para buscar por nombre usa LIKE '%texto%' sobre LOWER(columna), con el texto en minúsculas y sin tildes si dudas.\n"
            . '- Moneda: ' . $this->moneda . ". Redondea importes con ROUND(x,2). Pon alias de columnas legibles en español (sin espacios: usa guion bajo), por ejemplo producto, unidades_vendidas, total_ventas.\n"
            . '- Si piden un ranking sin cantidad, usa LIMIT 10; en cualquier caso nunca más de ' . self::MAX_FILAS . " filas. Ordena de forma útil.\n"
            . '- Si la pregunta no se puede responder con estas vistas, no inventes.';
    }

    /* =====================================================================
     *  Asistente de reportes: pregunta en lenguaje natural → SQL
     * ===================================================================== */
    private function promptSistema(): string
    {
        return "Eres el analista de datos de una ferretería con punto de venta. Conviertes preguntas en español en UNA consulta SQL (MySQL 8) de solo lectura.\n\n"
            . "VISTAS DISPONIBLES (no existe nada más):\n" . $this->esquemaTexto() . "\n"
            . $this->reglas() . " Si no se puede responder, devuelve sql null y explica en «mensaje».\n\n"
            . "Responde SOLO con un objeto JSON, sin texto adicional ni markdown:\n"
            . '{"titulo":"título corto del reporte","sql":"SELECT ...","grafico":{"tipo":"bar|line|pie|none","x":"alias_columna_etiquetas","y":"alias_columna_numerica"},"mensaje":null}' . "\n"
            . 'grafico.tipo: line para series por fecha, bar para rankings/comparaciones, pie para participación (máx. 8 categorías), none si no tiene sentido graficar.';
    }

    public function preguntar(string $pregunta, ?array $previo = null): array
    {
        $m = [['role' => 'system', 'content' => $this->promptSistema()]];
        if ($previo && ! empty($previo['pregunta']) && ! empty($previo['sql'])) {
            $m[] = ['role' => 'user', 'content' => mb_substr((string) $previo['pregunta'], 0, 300)];
            $m[] = ['role' => 'assistant', 'content' => json_encode(['titulo' => '', 'sql' => mb_substr((string) $previo['sql'], 0, 1500), 'grafico' => ['tipo' => 'none'], 'mensaje' => null], JSON_UNESCAPED_UNICODE)];
        }
        $m[] = ['role' => 'user', 'content' => $pregunta];
        $ultimoError = '';
        for ($intento = 0; $intento < 2; $intento++) {
            $raw = $this->llamar($m);
            $j = self::extraerJson($raw);
            if (! $j) {
                $m[] = ['role' => 'assistant', 'content' => mb_substr($raw, 0, 800)];
                $m[] = ['role' => 'user', 'content' => 'Tu respuesta no era JSON válido. Responde SOLO el objeto JSON indicado.'];
                $ultimoError = 'La IA respondió en un formato inesperado.';
                continue;
            }
            if (empty($j['sql'])) {
                return ['mensaje' => (string) (($j['mensaje'] ?? '') ?: 'No puedo responder esa pregunta con los datos disponibles. Prueba con ventas, productos, clientes, compras, cuentas por cobrar o inventario.')];
            }
            try {
                $r = $this->ejecutar((string) $j['sql']);
                $g = is_array($j['grafico'] ?? null) ? $j['grafico'] : [];

                return $r + [
                    'titulo' => mb_substr(trim((string) ($j['titulo'] ?? '')) ?: $pregunta, 0, 160),
                    'grafico' => [
                        'tipo' => in_array($g['tipo'] ?? '', ['bar', 'line', 'pie', 'none'], true) ? $g['tipo'] : 'none',
                        'x' => (string) ($g['x'] ?? ''),
                        'y' => (string) ($g['y'] ?? ''),
                    ],
                ];
            } catch (Throwable $e) {
                $ultimoError = $e->getMessage();
                $m[] = ['role' => 'assistant', 'content' => $raw];
                $m[] = ['role' => 'user', 'content' => 'Esa consulta falló: ' . $ultimoError . '. Corrígela usando solo las vistas y columnas listadas y responde de nuevo SOLO el JSON.'];
            }
        }
        throw new RuntimeException('No pude generar una consulta válida para esa pregunta. Reformúlala con más detalle. (' . $ultimoError . ')');
    }

    public function resumir(string $pregunta, array $cols, array $filas): string
    {
        $muestra = array_slice($filas, 0, 25);
        $txt = $this->llamar([
            ['role' => 'system', 'content' => 'Eres un analista de una ferretería. Responde en español, en 1 o 2 frases claras, mencionando las cifras más relevantes. No uses listas, ni markdown, ni inventes datos que no estén en la tabla. Moneda: ' . $this->moneda . '.'],
            ['role' => 'user', 'content' => "Pregunta: $pregunta\nColumnas: " . implode(', ', $cols) . "\nFilas (" . count($filas) . ' en total, se muestran ' . count($muestra) . "):\n" . json_encode($muestra, JSON_UNESCAPED_UNICODE)],
        ], 250);

        return trim(strip_tags($txt));
    }

    /* =====================================================================
     *  Chat conversacional
     * ===================================================================== */
    private const GUIA = 'Punto de Venta (POS, atajos F2/F9): cobrar ventas en efectivo, tarjeta, transferencia, Yape, Plin o a crédito; antes hay que abrir la Caja. Ventas: historial, detalle, comprobante y anulación (devuelve el stock). Cotizaciones: crear, imprimir y convertir a venta. Caja: abrir, cerrar y arqueo. Productos: CRUD, búsqueda, márgenes, categorías, marcas y unidades (Catálogos). Inventario/Kardex: movimientos y ajustes manuales de stock. Compras: registrar compras (a contado o crédito), ingresan al stock y actualizan costos. Cuentas por Cobrar y por Pagar: cartera, vencidos, abonos parciales y estado de cuenta del cliente. Clientes y Proveedores: directorio con historial. Reportes: ventas, utilidad, tops y exportar CSV. Usuarios y Roles y Configuración (empresa, moneda, impuesto): solo administrador. Facturación Electrónica: boletas y facturas. Asistente IA (reportes con gráficos) y Chat IA.';

    private function promptChat(): string
    {
        return 'Eres el chatbot de «' . ($this->user->empresa->nombre ?? 'la ferretería') . "», un sistema de gestión para ferreterías. Conversas en español con el administrador, con tono cercano y breve.\n"
            . "Puedes: (1) consultar datos del negocio (productos, stock, ventas, clientes, proveedores, compras, cuentas por cobrar/pagar, cajas, cotizaciones); (2) explicar cómo usar el sistema; (3) saludar o aclarar. NO puedes registrar, modificar ni borrar nada: si te lo piden, explica en qué módulo se hace.\n\n"
            . "VISTAS DISPONIBLES (no existe nada más):\n" . $this->esquemaTexto() . "\n" . $this->reglas() . "\n"
            . "- Para datos de un producto, cliente o proveedor concretos devuelve pocas columnas útiles y LIMIT 20.\n"
            . "- Si el mensaje es una continuación («¿y el mes pasado?», «¿y por cajero?»), reutiliza y adapta la consulta anterior.\n\n"
            . 'GUÍA DEL SISTEMA: ' . self::GUIA . "\n\n"
            . "Responde SOLO con un objeto JSON, sin texto adicional ni markdown, en una de dos formas:\n"
            . '{"accion":"consulta","sql":"SELECT ..."}  → cuando necesites datos.' . "\n"
            . '{"accion":"respuesta","texto":"..."}  → cuando no necesites datos (saludo, ayuda de uso, aclaración, o si algo no es posible).';
    }

    /** Un turno de chat. $hist = [['q'=>..., 'sql'=>?, 'a'=>...], ...]. Devuelve ['texto'=>..., 'tabla'=>?, 'sql'=>?] */
    public function chat(string $msg, array $hist): array
    {
        $m = [['role' => 'system', 'content' => $this->promptChat()]];
        foreach (array_slice($hist, -6) as $h) {
            $m[] = ['role' => 'user', 'content' => (string) $h['q']];
            $m[] = ['role' => 'assistant', 'content' => json_encode(! empty($h['sql']) ? ['accion' => 'consulta', 'sql' => $h['sql']] : ['accion' => 'respuesta', 'texto' => mb_substr((string) $h['a'], 0, 300)], JSON_UNESCAPED_UNICODE)];
        }
        $m[] = ['role' => 'user', 'content' => $msg];
        $err = '';
        for ($i = 0; $i < 2; $i++) {
            $raw = $this->llamar($m, 700);
            $j = self::extraerJson($raw);
            if (! $j) {
                $t = trim(strip_tags($raw));
                if ($t !== '' && $t[0] !== '{') {
                    return ['texto' => $t];
                }
                $m[] = ['role' => 'assistant', 'content' => mb_substr($raw, 0, 500)];
                $m[] = ['role' => 'user', 'content' => 'Responde SOLO con el objeto JSON indicado.'];
                continue;
            }
            if (($j['accion'] ?? '') !== 'consulta' || empty($j['sql'])) {
                return ['texto' => trim((string) ($j['texto'] ?? $j['respuesta'] ?? '')) ?: 'No entendí bien. ¿Puedes darme más detalle?'];
            }
            try {
                $r = $this->ejecutar((string) $j['sql']);
            } catch (Throwable $e) {
                $err = $e->getMessage();
                $m[] = ['role' => 'assistant', 'content' => $raw];
                $m[] = ['role' => 'user', 'content' => 'Esa consulta falló: ' . $err . '. Corrígela usando solo las vistas y columnas listadas y responde SOLO el JSON.'];
                continue;
            }
            $muestra = array_slice($r['filas'], 0, 25);
            $texto = $this->llamar([
                ['role' => 'system', 'content' => 'Eres el chatbot de una ferretería. Responde en español con tono cercano y breve (máximo 4 frases o una lista corta con guiones). Usa las cifras EXACTAS de los datos, sin inventar nada. Importes con el símbolo ' . $this->moneda . ' (ejemplo de formato: ' . $this->moneda . ' 1,234.50). Si no hay filas, dilo claramente. No menciones SQL, vistas ni columnas técnicas.'],
                ['role' => 'user', 'content' => "Pregunta: $msg\nColumnas: " . implode(', ', $r['columnas']) . "\nFilas (" . count($r['filas']) . ' en total' . ($r['truncado'] ? ', hay más' : '') . ', se muestran ' . count($muestra) . "):\n" . json_encode($muestra, JSON_UNESCAPED_UNICODE)],
            ], 450);

            return [
                'texto' => trim(strip_tags($texto)),
                'sql' => $r['sql'],
                'tabla' => ['columnas' => $r['columnas'], 'filas' => array_slice($r['filas'], 0, 10), 'total' => count($r['filas'])],
            ];
        }
        throw new RuntimeException('No pude consultar eso con los datos disponibles. Prueba reformulando la pregunta. (' . $err . ')');
    }
}
