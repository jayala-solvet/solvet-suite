# Desarrollo con IA y control de cambios

## Contexto mínimo por tarea

Leer `AGENTS.md`, [índice](README.md), [contexto](contexto-proyecto.md), arquitectura, diseño y la especificación del módulo afectado. Para acceso/datos/despliegue, agregar seguridad, entornos y operación. No es necesario cargar todos los documentos del repositorio en cada prompt: usar el índice y los contratos pertinentes.

La IA debe revisar versiones reales, cambios locales y consumidores del módulo antes de editar. No inventar servicios existentes, permisos, estados o funciones. Cuando la implementación difiera del documento, señalarlo y resolver la diferencia en el mismo cambio; no tratar una propuesta como funcionalidad instalada.

## Unidad de trabajo

Una función o arreglo verificable por rama. Usar [plantilla](plantillas/funcionalidad.md) para funciones nuevas; para un error puntual basta indicar reproducción, resultado esperado, contratos afectados y regresión. La especificación describe qué hace, valida, calcula y guarda, y la maqueta cómo se usa.

Los cambios de regla comercial o decisiones abiertas que alteren datos/permisos necesitan resolución explícita; los detalles rutinarios y correcciones ya autorizadas se resuelven dentro del alcance. No imponer aprobación nueva para cada paso técnico.

## Repositorio y compatibilidad

Repositorio existente: `jayala-solvet/solvet-suite`. `main` contiene cambios integrados y revisados; ramas cortas por función/arreglo/documentación y PR con resultado, comprobaciones y límites. No sobrescribir una rama compartida ni reescribir historial remoto para arreglos rutinarios.

Antes de tocar una función estable, identificar los servicios, recursos, rutas, PDFs, pruebas y módulos que la usan. Declarar qué contrato cambia y qué se conserva. Una interfaz nueva reutiliza el mismo servicio; no agrega otro cálculo o flujo de emisión.

- Migraciones aditivas y compatibles. No modificar las ya aplicadas ni ejecutar `migrate:fresh`, `db:wipe` o `down -v` sobre la instalación de José.
- No renombrar columnas/estados/contratos sin transición. Campos nuevos en snapshots requieren lectura compatible con documentos anteriores.
- Conservar datos, archivos y APP_KEY. El rediseño no reconstruye presupuestos emitidos.
- Dependencias bloqueadas: `composer install`, no update global. Justificar cada nueva dependencia y cambios de arquitectura en ADR.
- No incluir `.env`, datos reales, SQLite local, PDFs privados, backups, credenciales ni vendor en Git o contexto compartido.
- Cambios de stack, aislamiento multiempresa, autenticación o numeración no son refactors incidentales.

Configuración de protección de `main`/revisiones obligatorias/CI en GitHub es una tarea administrativa pendiente de verificar; este documento no afirma que esté aplicada. Los comandos manuales y evidencias no equivalen a un pipeline activo.

## Flujo de una entrega

1. Inspeccionar estado del repo, especificación y dependencias. Definir alcance y casos de aceptación; diseñar frames si cambia la experiencia.
2. Implementar una porción completa: permisos, validación, servicio, datos, interfaz y errores aplicables, con compatibilidad del resto.
3. Ejecutar pruebas pertinentes. Para lógica de negocio/permisos, incluir positivos y negativos significativos; no tests que copian cada línea. Cambios solo documentales se verifican por coherencia, enlaces y diff, sin atribuir pruebas de ejecución nuevas.
4. Revisar UI real en claro/oscuro y móvil; si cambia PDF, renderizarlo e inspeccionar páginas. La maqueta no sustituye esta comprobación.
5. Revisar diff, secretos, migraciones, dependencias y efectos. Actualizar especificación/CHANGELOG/validación y ADR cuando corresponda.
6. Publicar PR y versión identificable. Probar instalación/actualización en entorno aislado antes de promover cambios de operación. Desplegar solo al entorno dentro del alcance autorizado.
7. Informar qué funciona, qué se probó y qué queda pendiente. No afirmar acceso al servidor, configuración WAF o prueba Docker sin haberla realizado.

## Verificación técnica

```bash
bash scripts/test.sh
```

El script usa un proyecto Docker de pruebas separado. `phpunit.xml` y `tests/bootstrap.php` fuerzan SQLite en memoria incluso si el proceso recibe variables de PostgreSQL. Esto protege el destino de pruebas, pero no verifica bloqueos concurrentes de PostgreSQL.

Con PHP/dependencias instalados:

```bash
php vendor/bin/phpunit
vendor/bin/pint --test
composer validate --strict
git diff --check
```

Aplicar según el tipo de cambio. Para esquema, emisión, despliegue o proxies, comprobar además PostgreSQL, concurrencia o cadena HTTPS real según corresponda. Pruebas de regresión existentes deben seguir pasando; agregar casos cuando se cambia una regla o se corrige un fallo material.

## Desarrollo Docker y assets

Para nuevos checkouts, preferir los perfiles y comandos de [entornos separados](entornos-desarrollo-pruebas-produccion.md). `bash scripts/environment.sh testing test` ejecuta PHPUnit en un proyecto e imagen de pruebas independientes; `bash tests/Infrastructure/environments.sh` verifica el contrato de los scripts con un Docker simulado, sin tocar bases instaladas.

```bash
docker compose -f compose.yml -f compose.dev.yml up -d --build
```

Los montajes de desarrollo no implican que todos los archivos de la imagen se actualicen. Dependencias, Dockerfile, providers o archivos fuera de montajes requieren reconstrucción. Tras modificar la hoja de diseño:

```bash
docker compose exec app php artisan filament:assets
```

Nginx sirve assets copiados al construir `web`: publicar dentro de `app` no actualiza ese filesystem. Reconstruir también `web` para probar la imagen de entrega. Conservar el hash de CSS en URL.

## Incorporar módulos sin duplicar el núcleo

Definir entidad, relaciones, estados, permisos, campos/calculador, auditoría, efectos y errores en `docs/modulos/<nombre>.md`. Reutilizar Users, Company, Customer y componentes. Operaciones de negocio en `app/Modules/<Modulo>/Services`; no escribir directamente en tablas de otro módulo para evitar su contrato.

Las integraciones tendrán adaptador propio, permisos y credenciales mínimas, tiempos de espera, reintentos e idempotencia. Introducir cola/worker cuando exista un trabajo asíncrono real y operarlo explícitamente; tener tablas `jobs` no significa que un worker esté ejecutándose.

Si aumenta el volumen, medir consultas, tiempos y consumo, corregir N+1/índices/paginación; luego evaluar cola o escalado. No introducir microservicios antes de tener evidencia. La venta a otra empresa usa la misma base de código y configuración por instalación; no una rama permanente por cliente.

## Prompt de continuidad

```text
Trabajá sobre el repositorio Solvet Suite existente.
Leé AGENTS.md, docs/README.md y los documentos pertinentes a la tarea.
Funcionalidad/ID: [ID y especificación].
Objetivo observable: [resultado].
Maqueta: [frame y versión o sin cambio visual].
Alcance autorizado: [módulo y entorno].
Casos de aceptación: [casos y límites].
Conservá los contratos de presupuestos emitidos, permisos y diseño compartido.
Reutilizá los servicios y componentes actuales; no cambies stack ni dependencias
sin justificación. Revisá los consumidores y datos existentes antes de editar.
Implementá una porción completa, verificá casos relevantes y regresión,
actualizá documentación y entregá diff/PR con evidencia y pendientes reales.
```
