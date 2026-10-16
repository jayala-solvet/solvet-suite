# Seguridad y límites de autorización

## Modelo de v0.1

Una empresa emisora por instalación; todos los usuarios activos de esa instalación pueden consultar sus clientes, presupuestos y documentos. La separación entre empresas se hace por despliegue/base/volúmenes. No hay control por cartera individual de clientes ni por sucursal. No alojar empresas diferentes dentro de una sola base como si fueran clientes comerciales.

| Rol | Consulta/PDF/historial | Clientes/presupuestos | Datos del emisor |
|---|---|---|---|
| admin | Sí | Crear/editar/baja auditada de borrador/emitir/decisión y editar catálogo | Editar |
| commercial | Sí | Crear/editar/baja auditada de borrador/emitir/decisión y editar catálogo | No |
| reader | Sí | No | No |

Se comprueban permisos del servicio de presupuestos en servidor. El panel exige usuario activo. Las rutas PDF e historial requieren sesión y usuario activo. La creación de usuarios se hace por CLI del operador del servidor. No hay usuarios demo ni contraseña fija dentro del proyecto.

## Controles base

- Contraseñas con hash de Laravel, mínimo 12 caracteres en el comando de alta.
- Sesión y CSRF del framework, limitación de intentos de inicio de sesión de Filament.
- Secretos en `.env` fuera de Git e imagen. APP_KEY persistente; no regenerarla al actualizar.
- PDF privado, sin enlaces públicos permanentes ni symlink público al almacenamiento privado.
- Datos validados, salida Blade escapada y render de PDF sin recursos remotos habilitados por la aplicación.
- Integridad de correlativos mediante transacciones y restricciones.
- Logs sin credenciales ni payloads completos del entorno.

## Antes de uso comercial externo

Completar y probar: recuperación de acceso, MFA para administradores, gestión de usuarios/bajas, política de retención, respaldo fuera del servidor, restauración ensayada, revisión de dependencias/licencias y pruebas de concurrencia en PostgreSQL. Acordar responsables de actualizaciones y recuperación.

La auditoría comercial es una tabla de la misma base: un administrador de base puede alterarla. Si se exige evidencia resistente a manipulación, enviar registros a un destino externo con controles de retención. El hash almacenado no evita que alguien con acceso a base y archivos cambie ambos.

## Operación

En producción: HTTPS, `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, APP_URL correcto y proxy confiable acotado. No publicar PostgreSQL. Los respaldos contienen datos y secretos: permisos restringidos, cifrado en el destino y almacenamiento fuera del host. La muestra no configura automáticamente una tarea de backup.

Los datos fiscales y tasas incluidos son configuración técnica de ejemplo; la adaptación a documentos legales/fiscales de cada país corresponde a un módulo posterior validado.

## Contexto de laboratorio y publicación prevista

El laboratorio informado por José es `http://192.168.112.22:8088/`. La publicación futura mediante Nginx y Cloudflare se especifica en [entornos y publicación](entornos-publicacion.md), con controles existentes, brechas y criterios para promover. No se modificó configuración de red/proxy desde esa documentación.

La aplicación ya define `America/Asuncion` como zona de negocio. Revisar correctamente fechas/hoy al agregar vencimientos programados. Proxy confiable, validación de host y restricciones del origen aún requieren implementación/validación en la cadena real. APP_URL y un WAF no habilitan esos controles por sí solos.

## Reglas para cada nueva funcionalidad

Declarar permisos, alcance por registro y datos controlados por servidor en la [plantilla](plantillas/funcionalidad.md). Comprobar acceso directo/Livewire y puntos de entrada nuevos, no solo visibilidad. Toda integración usa credenciales acotadas y evita URLs arbitrarias o ejecución de comandos derivados de datos del usuario.

Si se incorpora carga de archivos, definir límites de contenido/tamaño, nombres generados, almacenamiento privado, validación de descarga y tratamiento de archivos activos. Actualmente no hay carga de evidencia adjunta en presupuestos: solo texto. Si se incorpora API/portal, especificar autenticación, alcance de tokens, expiración, revocación y límites; no publicar automáticamente los recursos internos.

El historial de clientes/emisor, control de versiones concurrentes de borradores, límites específicos de generación PDF y verificación periódica de integridad son pendientes, no capacidades instaladas. Priorizarlos por riesgo y operación, con pruebas propias; no suponer que el documento de contexto evita esos problemas.

## Costos internos

Admin/commercial activos consultan y administran costos (`view-costs`, `manage-costs`). Reader no accede a páginas, formulario ni estimaciones de utilidad; su consulta de dashboard no carga el JSON privado. cost_snapshot se oculta en serialización y no forma parte de líneas/snapshot/PDF comercial. Crear versiones exige autorización de servicio y pertenencia a catálogo/moneda. No se incorporan cargas de comprobantes ni validación tributaria externa.
