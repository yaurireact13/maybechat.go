# FerreMax — SaaS de Gestión para Ferreterías

Sistema web multi-empresa para ferreterías construido con **Laravel 11 + MySQL + Blade + Tailwind (CDN) + Chart.js**.

## Estado actual (Fase 1)

| Módulo | Estado |
|---|---|
| Login / Autenticación | ✅ Funcional |
| Dashboard (KPIs, gráficas, alertas) | ✅ Funcional |
| Menú lateral con todos los módulos | ✅ Funcional |
| Multi-tenant (columna `empresa_id` + scope global) | ✅ Funcional |
| Base de datos completa (15 tablas) + datos demo | ✅ Funcional |
| Productos (CRUD, búsqueda, filtros, márgenes, borrado lógico) | ✅ Funcional |
| Catálogos: categorías, marcas y unidades (CRUD inline) | ✅ Funcional |
| Kardex automático al crear/ajustar stock de productos | ✅ Funcional |
| POS: carrito, escáner de códigos, descuento, stock en vivo, atajos F2/F9 | ✅ Funcional |
| Comprobante imprimible (ticket 80mm) | ✅ Funcional |
| Ventas: historial con filtros, detalle y anulación con devolución de stock | ✅ Funcional |
| Compras: registro con ingreso a stock, actualización de costos y anulación | ✅ Funcional |
| Inventario/Kardex: movimientos con filtros y ajustes manuales | ✅ Funcional |
| Clientes y Proveedores: CRUD con historial de operaciones | ✅ Funcional |
| Caja: apertura, cierre y arqueo con diferencia en vivo | ✅ Funcional |
| Cotizaciones: creación, impresión y conversión a venta | ✅ Funcional |
| Reportes: ventas, utilidad, tops, gráficas y export CSV | ✅ Funcional |
| Usuarios y Roles (solo admin) | ✅ Funcional |
| Configuración de empresa, moneda e impuesto (solo admin) | ✅ Funcional |
| Registro self-service de empresas (onboarding SaaS) | ✅ Funcional |

## Estado actual (Fase 2)

| Módulo | Estado |
|---|---|
| Ventas a crédito desde el POS (saldo + vencimiento) | ✅ Funcional |
| Compras a crédito (saldo + vencimiento) | ✅ Funcional |
| Cuentas por Cobrar: cartera, vencidos, abonos parciales e historial | ✅ Funcional |
| Cuentas por Pagar: deuda a proveedores, abonos parciales e historial | ✅ Funcional |
| Estado de cuenta por cliente | ✅ Funcional |
| KPIs de Por Cobrar / Por Pagar en el Dashboard | ✅ Funcional |

> Para activar la Fase 2 ejecuta `php artisan migrate` (o `php artisan migrate:fresh --seed` para recrear con datos demo de crédito).

## Facturación electrónica (integración con el microservicio)

Al cerrar una **boleta** o **factura** en el POS, el ERP emite el comprobante electrónico de forma
**asíncrona** (no bloquea la venta) llamando al microservicio `facturacion-service`. El estado
(aceptado / observado / rechazado / error) se muestra en el detalle de la venta, con botón para
**reintentar** o **emitir** manualmente.

Configuración en `.env`:

```
FACT_ENABLED=true
FACT_MODO=simulado        # "simulado" prueba el flujo sin el microservicio; "real" lo consume
FACT_BASE_URL=http://localhost:8090
FACT_TOKEN=
FACT_PAIS=PE
```

> En modo **simulado** el comprobante se acepta localmente (con código `-SIM`), útil para demostrar
> el flujo end-to-end sin desplegar aún el microservicio. Cambia a `real` cuando el servicio esté
> corriendo. Ejecuta `php artisan migrate` para crear la tabla `comprobantes_electronicos`.

La arquitectura del microservicio está en `facturacion-service/` (ver `docs/ARQUITECTURA.md` y el PDF
`FerreMax_Arquitectura_Facturacion.pdf`).

## Estado actual (Fase 3 — Super Admin de plataforma)

| Módulo | Estado |
|---|---|
| Perfil **Super Admin** (controla toda la plataforma, sin empresa) | ✅ Funcional |
| Panel de plataforma con layout y métricas globales (MRR, empresas por plan, altas) | ✅ Funcional |
| Gestión de ferreterías/tenants: crear, editar, ver detalle, suspender/reactivar | ✅ Funcional |
| Provisión automática del tenant + su administrador inicial | ✅ Funcional |
| Usuarios globales: ver, activar/desactivar y resetear contraseña en cualquier empresa | ✅ Funcional |
| Impersonación de empresas (modo soporte con banner para volver) | ✅ Funcional |
| Catálogo de planes y precios con MRR por plan | ✅ Funcional |
| **Facturación de suscripción** por ferretería (pagos, renovación de vencimiento, reactivación) | ✅ Funcional |
| Panel de facturación global (recaudado del mes, MRR, morosos, próximos a vencer) | ✅ Funcional |
| **Límites por plan** con bloqueo: Básico 2 usuarios/150 productos · Pro 8/ilimitado · Premium ilimitado | ✅ Funcional |
| Indicadores de uso del plan en las pantallas de productos y usuarios | ✅ Funcional |

