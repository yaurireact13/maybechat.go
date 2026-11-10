# Arquitectura — Servicio de Facturación Electrónica Multipaís

**Rol:** microservicio independiente que el ERP FerreMax (y cualquier otro ERP) consume para
emitir comprobantes electrónicos en varios países, empezando por **Perú (SUNAT)** e
incorporando después **Colombia (DIAN)**, **Chile (SII)**, **Argentina (ARCA)** y **México (SAT)**
sin cambiar la lógica del ERP ni la del propio servicio.

## 1. Principios de diseño

- **Clean Architecture (hexagonal / puertos y adaptadores):** el núcleo (Dominio + Aplicación)
  no depende de frameworks, HTTP, SOAP ni base de datos. Las dependencias apuntan **hacia adentro**.
- **Domain-Driven Design:** un único *bounded context* "Facturación" con su **aggregate root**
  `Comprobante`, objetos de valor (`Dinero`, `Emisor`, `Receptor`, `LineaDetalle`) y lenguaje ubicuo.
- **Patrón Strategy / Adapter por país:** una sola interfaz (`ProveedorFacturacion`) y una
  implementación por organismo fiscal. Elegir el país selecciona la estrategia en tiempo de ejecución.
- **Principio Abierto/Cerrado (OCP) e Inversión de Dependencias (DIP):** agregar un país es
  *añadir* código nuevo, nunca *modificar* el existente.

## 2. Capas

```
Dominio          Núcleo puro (entidades, VO, enums, PUERTOS/interfaces). Sin framework.
Aplicación       Casos de uso que orquestan el dominio. Dependen solo de puertos.
Infraestructura  Adaptadores concretos: SUNAT/DIAN..., persistencia, HTTP, storage, colas.
```

Regla de oro: **el Dominio y la Aplicación nunca importan Infraestructura.** El "cableado"
(qué implementación cumple cada puerto) ocurre solo en el *composition root*
(`FacturacionServiceProvider`).

## 3. La interfaz común (puerto)

```php
interface ProveedorFacturacion
{
    public function pais(): Pais;
    public function emitirFactura(Comprobante $c): ResultadoEmision;
    public function emitirNotaCredito(Comprobante $nc): ResultadoEmision;
    public function anularComprobante(Comprobante $c, string $motivo): ResultadoEmision;
    public function consultarEstado(Comprobante $c): EstadoRemoto;
}
```

Cada método recibe y devuelve **tipos del dominio** (`Comprobante`, `ResultadoEmision`), nunca
estructuras crudas de SUNAT/DIAN. Traducir el modelo neutral ⇄ formato local es responsabilidad
exclusiva del adaptador de cada país.

## 4. Cómo se agrega un país nuevo (sin tocar nada existente)

1. Crear `app/Infrastructure/Providers/<Pais>/<Organismo>Proveedor.php` que **implemente**
   `ProveedorFacturacion` (usa la plantilla en `Providers/_Plantilla`).
2. Encapsular ahí todo lo local: construcción del XML/JSON, firma, transporte, parseo de respuesta.
3. Registrar el adaptador en `config/facturacion.php`:
   ```php
   'proveedores' => [
       'PE' => SunatProveedor::class,
       'CO' => DianProveedor::class,   // <- solo se AÑADE esta línea
   ],
   ```
4. (Opcional) parámetros del país en `config('facturacion.pais.CO')`.

El dominio, los casos de uso, el controlador REST y el ERP **no se modifican**.

## 5. Estándares por país (encapsulados en cada adaptador)

| País | Organismo | Formato | Identificador único | Transporte |
|------|-----------|---------|---------------------|------------|
| Perú | SUNAT | UBL 2.1 (XML firmado) | CDR / hash CPE | SOAP billService |
| Colombia | DIAN | UBL 2.1 + validación previa | CUFE | REST/SOAP DIAN |
| Chile | SII | DTE (XML + TED/CAF) | Folio + timbre | SOAP/REST SII |
| Argentina | ARCA (ex-AFIP) | WSFE (SOAP) | CAE | WSAA + WSFEv1 |
| México | SAT | CFDI 4.0 (XML) | UUID (timbre PAC) | vía PAC autorizado |

## 6. Modelo de datos

| Tabla | Propósito |
|-------|-----------|
| `comprobantes` | Estado y datos del comprobante; clave de negocio única por emisor (idempotencia). |
| `comprobante_eventos` | Auditoría append-only: cada transición y llamada al organismo. |
| `outbox_messages` | Patrón Outbox: eventos a publicar (webhooks al ERP / mensajería) con reintentos. |
| `idempotency_keys` | Respuestas cacheadas por `Idempotency-Key` para no emitir dos veces. |
| `credenciales_emisor` | Certificados y credenciales por emisor/país, cifrados en reposo. |

