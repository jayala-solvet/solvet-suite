# Diagnóstico y validación de seguridad · 16/10/2026

Alcance: revisión de código/contratos y pruebas locales sobre PHP 8.5, Laravel 13, Filament 5 y SQLite. No es un pentest del servidor ni una certificación; no se accedió a Nginx/Cloudflare, SMTP, volúmenes o datos reales.

## Diagnóstico inicial y correcciones

| Hallazgo | Estado tras el cambio |
|---|---|
| Tres roles rígidos y lectura comercial para cualquier cuenta activa | Matriz explícita por acción, perfiles nuevos sin acceso indiscriminado, filtros de pedidos asignados |
| Sin gestión web de cuentas ni revocación integral | Altas/edición/suspensión/baja/reactivación con servicio, reautenticación, auditoría y último admin protegido |
| Sin MFA y recuperación operativa | TOTP/recovery nativos, requisito admin probado, broker temporal; worker y cola cifrada configurados |
| Recuperación podía revelar resultados distintos o usar mail log | Respuesta genérica, límites, no emitir enlaces a logs; SMTP real pendiente |
| Cookies/caché/proxy dependían del despliegue sin apoyo de aplicación | No-store privado, no-referrer, proxies/hosts explícitos sin wildcard; plantilla de publicación |
| Cuenta PostgreSQL de aplicación igual al administrador inicial | Rol acotado en nuevos volúmenes/perfiles; instalaciones previas no se alteran automáticamente |
| Sin versiones legales/eventos | Borradores y publicación condicionada, texto/hash/copia/actor/fecha y aceptación/lectura/consentimiento/retiro separados |
| Backup sin cifrado integrado | Cifrado age opcional con clave pública, prueba de recuperación; clave/custodia/destino requieren configuración |

## Pendientes que bloquean declarar publicación segura

| Severidad | Pendiente / impacto | Acción requerida |
|---|---|---|
| Alta | Cadena HTTPS, host/proxy confiable y firewall del origen no probados | Configurar dominio/certificados/proxies exactos, Full strict, restringir origen, probar falsificación/cookies/cache desde fuera |
| Alta si existe en destino | Aplicación antigua puede seguir usando rol PostgreSQL superusuario | Verificar rol con security-check, crear/migrar permisos en copia y rotar credenciales; no cambiar usuario sobre volúmenes existentes sin preparación |
| Alta si no está resuelto | Datos/discos/backups reales sin cifrado/recuperación fuera de host | Cifrado de volumen y backup age con identidad privada externa, copia externa y restauración ensayada |
| Alta si quedan cuentas de ensayo | Contraseña de testing conocida podría reutilizarse en producción | Usuarios individuales, retirar cuentas ficticias y comprobar MFA admin |
| Media operativa | SMTP, entrega y worker no verificados contra proveedor real | Configurar variables privadas y comprobar invitación/recuperación/cola/errores sin exposición de enlaces |
| Pendiente de evidencia | Bloqueos de último admin/reservas/emisión en PostgreSQL | Ensayo concurrente y construcción Docker en laboratorio, luego promover SHA probado |
| Legal | Identidad/avisos/fundamentos/transferencias/conservación sin aprobación | Completar desde empresa y operación real, validación jurídica y publicar versiones explícitamente |
| Media | Auditoría aún en la misma base y alertas internas, retención externa no acordada | Definir canal, responsable, retención y copia resistente a alteración según necesidad |

No se encontró en el escaneo limitado de las treinta revisiones Git disponibles una coincidencia de claves privadas PEM/OpenSSH, tokens GitHub con prefijo conocido o identificadores AWS. Es una búsqueda por patrones y no prueba ausencia de todos los secretos. No se inspeccionaron secretos reales del servidor ni se rotaron claves remotamente. composer audit --locked informó cero advisories y cero paquetes abandonados en la ejecución local; repetir al promover cada versión.

## Aceptación mínima antes de producción

Ejecutar `solvet:security-check --public` en el entorno final y revisar cada pendiente, además de pruebas externas. El comando es diagnóstico, no configura servicios ni certifica los puntos de esta tabla. La evidencia exacta de pruebas locales se registra en [validación](validacion.md). Ver [requisitos persistentes](seguridad-requisitos.md), [módulo de acceso](modulos/usuarios-permisos.md) y [operación de seguridad](operacion-seguridad.md).
