# FerreMax — SaaS de Gestión para Ferreterías

Sistema web multi-empresa para ferreterías construido con **Laravel 11 + MySQL + Blade + Tailwind (CDN) + Chart.js**.

## ▶️ Cómo correrlo (Windows + Laragon)

**Necesitas:** [Laragon Full](https://laragon.org/download/) (trae PHP, MySQL y Composer) y [Git](https://git-scm.com/).

1. Abre Laragon y pulsa **Start All** (Apache y MySQL en verde). Verifica en *Menú → PHP* que sea 8.2 o superior.
2. Abre **Menú → Terminal** y ejecuta, uno por uno:

```bash
cd C:\laragon\www
git clone https://github.com/yaurireact13/LederHome.git ferremax
cd ferremax
composer install
copy .env.example .env
php artisan key:generate
mysql -u root -e "CREATE DATABASE IF NOT EXISTS saas_ferreteria CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --seed
php artisan serve --port=8087
```

3. Abre **http://127.0.0.1:8087** y entra con `admin@ferremax.com` / `admin123` (más usuarios en «Credenciales de demostración»).

Para volver a usarlo otro día solo necesitas: Laragon → *Start All*, terminal en la carpeta y `php artisan serve --port=8087`.

**Problemas comunes**

| Mensaje | Solución |
|---|---|
| `composer: command not found` | Usa la terminal de Laragon (Menú → Terminal), no la de Windows. |
| `SQLSTATE[HY000] [2002]` / *Connection refused* | MySQL no está encendido: pulsa *Start All* en Laragon. |
| `Unknown database 'saas_ferreteria'` | Ejecuta el comando `mysql -u root -e "CREATE DATABASE ..."` del paso 2. |
| `No application encryption key` | Ejecuta `php artisan key:generate`. |
| La página sale sin estilos | Verifica que existe `public/css/app.css`; si cambiaste vistas, ejecuta `npm install` y `npm run build`. |
| `Address already in use` | Usa otro puerto: `php artisan serve --port=8088`. |

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

# 2. Crear el .env y generar la clave de la aplicación
copy .env.example .env
php artisan key:generate

# 3. Crear la base de datos
mysql -u root -e "CREATE DATABASE IF NOT EXISTS saas_ferreteria CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
#    (o créala desde HeidiSQL/phpMyAdmin con el nombre: saas_ferreteria)

# 4. Crear tablas y datos de demostración
php artisan migrate --seed

# 5. (Opcional) Más datos para las gráficas del Dashboard
php artisan dashboard:demo

# 6. Iniciar el servidor
php artisan serve --port=8087
```

Abrir **http://127.0.0.1:8087**

### Estilos (Tailwind) y Chart.js — sin CDN

Los estilos ya vienen **compilados** en `public/css/app.css` y Chart.js en `public/js/vendor/`, por lo que
el sistema funciona sin Node.js y sin depender de CDNs externos. Solo si cambias clases en las vistas Blade
debes recompilar (requiere Node 18+):

```bash
npm install
npm run build      # o "npm run watch" mientras desarrollas
```

## Pruebas automatizadas

Las pruebas usan una base **separada** (`saas_ferreteria_test`), nunca la real:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS saas_ferreteria_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php vendor/bin/phpunit
```

Cubren: todas las pantallas por rol (admin, vendedor, super admin), venta en POS con stock y kardex,
ventas a crédito, aislamiento entre ferreterías, bloqueo de empresas suspendidas y límite de intentos de login.
GitHub Actions las ejecuta en cada push (`.github/workflows/ferremax.yml`).

## Credenciales de demostración

| Rol | Email | Contraseña |
|---|---|---|
| Super Admin (plataforma) | `superadmin@ferremax.com` | `super123` |
| Administrador | `admin@ferremax.com` | `admin123` |
| Vendedor | `vendedor@ferremax.com` | `vendedor123` |
| Cajero | `cajero@ferremax.com` | `cajero123` |
| Almacenero | `almacenero@ferremax.com` | `almacen123` |

> ⚠️ Son contraseñas de demostración: cámbialas antes de publicar el sistema.

La empresa demo *"Ferretería El Tornillo Feliz"* incluye 15 productos, 8 categorías, clientes, proveedores y ~6 meses de ventas para alimentar las gráficas del dashboard.

## Permisos por rol

Cada módulo exige un permiso. La matriz completa vive en **`config/permisos.php`** y es la única fuente de verdad:
protege las rutas (`can:`), oculta el menú y los botones (`@can`) y se muestra al administrador en **Usuarios y Roles**.

| Módulo | Admin | Vendedor | Cajero | Almacenero |
|---|:-:|:-:|:-:|:-:|
| Punto de venta (POS) | ✓ | ✓ | ✓ | |
| Historial de ventas y recibos | ✓ | ✓ | ✓ | |
| Anular ventas | ✓ | | | |
| Cotizaciones | ✓ | ✓ | | |
| Caja (apertura, cierre, arqueo) | ✓ | | ✓ | |
| Cuentas por cobrar y cobros | ✓ | | ✓ | |
| Cuentas por pagar y pagos | ✓ | | | |
| Clientes | ✓ | ✓ | ✓ | |
| Productos, catálogos, inventario/kardex | ✓ | | | ✓ |
| Compras y proveedores | ✓ | | | ✓ |
| Anular compras | ✓ | | | |
| Reportes, indicadores financieros del Dashboard | ✓ | | | |
| Usuarios, configuración, facturación, IA | ✓ | | | |

- El **Dashboard** muestra a cada rol solo sus bloques: el vendedor y el cajero ven sus ventas, pero no ingresos vs egresos ni lo que se debe a proveedores; el almacenero ve stock y movimientos. Los datos de las gráficas ocultas **ni siquiera se envían** al navegador.
- Para cambiar lo que puede hacer un rol, edita `config/permisos.php` (y luego `php artisan config:clear`). Las pruebas de `tests/Feature/PermisosTest.php` tienen la matriz escrita aparte a propósito: si cambias un permiso, también tienes que actualizarlas, así ningún cambio pasa sin querer.
- Si alguien entra a una sección sin permiso, ve una página **403 en español** que le indica su rol y le ofrece volver.

## Arquitectura multi-tenant

- Todas las tablas de negocio llevan `empresa_id`.
- El trait `App\Models\Concerns\PerteneceAEmpresa` aplica un **global scope**: cada consulta filtra automáticamente por la empresa del usuario autenticado, y al crear registros asigna `empresa_id` solo.
- Para registrar otra ferretería basta con crear una `Empresa` y sus `User`; los datos quedan aislados.

## Estructura de la base de datos

`empresas`, `users`, `categorias`, `marcas`, `unidades`, `productos`, `clientes`, `proveedores`, `ventas`, `venta_detalles`, `compras`, `compra_detalles`, `cotizaciones`, `movimientos_inventario`, `cajas`, `pagos`.

> Las tablas `ventas` y `compras` incluyen `saldo` y `fecha_vencimiento`; `compras` añade `a_credito`. La tabla `pagos` registra cada abono (cobro a cliente o pago a proveedor) con método, referencia y nota.

## Seguridad y correcciones (noviembre 2026)

| Cambio | Detalle |
|---|---|
| Clientes, Proveedores y Usuarios daban **error 500** | `@json(...)` dentro de `onclick` rompía el compilador de Blade. Ahora los datos van en un atributo `data-registro` escapado. |
| **Precio manipulable en el POS** | El servidor tomaba el precio que enviaba el navegador. Ahora el precio se lee siempre de la base de datos. |
| Stock con productos repetidos | Un carrito con el mismo producto dos veces podía saltarse la validación de stock. Ahora se agrupan antes de validar. |
| Error con petición sin `cliente_id` | El POS fallaba con "Undefined array key". Corregido. |
| Ferretería suspendida seguía operando | Las sesiones ya abiertas no se cerraban. Nuevo middleware `EnsureCuentaActiva` que revalida usuario y empresa en cada petición; el login también rechaza empresas suspendidas. |
| Fuerza bruta en el login | Límite de 10 intentos/minuto en `/login` y 5/minuto en `/registro`. |
| Referencias entre ferreterías | Productos (categoría/marca/unidad) y cotizaciones solo aceptan registros de la propia empresa. |
| Cookie de sesión | `secure` se activa automáticamente en producción (`SESSION_SECURE_COOKIE`). |
| Comando `dashboard:demo` | No existía; ahora carga datos de demostración (bloqueado en producción salvo `--force`). |
| Diseño responsive | En móvil las tablas desbordaban toda la página (faltaba `min-w-0`). Verificado sin desbordes a 320 y 375 px. |
| Dependencia de CDN | Tailwind (CDN "play", no apto para producción) y Chart.js ahora se sirven desde `public/`. |

## Próximas fases sugeridas

1. **Comprobantes electrónicos** (integración SUNAT u homólogo local).
2. ~~Permisos finos por rol~~ ✅ Hecho (ver «Permisos por rol»).
3. **Planes de pago y facturación del SaaS** (suscripciones por empresa).
4. **Reportes de antigüedad de saldos** (aging 30/60/90 días de la cartera).
