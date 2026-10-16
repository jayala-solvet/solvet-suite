# Desarrollo, testing y producción

Estado: configuración y comandos implementados. No crea entornos en servidores automáticamente. Requisitos: Linux/Bash, Docker Engine + Compose v2, OpenSSL y Git. Se mantiene la instalación anterior basada en .env sin renombrarla ni modificar sus volúmenes.

## Entornos recomendados

| Entorno | Uso | Proyecto Compose | Puerto inicial | APP_ENV |
|---|---|---|---|---|
| development | Modificar código, montajes y dependencias de desarrollo | solvet-suite-development | 8089 | local |
| testing | Carga de datos ficticios, aceptación y ensayo de actualización | solvet-suite-testing | 8090 | staging |
| production | Movimientos reales y documentos conservados | solvet-suite-production | 8091 | production |

El puerto 8088 queda libre de cambios para la instalación actual. Los nuevos puertos vienen enlazados a 127.0.0.1. Para acceso LAN, configurar BIND_ADDRESS con la IP LAN del servidor y APP_URL con esa IP/puerto, restringiendo la red autorizada. La aplicación permanece en /app. Para publicación HTTPS aplicar además [proxy y seguridad](entornos-publicacion.md); seleccionar production no configura TLS/Cloudflare.

Tres proyectos Compose crean redes, PostgreSQL, PDFs, sesiones y volúmenes distintos. El nombre lógico de la base puede ser solvet en todos: son contenedores independientes. Cada entorno genera APP_KEY y contraseña de base propias, cookie propia y copia de respaldo separada. No cambiar proyecto, clave ni carpeta de volúmenes de una instalación con datos esperando que estos se trasladen solos.

testing usa APP_ENV=staging: es el laboratorio persistente del usuario. APP_ENV=testing queda reservado a PHPUnit con SQLite en memoria. development usa compose.dev.yml; testing/production usan imágenes sin montajes de código y sin dependencias de desarrollo.

## Repositorio y carpetas

Mantener una sola línea de producto: ramas cortas con PR hacia main. No crear ramas permanentes con código distinto para cada entorno. El ambiente depende de configuración privada; la versión desplegada se identifica por commit.

Usar carpetas/checkouts separados para evitar que un git pull del laboratorio cambie el checkout desde el que se construye producción:

- /opt/solvet-it/solvet-suite: instalación actual; conservar hasta decidir su destino.
- /opt/solvet-it/solvet-suite-testing: nuevo laboratorio.
- /opt/solvet-it/solvet-suite-production: producción nueva.
- Desarrollo puede ejecutarse en el equipo del desarrollador.

Una carpeta dedicada debe operar un único perfil. Los env generados viven en deploy/environments, ignorado por Git y excluido del contexto Docker. No se ejecutan con source ni se imprimen secretos. No copiar .env entre entornos.

## Montar testing ahora

```bash
cd /opt/solvet-it
git clone https://github.com/jayala-solvet/solvet-suite.git solvet-suite-testing
cd solvet-suite-testing
bash scripts/environment.sh testing init
nano deploy/environments/testing.env
```

Para el servidor informado, ajustar estas líneas, dejando las claves generadas intactas:

```dotenv
APP_URL=http://192.168.112.22:8090
APP_PORT=8090
BIND_ADDRESS=192.168.112.22
```

Luego:

```bash
bash scripts/environment.sh testing check
bash scripts/environment.sh testing setup
bash scripts/environment.sh testing artisan solvet:user
bash scripts/environment.sh testing status
```

Abrir http://192.168.112.22:8090/app. setup crea esquema y datos básicos del emisor, sin usuarios fijos ni operaciones demo. Completar la empresa antes de emitir. El demo solo se solicita explícitamente y exige base comercial vacía:

```bash
bash scripts/environment.sh testing artisan solvet:demo
```

También podés cargar tus pruebas a mano, incluido [compra a ejecución](tareas/compra-a-ejecucion.md). Cada instalación crea su propio usuario. No se comparte sesión entre perfiles. Preferir nombres DNS distintos al publicar; los puertos diferentes del mismo host comparten el ámbito de cookies, por eso se asigna SESSION_COOKIE exclusivo.

## Desarrollo

```bash
bash scripts/environment.sh development init
bash scripts/environment.sh development setup
bash scripts/environment.sh development artisan solvet:user
```

Puerto inicial 8089; montar código según compose.dev.yml. Cambios de dependencias/Dockerfile/providers fuera de montajes requieren reconstrucción. Cambiar CSS exige publicar assets y reconstruir web como indica [desarrollo](desarrollo.md). No utilizar este perfil para datos reales.

## Validar una versión

