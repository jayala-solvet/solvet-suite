# Validación de la base 0.1.0

Fecha: 2026-10-15.

## Ejecutado

- Dependencias instaladas realmente mediante Composer; versiones concretas en `composer.lock`.
- `composer validate --strict`: correcto. La resolución reportó sin avisos de vulnerabilidad conocidos en ese momento.
- 11 pruebas PHPUnit, 35 aserciones: correctas, con PHP 8.5.11 y SQLite en memoria.
- Repetición de pruebas con variables externas de PostgreSQL: el bootstrap fuerza el entorno de prueba aislado y no se conecta a la instalación.
- Pruebas de cálculo, IVA incluido, descuento, USD, formato de montos grandes sin pérdida de dígitos, validación de cantidad/precio, preservación de PDF y snapshot, bloqueo de edición/emisión repetida, permisos, evidencia, rutas privadas, render y persistencia del formulario Livewire.
- Formato de PHP aplicado con Laravel Pint.
- Compose principal y combinación de desarrollo validados con `docker compose config --quiet` (CLI Compose v5.6.0); sintaxis de scripts Bash verificada.
- Aplicación iniciada con PHP 8.5.11/FrankenPHP y SQLite para revisión real, con datos ficticios.
- Navegador Chromium: inicio de sesión, vista general, listado, formulario y móvil de 390 px. Sin errores JavaScript; sin imágenes rotas; documento móvil sin desbordamiento global (390 px). Tabla con desplazamiento interno.
- PDF de una página y PDF de tres páginas en USD renderizados e inspeccionados con Poppler. Cabeceras de tabla, pie y numeración de página revisados. Ejemplo incluido en `ejemplos/presupuesto-multipagina-demo.pdf`.

## Pendiente de ejecución en la infraestructura de destino

En este entorno no había Docker Engine. No se ejecutó `docker build` ni se levantaron los contenedores Nginx/PHP-FPM/PostgreSQL. La validación de Compose comprueba configuración, no construcción ni arranque.

Antes de operar con datos reales:

1. Ejecutar `bash scripts/setup.sh` y comprobar salud de servicios.
2. Crear usuario, configurar empresa y recorrer creación → emisión → PDF → decisión.
3. Probar emisión concurrente contra PostgreSQL y verificar unicidad.
4. Probar respaldo y restauración en instalación separada.
5. Configurar HTTPS/proxy/secretos y verificar permisos según la instalación.

Las capturas de navegador fueron materiales internos de verificación y no se incluyen en el proyecto. El ejemplo PDF y los registros demostrativos contienen datos ficticios.

## Base operativa de presupuestos · 15/10/2026

Verificaciones de esta entrega:

- PHP 8.5.11, dependencias de `composer.lock`: 18 pruebas, 195 aserciones aprobadas. Incluyen pestañas, moneda, período, búsqueda por correlativo/identificador/snapshot, emisión y decisión desde Livewire, preview inline con bytes originales, permisos de consulta y vencimiento sin mutación de estado.
- Navegador Chromium real: 24 estados, 1545 comprobaciones de contraste de texto, cero fallas en esas combinaciones y cero errores JavaScript. Listado, vacío, pestañas, selección, detalle, formularios, cálculo durante edición, confirmación, clientes e historial; claro/oscuro y ancho móvil 390 px. Sin desbordamiento global en pantallas revisadas.
- PDFs generados por Dompdf: PYG de una página, 35 ítems en cuatro páginas, USD con decimales y marca de borrador. Inspección de páginas renderizadas, logo completo, encabezados repetidos, condiciones y paginación.
- Pint y `git diff --check` aprobados.

Se ejecutó la aplicación con PHP local y SQLite de pruebas; no hay Docker Engine en este entorno. No se ejecutó construcción Docker ni prueba de concurrencia PostgreSQL. La imagen de despliegue sigue usando el mismo Dockerfile y las mismas dependencias. No se accedió al servidor de José ni se emitieron documentos en su base. La instalación debe aplicar `scripts/update.sh` y verificar `/up` e ingreso.

## Refinamiento de referencia Supabase / Refero · 15/10/2026

Cambio de tokens y cabecera oscura, sin alterar el módulo comercial. Suite existente: 18 pruebas / 195 aserciones aprobadas. Chromium: 24 estados de escritorio/móvil claro/oscuro, 1545 comprobaciones de contraste de texto, cero fallas detectadas y cero errores JavaScript. Inspección visual de listado, formulario y detalle móvil; captura de aplicación real conservada en `docs/referencias/presupuestos-oscuro-neutro.png`. Enlaces locales y diff revisados. No se ejecutó una construcción Docker ni se accedió al servidor de José.