## 7. Comunicación ERP ⇄ Servicio

Dos modos complementarios:

- **REST síncrono** para operaciones directas: `POST /api/v1/facturas`, `/notas-credito`,
  `/comprobantes/{id}/anular`, `GET /comprobantes/{id}/estado`. Autenticación por token de servicio,
  cabecera `Idempotency-Key` obligatoria en emisión.
- **Mensajería / Outbox (recomendado en producción):** el ERP escribe la intención en su propia
  tabla `outbox` dentro de la misma transacción de la venta; un *job* llama al servicio con
  reintentos. Así, si el servicio o el organismo están caídos, **la venta no se bloquea** y el
  comprobante se emite de forma asíncrona. El servicio notifica el resultado por *webhook* (su
  propio Outbox) al ERP.

## 8. Manejo de errores, reintentos e idempotencia

- **Rechazo de negocio** (el organismo rechaza el comprobante): estado `RECHAZADO`, **no** se
  reintenta; se informa al ERP para corrección.
- **Fallo técnico** (timeout, red, organismo caído): estado `ERROR`/`reintentable`; un worker
  reintenta con **backoff exponencial** (2, 4, 8, 16, 32 s) hasta `reintentos.max`.
- **Idempotencia doble:** a nivel HTTP (`Idempotency-Key`) y a nivel de datos (índice único
  `pais+ruc_emisor+serie+numero`), garantizando que un reintento nunca genera un duplicado fiscal.
- **Circuit breaker / DLQ:** tras N fallos consecutivos se abre el circuito por país; los mensajes
  agotados van a una *dead-letter queue* para intervención manual.

## 9. Auditoría y almacenamiento

- Cada acción se registra en `comprobante_eventos` (quién, cuándo, request, response, código).
- El XML firmado, el CDR/acuse y el PDF se guardan mediante el puerto `AlmacenDocumentos`
  (S3/GCS/disco) con una ruta por `pais/ruc/año/mes/tipo-serie-numero`. Los documentos fiscales
  deben conservarse el tiempo que exige cada país (p. ej. 5 años).

## 10. Seguridad

- Certificados y credenciales SOL/PAC **cifrados en reposo** (`credenciales_emisor`).
- Autenticación servicio-a-servicio por token (Sanctum/OAuth client-credentials).
- Principio de mínimo privilegio; secretos fuera del código (variables de entorno / vault).

## 11. Estructura de carpetas

```
facturacion-service/
├─ app/
│  ├─ Domain/Facturacion/
│  │  ├─ Model/               Comprobante (aggregate) + ValueObject/
│  │  ├─ Enum/                Pais, TipoComprobante, EstadoComprobante
│  │  ├─ Event/ Exception/
│  │  └─ Port/                ProveedorFacturacion, ComprobanteRepository,
│  │                          AlmacenDocumentos, RegistroAuditoria, Resultado/
│  ├─ Application/
│  │  ├─ UseCase/             EmitirFactura, AnularComprobante,
│  │  │                       EmitirNotaCredito, ConsultarEstado
│  │  └─ Service/             ResolverProveedor
│  ├─ Infrastructure/
│  │  ├─ Providers/Peru/      SunatProveedor + Sunat/ (UBL, firma, SOAP, CDR)
│  │  ├─ Providers/_Plantilla/ Plantilla para nuevos países
│  │  ├─ Persistence/Eloquent/
│  │  ├─ Storage/ Audit/ Messaging/ Factory/
│  │  └─ Http/                Controllers, Requests, Middleware (Idempotencia)
│  └─ Providers/              FacturacionServiceProvider (composition root)
├─ config/facturacion.php     Mapa país → adaptador
├─ routes/api.php
└─ database/migrations/
```

## 12. Hoja de ruta por fases

- **Fase 0 — Cimientos:** dominio, puertos, casos de uso, REST, persistencia, idempotencia, CI. *(este scaffold)*
- **Fase 1 — Perú (SUNAT):** UBL 2.1 real, firma XML-DSig, SOAP billService, CDR, baja, PDF. Piloto.
- **Fase 2 — Colombia (DIAN):** adaptador UBL 2.1 + CUFE + validación previa. Sin tocar Fase 1.
- **Fase 3 — Chile (SII) y Argentina (ARCA):** DTE/TED y WSFE/WSAA + CAE.
- **Fase 4 — México (SAT):** CFDI 4.0 vía PAC.
- **Fase 5 — Escala:** colas/eventos, webhooks al ERP, panel de monitoreo, multi-tenant de certificados,
  circuit breaker y DLQ, observabilidad (logs/trazas/métricas).
```