> Acceso Super Admin: **`superadmin@ferremax.com` / `super123`** (entra directo al panel `/panel`).
> Para activar la Fase 3 en una base existente: `php artisan migrate` y luego `php artisan db:seed --class=SuperAdminSeeder`.
> El super admin tiene `empresa_id` nulo y queda fuera del scope multi-tenant, por lo que puede ver datos de cualquier ferretería.

## Asistente IA y Chat IA (solo administrador)

| Módulo | Estado |
|---|---|
| **Asistente IA** (`/asistente`): pregunta en lenguaje natural → tabla, gráfico (barras/líneas/torta), resumen, SQL, CSV, favoritas e historial | ✅ Funcional |
| **Chat IA** (`/chat-ia` + burbuja flotante en todas las pantallas): consulta datos del negocio y explica cómo usar cada módulo | ✅ Funcional |
| Configuración por empresa: Gemini (gratis), Groq, OpenRouter, Ollama (local) u otro compatible con OpenAI; clave cifrada con `APP_KEY` | ✅ Funcional |

**Seguridad multi-empresa:** la IA nunca toca las tablas reales; solo ve 11 vistas de lectura (`vb_ia_*`) que en cada consulta se filtran por la `empresa_id` del usuario (CTE). El SQL generado se valida (solo `SELECT`), corre en una transacción `READ ONLY` con límite de 8 s y 500 filas.

Activación: `php artisan migrate` (crea `ia_configs`, `ia_consultas` y las vistas) y luego, en **Asistente IA → Configurar IA**, pega tu clave de https://aistudio.google.com/apikey.

## Requisitos

- PHP 8.2+ (Laragon lo incluye)
- Composer
- MySQL 5.7+ / 8.x corriendo en `localhost:3306`

## Registro de nuevas ferreterías

Cualquier ferretería puede crear su cuenta en **/registro**: se crea la empresa con su administrador, categorías y unidades iniciales, y el cliente genérico "Cliente Varios". Sus datos quedan completamente aislados de las demás empresas.

## Instalación (Laragon)

Abrir una terminal en `C:\SAAS\saas_ferreteria` y ejecutar:

```bash
# 1. Instalar dependencias
composer install

# 2. Generar la clave de la aplicación
php artisan key:generate

# 3. Crear la base de datos
mysql -u root -e "CREATE DATABASE IF NOT EXISTS saas_ferreteria CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
#    (o créala desde HeidiSQL/phpMyAdmin con el nombre: saas_ferreteria)

# 4. Crear tablas y datos de demostración
php artisan migrate --seed

# 5. Iniciar el servidor
php artisan serve
```

Abrir **http://localhost:8000**

## Credenciales de demostración

| Rol | Email | Contraseña |
|---|---|---|
| Administrador | `admin@ferremax.com` | `admin123` |
| Vendedor | `vendedor@ferremax.com` | `vendedor123` |

La empresa demo *"Ferretería El Tornillo Feliz"* incluye 15 productos, 8 categorías, clientes, proveedores y ~6 meses de ventas para alimentar las gráficas del dashboard.

## Arquitectura multi-tenant

- Todas las tablas de negocio llevan `empresa_id`.
- El trait `App\Models\Concerns\PerteneceAEmpresa` aplica un **global scope**: cada consulta filtra automáticamente por la empresa del usuario autenticado, y al crear registros asigna `empresa_id` solo.
- Para registrar otra ferretería basta con crear una `Empresa` y sus `User`; los datos quedan aislados.

## Estructura de la base de datos

`empresas`, `users`, `categorias`, `marcas`, `unidades`, `productos`, `clientes`, `proveedores`, `ventas`, `venta_detalles`, `compras`, `compra_detalles`, `cotizaciones`, `movimientos_inventario`, `cajas`, `pagos`.

> Las tablas `ventas` y `compras` incluyen `saldo` y `fecha_vencimiento`; `compras` añade `a_credito`. La tabla `pagos` registra cada abono (cobro a cliente o pago a proveedor) con método, referencia y nota.

## Próximas fases sugeridas

1. **Comprobantes electrónicos** (integración SUNAT u homólogo local).
2. **Permisos finos por rol** (middleware por módulo según rol).
3. **Planes de pago y facturación del SaaS** (suscripciones por empresa).
4. **Reportes de antigüedad de saldos** (aging 30/60/90 días de la cartera).
