# Servicio de Facturación Electrónica Multipaís

Microservicio **independiente** (PHP 8.2 + Laravel 11) que emite comprobantes electrónicos para
varios países (Perú/SUNAT hoy; Colombia/DIAN, Chile/SII, Argentina/ARCA, México/SAT después),
diseñado con **Clean Architecture + DDD** y patrón **Strategy/Adapter** por país.

> Este servicio está pensado para vivir en **su propio repositorio**, desplegado aparte del ERP.
> Aquí se incluye como carpeta hermana solo para entregar el scaffold junto al proyecto.

## Idea clave

El ERP no conoce SUNAT/DIAN/SII: habla un **contrato REST neutral** con este servicio. Para
agregar un país se crea un adaptador que implementa `ProveedorFacturacion` y se registra en
`config/facturacion.php`. **No se modifica** el dominio, los casos de uso ni el ERP.

Ver el diseño completo en [`docs/ARQUITECTURA.md`](docs/ARQUITECTURA.md).

## Puesta en marcha

```bash
cd facturacion-service
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve --port=8090
```

## API (v1)

| Método | Ruta | Acción |
|--------|------|--------|
| POST | `/api/v1/facturas` | Emitir factura/boleta (cabecera `Idempotency-Key`) |
| POST | `/api/v1/notas-credito` | Emitir nota de crédito |
| POST | `/api/v1/comprobantes/{id}/anular` | Anular / dar de baja |
| GET  | `/api/v1/comprobantes/{id}/estado` | Consultar estado ante el organismo |

Ejemplo de emisión:

```bash
curl -X POST http://localhost:8090/api/v1/facturas \
  -H "Authorization: Bearer <token>" \
  -H "Idempotency-Key: 4d1c...-uuid" \
  -H "Content-Type: application/json" \
  -d '{
    "pais": "PE", "tipo": "factura", "serie": "F001", "numero": "123",
    "moneda": "PEN", "fechaEmision": "2026-07-08T10:00:00",
    "emisor":  {"identificadorFiscal":"20512345678","razonSocial":"Ferretería El Tornillo Feliz"},
    "receptor":{"tipoDocumento":"RUC","numeroDocumento":"20111222333","razonSocial":"Constructora Andina SAC"},
    "lineas": [{"descripcion":"Martillo 16oz","cantidad":2,"precioUnitario":39.90,"tasaImpuesto":18}]
  }'
```

## Cómo agregar un país (resumen)

1. Copia `app/Infrastructure/Providers/_Plantilla/ProveedorPaisPlantilla.stub` a
   `app/Infrastructure/Providers/<Pais>/<Organismo>Proveedor.php`.
2. Implementa `ProveedorFacturacion` con la lógica local (XML/JSON, firma, transporte, respuesta).
3. Regístralo en `config/facturacion.php` (`'CO' => DianProveedor::class`).

Listo: el ERP factura en ese país enviando `"pais": "CO"`.

## Estado del scaffold

Incluye dominio, puertos, casos de uso, resolver/fábrica, adaptador Perú (stubs de UBL/firma/SOAP/CDR),
persistencia, almacenamiento, auditoría, idempotencia, REST y migraciones. Los stubs marcados con
`TODO` son los puntos de integración real con cada organismo.