## Ampliación de carga, catálogo, pago y baja · 15/10/2026

- Suite existente y nueva: 26 pruebas / 263 aserciones aprobadas sobre PHP 8.5 y SQLite de pruebas. Casos de crédito obligatorio/límites/contado, términos por empresa, catálogo manual/mixto/moneda/centavos, protección de archivos emitidos, alta inline sin perder el formulario, permisos de catálogo/baja y soft delete con auditoría y 404 de rutas privadas.
- Migración aditiva aplicada sobre la base local de QA existente, además del esquema limpio de pruebas. No se accedió al servidor de José.
- Chromium: 30 estados claro/oscuro y escritorio/móvil, 1962 comprobaciones de contraste de texto sin fallas detectadas y cero errores JavaScript. Incluye catálogo, formulario de catálogo, configuración de términos, ítems, importes y campos nuevos.
- Recorrido real de navegador: seleccionar catálogo, agregar otro ítem manual, total ₲ 650.000, guardar crédito a 45 días, emitir, obtener PDF conservado, crear otro borrador y eliminarlo desde edición con confirmación. Verificado 404 del PDF de la baja; sin errores JavaScript.
- PDF renderizado e inspeccionado: PYG de una página, USD con marca de borrador y 35 ítems en tres páginas, con encabezados, términos, pago y paginación. PDF de la emisión de navegador comprobado por contenido y render.
- Pint, enlaces locales y `git diff --check` revisados. Sin construcción Docker o prueba de concurrencia PostgreSQL en este entorno; deben validarse en el destino antes de producción.

[Ejemplo visual del documento](ejemplos/presupuesto-condiciones.png) con datos ficticios; la fuente efectiva es la plantilla Blade, no la captura.

## Dashboard comercial inicial · 16/10/2026

- PHP 8.5 / SQLite: suite completa de 30 pruebas y 300 aserciones aprobadas. Casos nuevos: partición por estado/vigencia, período de emisión, moneda, exclusión de borradores/bajas/futuros, importes exactos grandes, cero, snapshot, seguimiento, filtros Livewire y autorización.
- Chromium real: 12 estados, 488 comprobaciones de contraste de texto sin fallas detectadas, cero errores JavaScript. Escritorio 1512 px y móvil 390 px, claro/oscuro, PYG/USD, período completo y selección vacía; sin desbordamiento global. Inspección visual de las capturas y navegación a detalle y nuevo presupuesto verificadas.
- Pint y diff revisados; CSS publicado desde la fuente. Sin migraciones o dependencias nuevas. No se accedió al laboratorio, ni se ejecutó Docker o PostgreSQL. No se simularon ganancias/cobros ni se modificaron documentos del usuario.

La captura [dashboard-inicial.png](referencias/dashboard-inicial.png) muestra datos ficticios de QA; el cálculo efectivo es `CommercialDashboard`, según su [contrato](modulos/dashboard.md).

## Costos y precios · 16/10/2026

- PHP 8.5 / SQLite: suite completa de 37 pruebas y 371 aserciones aprobadas. Costos propios/terceros/productos, IVA previsto/no recuperable/pendiente, margen/recargo/utilidad fija, redondeo PYG/USD, límites, historial, publicación opcional de precio, snapshot con descuentos/cero/desconocido, conservación de versión/PDF y privacidad de reader. Render comercial comprobado sin proveedor/referencia interna.
- Migración aditiva aplicada en base local existente de QA y en bases limpias de pruebas. No hubo carga demo ni acceso a la instalación de José.
- Chromium real: 33 estados y 2299 comprobaciones de contraste de texto, cero fallas detectadas, cero errores JavaScript y sin desbordamiento global en las pantallas revisadas. Costos/lista/formulario/modal, presupuestos, catálogo y dashboard; claro/oscuro y escritorio/móvil. Recorridos reales de creación de versión, cálculo, guardado/emisión de presupuesto con costos, elección de propio/tercero, pérdida por descuento y denegación de costos a reader.
- Pint, enlaces locales, diff y coincidencia de CSS fuente/publicado revisados. Sin dependencias nuevas. No se ejecutó Docker Engine ni concurrencia PostgreSQL en este entorno. Validar esos aspectos en el destino antes de producción.

[Captura real de Costos y precios](referencias/costos-precios.png), con datos ficticios locales. La documentación funcional está en [COS-01 a COS-04](modulos/costos-precios.md).

