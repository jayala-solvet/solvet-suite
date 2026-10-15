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
