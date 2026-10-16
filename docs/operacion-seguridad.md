# Instalar y operar la ampliación de seguridad

## Actualización en testing

Respaldar y actualizar primero el checkout de pruebas con main, usando su perfil:

```bash
bash scripts/environment.sh testing update
bash scripts/environment.sh testing artisan migrate:status
bash scripts/environment.sh testing artisan solvet:security-check
```

La migración 000005 es aditiva. El administrador actual mantiene el rol y debe enrolar MFA al entrar. Preparar una app TOTP y guardar los códigos de recuperación fuera del sistema. Las cuentas nuevas se gestionan desde Usuarios; para la primera cuenta administrativa sigue disponible solvet:user por CLI. No se cambia automáticamente ninguna contraseña del servidor.

El servicio worker se incorpora al levantar/recrear servicios. `update` recrea app/web/worker. En Docker, comprobar `docker compose ps` usando el archivo/proyecto del perfil o los comandos del operador equivalentes. Un worker ejecutándose no prueba entrega SMTP. Antes de una actualización, dejar que terminen los jobs activos; los jobs de acceso tienen un solo uso/vencimiento aunque el envío se reintente.

## Correo real

Configurar en el env privado del perfil: MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS/NAME y la URL canónica. Usar el esquema TLS que indique el proveedor mediante MAIL_SCHEME si procede; verificar las opciones de config/mail.php y no asumir STARTTLS implícito sin comprobarlo. No copiar credenciales en Git o capturas.

La solicitud de recuperación usa el broker; Invitación pide contraseña actual del operador. Ambos encolan AccessLink cifrado en security-mail, procesado por worker. Probar con un destinatario de prueba autorizado, revisar recepción, URL y vencimiento. Si el mailer es log/array no se generan enlaces en logs; la respuesta pública sigue siendo genérica. Revisar jobs fallidos sin publicar payloads o excepciones privadas. No habilitar MAIL_MAILER=log para inspeccionar un enlace de acceso real.

El usuario indicó que tiene dominio/SMTP definidos, pero no aportó sus valores ni secretos en esta tarea. Por eso no se configuran remotamente ni se afirma entrega real.

## Proxy y publicación

TRUSTED_HOSTS: lista de hostnames exactos, sin esquema ni puerto. Contemplar 127.0.0.1/localhost para los healthchecks solo si se utilizan. TRUSTED_PROXIES: IPs/CIDRs de los intermediarios que Laravel recibe realmente; nunca *. Los valores vacíos conservan compatibilidad del laboratorio, pero no satisfacen publicación segura.

La plantilla `docker/examples/nginx-publication.conf.example` requiere dominio/certificados, log_format privado en http, redes y upstream reales. No se aplica automáticamente. El proxy sobrescribe Host/protocolo/puerto y la cadena de IP, sin confiar en valores del cliente. Si se usa real_ip de Cloudflare, confiar exclusivamente en redes oficiales verificadas y cerrar acceso alternativo; no aceptar CF-Connecting-IP de cualquiera. Confirmar REMOTE_ADDR recibido por PHP, teniendo en cuenta puente Docker/NAT. No configurar confianza de toda la LAN por comodidad.

HTTPS navegador/Cloudflare/origen con certificado válido y Full strict. Bypass de caché compartida en todo lo autenticado y Livewire/PDF. Se añaden no-store/no-referrer en aplicación y logs Nginx internos sin query strings. Depuración desactivada; cookies Secure bajo HTTPS, HttpOnly/SameSite. No se activan CSP estricta/HSTS preload hasta ensayar el frontend y el dominio. No se expone PostgreSQL ni PHP-FPM.

## Privilegios PostgreSQL

Nuevos perfiles inicializados usan solvet_app para PHP y una contraseña administrativa separada para el clúster. El script postgres-init.sh crea un rol sin superusuario, crear bases/roles, replicación o bypass RLS, con CONNECT y uso/creación en el esquema de esta aplicación para ejecutar migraciones. La contraseña administrativa se enmascara en el entorno de app/worker; solo el contenedor de base la necesita.

El script de PostgreSQL se ejecuta únicamente al inicializar un volumen vacío. Volúmenes anteriores y el .env legado mantienen su usuario/clave. Si usan solvet como superusuario, planificar en una copia un rol de aplicación, permisos sobre tablas/secuencias/esquema ya existentes y nuevas credenciales. No basta cambiar DB_USERNAME en un volumen anterior ni eliminar datos para disparar el inicializador. Este cambio no ejecuta esa migración administrativa sobre el servidor.

## Cifrado de respaldo y restauración

Se admite `BACKUP_AGE_RECIPIENT` en el env privado para el destinatario público de age. age es una herramienta externa de host, no dependencia PHP: se elige por cifrado autenticado y recuperación con una identidad privada que puede guardarse fuera del servidor. Licencia BSD-3-Clause; alternativa es cifrado de volumen/gestor de backups del operador, pero debe cubrir también la copia que salga del host.

Instalar age según el sistema y crear/custodiar la identidad en un equipo seguro. Copiar al servidor solo el destinatario público age1..., nunca la identidad privada. `environment.sh ... backup` y backup.sh cifran el conjunto de base/documentos/env/metadatos; eliminan únicamente sus archivos temporales en claro tras el éxito. Ante fallo conservan esos archivos con permisos restringidos y la actualización sigue en mantenimiento. Esto no es borrado seguro de bloques de disco: cifrar también el volumen del host.

Para recuperar, en un destino aislado autorizado:

```bash
age --decrypt -i /RUTA/PRIVADA/identity.txt backup.tar.gz.age > backup.tar.gz
```

Extraer en carpeta restringida y seguir la restauración de [operación](operacion.md). No usar una identidad privada del servidor en Git ni en la imagen. La prueba de cifrado local con datos ficticios no sustituye probar restauración PostgreSQL y documentos completos con la copia real. Configurar destino externo, retención y responsables; no se instala cron ni transferencia automática.

Prueba reproducible opcional con age en PATH:

```bash
bash tests/Infrastructure/encrypted-backup.sh
```

## Criterio de publicación

Ejecutar `solvet:security-check --public` en producción. Revisar además [hallazgos](seguridad-validacion.md): el comando no prueba red, cifrado de disco, SMTP, restauración o WAF. Completar/revisar los borradores legales y publicarlos explícitamente desde Versiones legales. No usar credenciales de prueba ni presentar el sistema como certificado o sin vulnerabilidades.

## Corrección de notificaciones PostgreSQL y MFA de testing

La migración 000006 convierte notifications.data de text a jsonb mediante un cast, conservando las notificaciones. Filament filtra data->format: PostgreSQL requiere JSON nativo. No editar la migración 000005 ya aplicada. Un JSON inválido detiene la migración sin borrar registros; investigar antes de reintentar.

Para suspender temporalmente todo MFA en testing, agregar SECURITY_MFA_ENABLED=false a deploy/environments/testing.env y ejecutar el update del perfil. Se omiten proveedor, desafío y exigencia de inscripción, sin borrar secretos ni códigos existentes. Usuario/contraseña, permisos y sesiones siguen vigentes. Para reactivar, establecer true y actualizar; el valor predeterminado es true. La opción aplica a la instalación: no usarla en producción por accidente. El diagnóstico de seguridad seguirá señalando administradores sin segundo factor.

Validación local de esta corrección: PHPUnit con SQLite; la conversión SQL requiere comprobarse en PostgreSQL de testing. No se ejecutó contra el servidor del usuario.
