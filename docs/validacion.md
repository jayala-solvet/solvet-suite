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