## Compras, inventario y ejecución · 16/10/2026

- PHP 8.5 / SQLite: suite completa de **47 pruebas y 488 aserciones aprobadas**. Incluye A/B, compra/recepción, reserva parcial, entrega idempotente, falta de stock, promedio ponderado, devolución al costo de salida conservado, ajustes con motivo, reparto de envío por cantidad/valor/manual, ejecución propia/externa y anulación. Se comprueban creación Livewire de compra, permisos de detalle, publicación explícita de precios, componentes, cantidades, conservación de snapshots y bytes de PDF emitidos.
- Las dos migraciones aditivas se aplicaron sobre la base local existente y sobre bases limpias de prueba. No crean existencias, precios ni datos demo del usuario.
- Chromium: matriz final de 36 estados claro/oscuro, escritorio 1512 px/móvil 390 px; 1814 comprobaciones de contraste sin fallas, cero errores JavaScript y cero desbordamientos globales. Seis estados adicionales verifican caso A terminado, carga real de compra, error visible por salida sin stock y reader sin información financiera: 305 comprobaciones adicionales sin fallas. Inspección visual de detalle de pedido y móvil oscuro.
- Recorrido real A/B completado en navegador: recepción de ambos, reserva/entrega del equipo, hora propia, cierre de pedidos. A: utilidad ₲ 147.273, margen 24,54545 %, sin stock. B: costo efectivo equipo/envío ₲ 472.727, costo total ₲ 537.727, utilidad ₲ 62.273; una entrada/salida y ningún movimiento por configuración.
- Pint, enlaces locales, diff y CSS fuente/publicado idéntico comprobados. Sin dependencias nuevas. No se accedió al servidor de José ni se ejecutaron Docker Engine o concurrencia PostgreSQL; comprobar esos aspectos en el destino.

Las capturas de prueba permanecen fuera del repositorio. La fuente efectiva es el código y [contrato OP-01…OP-08](modulos/operaciones.md).

## Revisión cruzada con propuesta ERP del otro chat · 16/10/2026

- Comparado PR #10 (bb9255c) con main/PR #11 (5a4a84d). Las capacidades de catálogo y prohibición de stock para servicios ya están implementadas y cubiertas por OperationsTest. PR #10 cerrado como sustituido; no integrar sus columnas duplicadas. Se conserva su propuesta en GitHub, sin desplegar esa rama.
- Suite completa repetida sobre main: 47 pruebas / 488 aserciones aprobadas. migrate:status local: las ocho migraciones aplicadas, sin pendientes. Correcciones de espacios en tres archivos de pruebas para que Pint completo pase; sin cambios de reglas de negocio.
- Chromium repetido: 36 pantallas, 1852 comprobaciones de contraste sin fallas, cero errores JavaScript y cero desbordamientos globales; claro/oscuro y 1512/390 px. Solo consultas y formularios con datos ficticios locales, sin nuevas operaciones comerciales.
- No hay Docker Engine ni PostgreSQL disponibles en este entorno. Apto para prueba funcional en laboratorio con main; construcción Docker, esquema real del destino y concurrencia PostgreSQL siguen pendientes de validar allí. No se accedió al servidor ni se aplicaron migraciones remotas.

## Entornos separados · 16/10/2026

- `tests/Infrastructure/environments.sh` aprobado con Docker/Git simulados en carpeta temporal: tres perfiles, claves distintas, permisos 600, no regeneración, argumentos/proyectos correctos y limpieza de variables heredadas, montajes solo de desarrollo, proyecto de PHPUnit separado, respaldos por entorno, comandos destructivos bloqueados en producción y fallo de migración sin reapertura. No ejecuta contenedores ni consulta bases instaladas.
- Suite de aplicación PHP 8.5/SQLite: 47 pruebas / 488 aserciones aprobadas. Pint completo aprobado; sintaxis Bash, YAML de Compose, enlaces locales y git diff --check sin errores.
- compose.yml mantiene .env como valor predeterminado; scripts/setup.sh/update.sh/backup.sh previos no se cambian. Secretos generados en deploy/environments quedan fuera de Git y del contexto Docker. No hubo modificaciones visuales ni de lógica de negocio o migraciones.
- Docker Engine y PostgreSQL no están disponibles aquí: el contrato de comandos está probado, pero construir/arrancar los tres perfiles, salud real, persistencia y concurrencia quedan para el laboratorio. No se montaron servicios en el servidor de José, no se copiaron datos de pruebas a producción y no se cambió su instalación actual.
