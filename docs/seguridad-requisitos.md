# Requisitos persistentes de seguridad · Solvet Suite

Referencia: OWASP ASVS **5.0.0**, publicada en mayo de 2025. [Proyecto oficial](https://github.com/OWASP/ASVS) y [versión estable](https://github.com/OWASP/ASVS/releases/tag/v5.0.0_release). Es una selección aplicable al monolito y su evidencia; no una declaración de verificación integral o certificación ASVS. Los IDs SEC siguientes pertenecen al proyecto; no se presentan como numeración literal de ASVS.

| ID | Área ASVS | Regla exigida | Evidencia y límite |
|---|---|---|---|
| SEC-01 | Autenticación | Cuenta individual, hash adaptativo, recuperación temporal, MFA admin, fuerza bruta | Filament/Broker/Hash; AccessSecurityTest; SMTP real pendiente |
| SEC-02 | Sesiones | Cookie segura bajo HTTPS, CSRF, vencimiento e invalidación | EnsureActiveUser, Laravel; comprobar cadena real/cookies |
| SEC-03 | Autorización | Denegar por defecto, permisos por acción/registro, sin confiar en UI | AccessRole, Gates, servicios, filtros técnicos; pruebas de URL/ID |
| SEC-04 | Entradas y negocio | Campos permitidos, cálculo de servidor, transacciones e idempotencia | Servicios de Quotes/Costs/Operations; no cambiar emitidos |
| SEC-05 | Datos/archivos | Datos mínimos, cifrado de secretos, documentos privados, descarga autorizada | MFA/cola cifrados, PDF privado, age opcional; disco/backup externo pendiente |
| SEC-06 | Configuración | Sin secretos en código/imagen/logs, mínimo privilegio y entornos separados | Exclusiones Docker/Git, roles PG nuevos; historia/entorno revisar y rotar si expuestos |
| SEC-07 | Comunicación | HTTPS/origen restringido/proxy explícito, no caché compartida | Middleware/template Nginx; evidencia real obligatoria |
| SEC-08 | Auditoría | Actor/acción/fecha/resultado proporcional, sin tokens, alertas y retención | AuditEvent, notificaciones internas; base admin puede alterar evidencia |
| SEC-09 | Dependencias | Instalar lock, auditar sin actualización global incidental | composer audit --locked; repetir por versión |
| SEC-10 | Privacidad | Finalidad/fundamento/versiones separados, consentimiento específico cuando aplica | LegalService, textos sin publicar automáticamente; revisión jurídica pendiente |

## Reglas para cambios futuros

- Cada endpoint, acción Livewire, descarga o integración declara autenticación, permiso, alcance por registro y campos aceptados. Un menú oculto no autoriza ni protege el servidor.
- Un ID, estado, rol, empresa, costo, total, actor o aprobación recibidos del cliente no son confiables. Validar pertenencia, permisos y reglas en el servicio de cada módulo.
- Nuevos datos personales/salarios/documentos requieren clasificación, finalidad, acceso, conservación y protección antes de agregarse. No conceder automáticamente a perfiles nuevos acceso a todos los maestros.
- No agregar RLS, claves de navegador o multiempresa como etiquetas sin un modelo real. Actualmente solo PHP accede a PostgreSQL; cada empresa tiene despliegue/base/secretos propios. Si se adopta acceso directo o tenants compartidos, se exige ADR y pruebas negativas de aislamiento.
- Nuevos uploads: límites de contenido/tamaño, nombres generados, almacenamiento privado, control de descarga y de contenido activo. No existe carga de adjuntos de usuario en esta entrega.
- APIs futuras requieren tokens acotados, revocación, expiración, límites y autorización de registro; no habilitar CORS comodín o registro público por conveniencia. Hoy no hay API pública ni claves públicas de base.
- Contraseñas/secretos/tokens no entran en auditoría, logs, URLs ordinarias o localStorage. El enlace de recuperación nativo es la excepción temporal: token de un uso, no-referrer y logs sin query string; no usarlo como autenticación permanente.
- No recuperar contraseñas en claro: se restablecen. La contraseña de prueba compartida en el chat no es una cuenta de producción; retirarla antes de operar datos reales.
- Las credenciales expuestas se revocan/rotan en el proveedor. Borrarlas del archivo actual no sanea historial, backups o copias ajenas. APP_KEY requiere plan de recifrado; nunca regenerarla rutinariamente.
- Los consentimientos opcionales deben comprobarse en el consumidor antes de ejecutar la finalidad. Añadir una casilla o tabla no bloquea un SDK de terceros por sí solo.
- CSP, HSTS y políticas WAF se prueban con Filament/Livewire. No instalar cabeceras genéricas que rompan el sistema ni confiar en Cloudflare para suplir permisos internos.

## Respuesta a incidentes

1. Registrar hecho, hora, alcance e identificadores; no copiar credenciales o datos personales innecesarios.
2. Contener: suspender cuentas/revocar sesiones, bloquear origen confirmado cuando proceda; conservar evidencia y no borrar movimientos.
3. Determinar exposición real, puntos de entrada, versiones, backups y sistemas afectados; coordinar con responsables técnicos/jurídicos.
4. Rotar credenciales comprometidas con plan, corregir y probar antes de recuperar servicio. Evaluar notificaciones bajo el régimen aplicable, sin inventar plazos legales.
5. Restaurar solo en destino confirmado y ensayado, verificar integridad/accesos y documentar cierre/acciones preventivas. Definir responsables y canal externo de escalamiento antes de publicación.
