# FUN-XXX · Nombre de la funcionalidad

Estado: propuesta / definida / en desarrollo / implementada / validada. Fecha: AAAA-MM-DD.
Responsable de negocio: por definir. Módulo: por definir. Issue/PR: pendiente.
Maqueta: enlace al frame y versión/fecha, o «no requiere cambio visual».

Esta es una plantilla. No declara implementado ningún comportamiento. Reemplazar todos los pendientes aplicables antes de considerar la función definida.

## Resultado y alcance

Qué necesita conseguir el usuario, qué cambia respecto de la versión actual y qué queda fuera. Ejemplo observable de entrada y resultado. Identificar módulos consumidores y comportamiento que se debe conservar.

## Actores y permisos

| Rol / actor | Puede consultar | Puede ejecutar | Alcance de registros |
|---|---|---|---|
| Por definir | Sí/no | Operaciones concretas | Instalación, propietario u otro alcance definido |

Indicar validación de sesión/usuario activo y autorización en servidor para interfaz, rutas, Livewire, tareas o API. Definir respuesta ante falta de permisos y acceso directo por ID. No basar seguridad en ocultar botones.

## Datos de entrada y persistencia

| Campo | Tipo / unidad / longitud | Obligatorio / valor inicial | Validación y origen | Se guarda en / quién lo controla |
|---|---|---|---|---|
| Por definir | Texto, fecha, entero menor, decimal textual… | Por definir | Usuario/servidor; límites | Tabla.campo, snapshot, auditoría o transitorio |

Separar datos de formulario, derivados y de sistema. Especificar unicidad, relaciones, borrado, sensibilidad, retención y alcance de instalación. Actor, permisos, correlativo y totales son de servidor. Definir migraciones, índices y compatibilidad con registros ya existentes; no editar migraciones históricas.

## Reglas y cálculos

Fórmula, unidad, precisión, orden de operaciones, redondeo, moneda y al menos un ejemplo comprobable. Definir cero, límites y valores inválidos. Identificar el servicio responsable. No aceptar cálculos del navegador como autoridad ni usar `float` monetario. Aclarar si una fecha usa zona de negocio o UTC.

## Estados, transacciones y efectos

| Estado inicial | Acción | Condición / permiso | Estado final | Escrituras y efectos |
|---|---|---|---|---|
| Por definir | Por definir | Validaciones en servidor | Por definir | Tablas, archivos, auditoría, integración |

Definir doble clic, reintento, concurrencia, bloqueo/unique/idempotencia, fallos parciales y recuperación. Indicar lo irreversible, confirmación, si envía correo o crea archivos, y qué sucede si un servicio externo no responde. Ningún efecto externo debe ocurrir antes de tener el estado local necesario confirmado; diseñar cola/outbox si aplica.

## Interfaz y trazabilidad

Pantallas, filtros, ordenación, paginación, vacíos, carga, errores, cancelación, claro/oscuro y móvil. Detallar mensajes por validación y destino tras éxito. Definir evento, actor, fecha, referencia y metadatos mínimos; nunca incluir secretos. Indicar si los datos históricos son snapshot o relaciones vivas.

## Seguridad y operación

Amenazas concretas y controles: acceso a otro registro, manipulación de campos, archivos/URLs externas, inyección/salida, abuso de recursos, datos sensibles y auditoría. Para cargas, definir tipos por contenido, tamaño, almacenamiento privado y descarga autorizada. No agregar estos mecanismos si la función no maneja cargas.

Configuración nueva, secretos fuera de Git, worker/scheduler si se necesita, respaldos, monitoreo y plan de reversión compatible con datos. Marcar claramente pendiente frente a implementado.

## Criterios de aceptación

| ID | Dado / cuando | Resultado verificable | Evidencia |
|---|---|---|---|
| AC-01 | Camino principal | Estado, importe o salida esperada | Prueba automática / comprobación real |
| AC-02 | Datos inválidos | Rechazo sin escritura parcial | Prueba negativa |
| AC-03 | Rol sin permiso o ID ajeno | Acceso denegado en servidor | Prueba de autorización |
| AC-04 | Reintento o concurrencia aplicable | Sin duplicados / pérdida | Prueba apropiada al motor real |
| AC-05 | Regresión de un módulo consumidor | Comportamiento anterior válido | Prueba relevante |

## Decisiones y entrega

Preguntas que cambian negocio/seguridad/datos, decisión adoptada y responsable. Resumen de implementación, archivos principales, migración, pruebas ejecutadas, límites pendientes y referencia de versión. No dar por verificado lo que no se ejecutó.
