# Desarrollo con IA

## Tarea útil

Cada tarea debe indicar resultado observable, casos de ejemplo, permisos, alcance de datos, criterio de aceptación y restricciones. Ejemplo: «Agregar revisión de un presupuesto emitido, conservando su PDF y número, con nueva revisión editable y trazabilidad al origen. No modificar el original».

Evitar pedir un ERP completo en una sola tarea. Trabajar por una porción funcional que se pueda ejecutar, revisar y guardar en Git.

## Flujo de Git

Mantener una rama principal revisada; ramas cortas por cambio, commits concretos y notas de versión. No incluir `.env`, SQLite local, PDFs privados, respaldos ni vendor. El ZIP no contiene un remoto configurado: crear el repositorio privado elegido y agregar un remoto propio. No se ha subido código a ningún servicio externo.

## Desarrollo Docker

```bash
docker compose -f compose.yml -f compose.dev.yml up -d --build
```

Se montan las carpetas de código y vistas para edición. Al cambiar clases, limpiar cachés si es necesario. Cambios de dependencias, Dockerfile, providers o archivos fuera de los montajes requieren reconstrucción. Para ver el CSS de Filament publicado tras editar tokens:

```bash
docker compose exec app php artisan filament:assets
```

Nginx sirve los assets copiados al construir su imagen. Por eso, después de modificar el CSS registrado en Filament, reconstruir también `web`; para iteración de estilo puede enlazarse la hoja fuente directamente en un render hook de desarrollo. No asumir que publicar dentro de `app` actualiza automáticamente el filesystem de `web`.

## Validación

```bash
bash scripts/test.sh
```

Pruebas con SQLite en memoria. Cubren cálculos/IVA/descuento, moneda, documento conservado, bloqueo de edición, permisos, decisiones con evidencia y render de páginas. Las pruebas de Livewire ejercitan persistencia real del formulario. Antes de publicar una versión, completar prueba de instalación Docker y flujos contra PostgreSQL; verificar correlativo con solicitudes simultáneas.

En un entorno PHP con dependencias:

```bash
php vendor/bin/phpunit
vendor/bin/pint
composer validate --strict
```

## Incorporar un módulo

1. Especificación en `docs/modulos/<nombre>.md` con estado propuesto.
2. Modelo de datos y migraciones; identificar dependencias con módulos existentes.
3. Servicio con validación/autorización y transacciones cuando se necesiten.
4. Interfaz que reutilice componentes y tokens.
5. Pruebas relevantes y revisión visual.
6. Actualizar roadmap y CHANGELOG; ADR si cambia una decisión estructural.

No agregar dependencias «por si acaso». Revisar compatibilidad/licencia/actualizaciones y mantener el lock. Cambios del framework deben seguir su guía oficial y una tarea separada.

El comando de pruebas usa un nombre de proyecto Docker separado para no sobrescribir la imagen de la instalación. `phpunit.xml` y `tests/bootstrap.php` fuerzan el entorno SQLite en memoria incluso si el proceso recibe variables de PostgreSQL.
