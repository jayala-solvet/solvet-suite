# Seguridad y límites de autorización

Estado actual y evidencia: [diagnóstico](seguridad-validacion.md). Reglas persistentes: [SEC-01…SEC-10](seguridad-requisitos.md). Operación y configuración: [guía](operacion-seguridad.md).

Una empresa por instalación con base/archivos/claves independientes. Clientes comerciales no son tenants. Autenticación Laravel/Filament, sesiones de servidor, CSRF y módulos privados; no hay registro público ni API de acceso directo a PostgreSQL. Roles y permisos explícitos, alcance técnico por assigned_to y autorización en servicios. El rol nuevo sin capacidades no accede por defecto a datos comerciales.

[ACC-01…ACC-08](modulos/usuarios-permisos.md) define altas, suspensión/baja/reactivación, último administrador, reautenticación, invitaciones/recuperación, MFA, sesiones y auditoría. [Legal y privacidad](legal-paraguay.md) separa avisos, contratos y finalidades opcionales.

Contraseñas nuevas con Argon2id; hashes bcrypt previos siguen verificables y se actualizan al iniciar sesión. Secretos/códigos MFA y cola de acceso cifrados con APP_KEY, sin valores en serialización/auditoría. Mantener claves y backups protegidos; no regenerar APP_KEY al actualizar. PDF y cálculos históricos se conservan. Costos/márgenes requieren permiso financiero y no aparecen en PDF comercial.

La aplicación agrega no-store/no-referrer y políticas básicas a respuestas sensibles. Proxies/hosts se configuran explícitamente; la confianza global se rechaza. El Nginx interno no registra query strings, referencias o tokens en access logs. Esas medidas no demuestran firewall, TLS, Full strict, caché del borde, cifrado del volumen o recuperación externa del servidor.

La auditoría es append-only desde modelos/UI y tiene alertas internas por fallos; un administrador de base puede alterarla. No hay SIEM externo o retención resistente a manipulación configurados. No hay adjuntos de usuario, módulos salariales o multiempresa que deban darse por implementados.

Antes de publicación revisar hallazgos altos de destino, roles DB previos, cuentas de ensayo, SMTP/worker, MFA, restauración y textos legales. `solvet:security-check --public` ayuda al diagnóstico pero no certifica seguridad o cumplimiento. No compartir .env, tokens, dumps o datos reales en Git/capturas.
