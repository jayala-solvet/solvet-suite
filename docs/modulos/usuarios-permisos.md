# Usuarios, roles y acceso · ACC-01 a ACC-08

## Arquitectura y compatibilidad

Se conserva users.role como clave del rol; los valores admin/commercial/reader existentes reciben las capacidades anteriores. La migración 000005 agrega access_roles, suspensión, baja lógica, versión de sesión, secretos MFA cifrados, asignación de pedidos, notificaciones y documentos/eventos legales. No modifica datos comerciales, precios, archivos emitidos, saldos ni secuencias. No hay usuarios o contraseñas de fábrica.

Los permisos están enumerados en Permissions::all y se guardan en access_roles.permissions. Un permiso desconocido o no otorgado se deniega. No existen excepciones directas por usuario en esta entrega: se utiliza otro rol con justificación. No se agregan módulos RRHH/salarios por el hecho de crear ese perfil.

## Matriz inicial

| Perfil | Acceso inicial |
|---|---|
| Administrador | Administración, permisos, legal y operación; MFA obligatorio; rol protegido |
| Comercial | Catálogo, clientes, presupuestos, costos, compras, inventario y pedidos; sin administración de usuarios/legal/empresa |
| Consulta | Lectura comercial, PDF e inventario/cumplimiento, sin costos/márgenes ni escritura |
| Administración / contabilidad | Lectura comercial, costos y compras; sin modificar o aprobar por defecto |
| Recursos humanos | Cuenta y documentos personales; sin datos comerciales ni módulos futuros |
| Técnico | Solo pedidos asignados, cantidades y cumplimiento; sin precios, costos, margen o acceso al presupuesto |

La matriz editable muestra únicamente acciones soportadas. Consultar compras implica ver importes del documento de proveedor. Consultar costos es una autorización financiera separada. Para escribir/aprobar/exportar debe otorgarse también consultar el módulo. El rol administrador no se modifica, elimina o renombra; los demás roles pueden configurarse. Ningún administrador delegado puede conceder permisos que no tiene ni administrar cuentas admin si no es admin.

## ACC-01 · Alta y edición

Administración → Usuarios. Solo users.manage. Nombre, correo normalizado, rol, estado activo/suspendido/baja y motivo mínimo de diez caracteres. Se exige la contraseña actual del operador, con límite de intentos. El servicio acepta una lista explícita: no se acepta contraseña del usuario nuevo, session_version ni secretos MFA del formulario. La nueva contraseña es aleatoria y no se presenta: enviar invitación por correo con enlace de recuperación. No se crean cuentas compartidas.

Edición y reactivación conservan IDs, referencias y auditoría. Baja lógica no borra presupuestos ni movimientos de ese usuario. Se comprueba bajo transacción/bloqueo del rol admin que quede al menos un administrador activo. No hay borrado físico web ni restablecimiento de MFA ajeno desde este formulario.

## ACC-02 · Roles

Administración → Roles y permisos. Nombre, clave inicial, lista de permisos, justificación y reautenticación. Claves existentes no se cambian. Un cambio revoca sesiones y enlaces pendientes de los miembros. Las asignaciones se validan en servidor: modificar un ID o una propiedad Livewire no concede derechos.

## ACC-03 · Invitación / recuperación

En Usuarios → Enviar invitación / recuperación. Usa el broker nativo: token aleatorio almacenado como hash, válido 60 minutos y de un solo uso. No se envía contraseña. La recuperación pública responde igual para cuenta existente, inexistente, inactiva o limitada por intentos. También se limita por dirección y origen. Correo log/array no emite enlaces en logs.

Las notificaciones de acceso se cifran en la cola security-mail con APP_KEY. El servicio worker de Compose procesa esa cola. Requiere SMTP real, APP_URL correcto y continuidad del worker; la configuración no prueba entrega externa. Resetear contraseña no quita MFA. Si se pierde el factor, usar un código de recuperación de un solo uso; conservarlos fuera del sistema. Para recuperación administrativa extraordinaria, verificar identidad y documentar el procedimiento del operador, sin publicar secretos.

## ACC-04 · MFA

Proveedor TOTP y recuperación nativos de Filament. Administradores deben completar la configuración antes de acceder a módulos; el middleware se registra de forma estática y decide el requisito por cuenta en cada solicitud. Las demás cuentas pueden configurarlo. Un factor configurado siempre se exige al iniciar sesión. Ventana TOTP de un intervalo adyacente; protección de reutilización/limitación nativas. Secretos y códigos de recuperación están cifrados y ocultos en serialización; no son texto plano en base ni en auditoría.

## ACC-05 · Sesiones

Cookie HttpOnly/SameSite, Secure al publicar HTTPS; CSRF nativo. Se conserva la duración por inactividad de Laravel y se agrega máximo absoluto de ocho horas configurable por SECURITY_SESSION_MAX_MINUTES. Login registra inicio/versión; middleware persistente comprueba estado y versión actual contra base. Cambiar contraseña, correo, rol, estado o factores revoca sesiones/remember tokens y enlaces pendientes. En autogestión del factor se conserva únicamente la sesión que hizo la configuración; las otras se revocan. Cambios de contraseña exigen volver a iniciar sesión.

## ACC-06 · Registros y campos

Presupuestos/maestros son compartidos entre roles autorizados de una instalación; no hay cartera privada o tenants mezclados. Pedidos técnicos usan assigned_to y consulta filtrada; acceso directo a un pedido ajeno no resuelve el registro. Asignar técnico exige orders.update, sin aceptar el propietario desde presupuestos. Técnico consulta cumplimiento; el costo de sus horas lo registra un usuario financiero autorizado. Toda ampliación de captura técnica debe separar horas del costo unitario.

Servicio de presupuesto diferencia crear/modificar/eliminar/aprobar, filtra campos y calcula importes en servidor. Quien no ve costos no selecciona costos internos; la estimación se conserva/calcula internamente sin exponer sus valores. PDF/historial tienen permisos explícitos. No hay API pública ni acceso de navegador a PostgreSQL: RLS y una supuesta clave pública de base no son el mecanismo de este monolito.

## ACC-07 · Auditoría y alertas

Usuarios, roles, sesiones, login/fallos/logout, exportaciones, asignaciones y versiones/eventos legales registran acción, actor cuando existe, recurso y fecha. No se copian contraseñas, tokens, secretos MFA o payloads completos. La aplicación bloquea edición/borrado de auditoría; un administrador de base sigue pudiendo alterarla. Umbral de diez fallos por origen en quince minutos produce alerta interna para admins y log saneado, con correlación HMAC y sin IP/correo crudos. No equivale a un SOC o canal externo de alertas.

## ACC-08 · Pruebas

AccessSecurityTest cubre roles, accesos directos, alcance técnico, campos inyectados, reautenticación, último admin, suspensiones/reactivación/bajas, revocación, MFA/TOTP real, tokens/reutilización/vencimiento, cola cifrada, alertas, cabeceras/sesión y evidencia legal. Pruebas de dominio usan administradores ficticios ya enrolados; no deshabilitan MFA de producción. Los bloqueos concurrentes deben verificarse en PostgreSQL antes de publicación.

### Errores de confirmación

Alta y edición vinculan errores del servicio a data.<campo>. La contraseña de confirmación se vacía tras un error para que no viaje de vuelta en el estado de respuesta. Después de cinco fallos se conserva la espera de cinco minutos y se muestra el tiempo restante junto al campo. No se deshabilita la reautenticación ni se crea la cuenta cuando falla.