1. Integrar el cambio por PR y tomar el commit exacto a ensayar.
2. En el checkout de testing, traer main sin descartar cambios locales y ejecutar `bash scripts/environment.sh testing update`.
3. Comprobar status, /up, acceso, documentos previos, roles y datos persistentes. Registrar migraciones y casos A/B, reservas/devoluciones, ejecución, totales y PDFs.
4. Ejecutar suite automática separada: `bash tests/Infrastructure/environments.sh` y `bash scripts/environment.sh testing test`. Esta última construye imagen de desarrollo en un proyecto separado solvet-suite-testing-checks; no comparte imagen, almacenamiento ni base con testing y usa SQLite en memoria. No confundirla con una prueba de concurrencia PostgreSQL.
5. Consultar el commit instalado: `cat deploy/environments/testing.commit`. Conservar evidencia de la prueba junto con ese SHA. El marcador registra fin exitoso de setup/update; comprobar además la salud real.
6. Si falla, corregir en una rama y volver a validar. No promover la base de pruebas ni declarar producción verificada por un healthcheck.

## Primera producción

Clonar en otra carpeta; seleccionar con `git checkout --detach SHA_PROBADO` el SHA completo verificado en testing. Después:

```bash
bash scripts/environment.sh production init
nano deploy/environments/production.env
bash scripts/environment.sh production check
bash scripts/environment.sh production setup
bash scripts/environment.sh production artisan solvet:user
```

Puerto inicial 8091 para no chocar con la instalación existente. Ajustar APP_URL/BIND_ADDRESS según LAN o proxy. Por defecto HTTP de loopback tiene SESSION_SECURE_COOKIE=false; con HTTPS validado debe ser true. Producción exige APP_ENV=production y APP_DEBUG=false. El script bloquea comandos habituales de borrado/demo, pero no es una frontera de seguridad frente a un administrador con Docker/SQL.

Comenzar con datos de empresa, clientes/catálogo y saldos iniciales documentados. Decidir el correlativo real según [operación](operacion.md), sin reutilizar números ya emitidos. Nunca copiar presupuestos de ensayo como operaciones reales. Si la instalación actual ya contiene movimientos reales, mantenerla intacta: se requiere un traslado completo ensayado de base/archivos y APP_KEY, no copiar filas sueltas ni renombrar proyectos. Este cambio no hace ese traslado.

## Actualizaciones de producción

En su checkout, git fetch y checkout --detach al mismo SHA probado; `git status --short` debe estar limpio. Ejecutar:

```bash
bash scripts/environment.sh production update
bash scripts/environment.sh production status
```

Se construyen app/web, se pone en mantenimiento, se respalda base/archivos/env/commit, se migra, se recrean servicios y se limpia caché. Un fallo detiene el script sin levantar mantenimiento. No se ejecuta git pull ni un despliegue automático al integrar un PR. Se reconstruye desde el mismo código probado; no hay todavía registro de imágenes/digests ni promoción de un binario firmado.

Cada versión nueva migra la base real existente; no se reemplaza por un dump de testing. Backups por perfil en backups/<perfil>/<fecha-id>, con secretos y permisos restringidos. Copiar a un destino protegido fuera del servidor y ensayar restauración. Para respaldo manual consistente: artisan down, backup y artisan up del mismo perfil. Una reversión tras migraciones requiere plan de recuperación coherente de base/archivos/código, no solo cambiar una imagen.

## Comandos y límites

`environment.sh <perfil> init/setup/update/backup/status/check/test/stop/artisan ...`. check usa compose config --quiet para validar sin mostrar secretos; no publicar la salida de docker compose config sin --quiet. stop conserva volúmenes. No se ofrece reset automático ni borrado de pruebas.

Los scripts setup.sh/update.sh/backup.sh anteriores siguen usando .env y el proyecto anterior. No mezclarlos con los nuevos perfiles. El archivo compose.yml selecciona env_file con SOLVET_ENV_FILE, manteniendo .env como valor por defecto. El wrapper fija proyecto/archivos y limpia variables de interpolación heredadas que podrían redirigir el despliegue.

Puede haber tres bases PostgreSQL corriendo: dimensionar RAM/disco antes de habilitar los tres en el mismo host. Se puede comenzar solo con testing y producción. El aislamiento es de aplicación/volúmenes, no de máquina: para mayor separación de fallos, usar hosts distintos y control de acceso por operadores.

Referencias: [Compose: nombre de proyecto](https://docs.docker.com/compose/how-tos/project-name/) y [env-file/interpolación](https://docs.docker.com/compose/how-tos/environment-variables/variable-interpolation/), consultadas 16/10/2026.
