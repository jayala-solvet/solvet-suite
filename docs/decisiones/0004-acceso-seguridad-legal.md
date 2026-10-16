# ADR 0004 · Acceso, seguridad y versiones legales

Fecha: 16/10/2026. Estado: implementado para prueba; publicación segura condicionada a evidencia de destino y aprobación jurídica.

Mantener Laravel/Filament y sesión de servidor. No añadir un proveedor de identidad, un motor de permisos externo o RLS de navegador a una arquitectura sin ese acceso directo. Las capacidades se enumeran y se asignan mediante roles, preservando las claves legacy. Un rol técnico tiene alcance por asignación de pedido; no crear un tenant por cliente comercial. Las futuras excepciones individuales requieren contrato propio, justificación y auditoría.

Usar MFA/recuperación de Filament y broker de Laravel, con Argon2id y aceptación de hashes bcrypt anteriores para actualización gradual. La obligación de enrolamiento se aplica en middleware por usuario; su ruta se registra siempre para evitar decisiones tomadas cuando todavía no hay sesión. Mantener APP_KEY, cifrar secretos de factor y notificaciones encoladas, invalidar sesiones por versión y cambios sensibles. Un worker de seguridad evita que el envío de correo bloquee el login; la entrega real depende de SMTP y operación de la cola.

La evidencia legal se versiona como texto publicado inmutable y eventos asociados. La publicación es una acción humana reautenticada con identidad completa y referencia de validación jurídica. Separar contrato, aviso y finalidad opcional; el sistema no inventa identidades ni fundamentos legales. Las plantillas no son políticas aprobadas automáticamente.

Nuevas instalaciones pueden usar un rol PostgreSQL de aplicación sin privilegios administrativos. Las existentes no se reconfiguran a ciegas. Cifrado de respaldo usa age opcional en host (BSD-3-Clause), sin dependencia Composer; custodia privada y cifrado de volumen pertenecen a operación. Los requisitos de proxy/TLS/retención/recuperación son verificables y se registran como pendientes cuando falta evidencia.

No se agregan microservicios ni dependencias PHP nuevas. La separación por empresa sigue siendo despliegue/base/archivos. Una prueba local no es una auditoría exhaustiva de servidor ni certificación ASVS/legal.
