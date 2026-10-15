# Módulo Presupuestos

Estado: núcleo funcional implementado en v0.1.0. Revisiones, envío y portal pendientes.

## Entidades

- Quote: destinatario, creador, asunto, moneda, vigencia, líneas, condiciones, totales, estado, número, snapshot y archivo/hash.
- Líneas en JSON: descripción libre, cantidad de hasta 3 decimales, precio, descuento porcentual, tasa IVA, total e IVA incluido calculados. Un catálogo reutilizable se incorporará después sin alterar snapshots emitidos.
- document_sequences: una secuencia global por instalación, no reinicia por año en v0.1.
- AuditEvent: actor, acción, documento, metadatos y fecha.

## Ciclo

Borrador → Emitido → Aceptado / Rechazado / Anulado.

Borrador no tiene número comercial y puede editarse por admin/comercial. Emitir fija correlativo y contenido. Una decisión solo puede registrarse una vez desde Emitido. No se eliminan documentos en esta interfaz.

El vencimiento es fecha de vigencia; no hay un proceso que cambie estados automáticamente. Un borrador con fecha pasada no puede emitirse hasta corregirla. Aceptación vencida no tiene bloqueo automático en esta entrega; se acuerda esa regla antes del portal.

## Cálculo

- Monedas: PYG, sin decimales; USD, 2 decimales.
- Cantidad: 0.001 a 999999.999; precio no negativo hasta 9 dígitos enteros.
- Los precios incluyen IVA. Tasas disponibles 0, 5 y 10%; no se suma IVA nuevamente al total.
- Importe bruto de línea = cantidad × precio, redondeado a unidad menor.
- Descuento = bruto × porcentaje, redondeado a unidad menor.
- Neto = bruto − descuento.
- IVA incluido = neto × tasa / (100+tasa), redondeado a unidad menor.
- Total = suma de netos. Redondeo half-up por línea con BCMath.

Este es un presupuesto comercial, no una factura fiscal. El tratamiento definitivo de operaciones fiscales y multimoneda debe validarse en un módulo posterior.

## Criterios de aceptación implementados

1. Crear/editar borrador con destinatario y al menos una línea válida.
2. Recalcular en servidor y rechazar cantidades/precios/monedas inválidos.
3. Emitir exactamente una vez, sin duplicar número.
4. Descargar siempre el PDF conservado después de emitir.
5. Cambios posteriores de cliente no afectan el PDF ni snapshot.
6. Registrar decisión con evidencia mínima de 10 caracteres.
7. Usuario de consulta no puede escribir.
8. PDF e historial no son públicos.

## Interfaz de referencia

El listado presenta documento y referencia en una misma celda, cliente, estado etiquetado, importe alineado a la derecha y vigencia. La acción principal es `Nuevo presupuesto`; PDF y menú de fila son secundarios. Los borradores muestran su identificador interno (`Borrador #…`), sin asignar correlativo comercial.

El formulario agrupa datos de cabecera, productos/servicios y condiciones comerciales. El detalle pasa de una columna en móvil a dos en tablet y una fila de campos en escritorio amplio. `Guardar borrador` usa el mismo servicio y validaciones de la versión inicial. La búsqueda sigue incluyendo número, asunto y cliente; el modo móvil apila cada documento con etiquetas.

Ver `diseno.md` y `componentes-ui.md` en la carpeta superior antes de implementar nuevas pantallas.

## Siguientes tareas

Revisiones vinculadas con referencia de documento/revisión, aprobación interna configurable, envío con estados separados, evidencia adjunta, catálogo, historial de modificaciones con diferencias de campos y conversión a orden. El historial actual registra que se guardó un borrador, no cada diferencia individual.

## Operación completa desde la interfaz (15/10/2026)

- Inicio en Presupuestos, pestañas Todos/Borradores/Emitidos/Aceptados/Rechazados/Anulados con conteos globales por estado (independientes de filtros de tabla).
- Búsqueda por correlativo completo, identificador de borrador, asunto y nombre actual/original del cliente, filtro PYG/USD, rango de emisión, emitidos del mes y vencidos sin respuesta. Un borrador sin emisión no entra en rangos de emisión.
- Selector de columnas, ordenación y paginación de 10/25/50. Selección máxima de un registro para detalle, edición de borrador, PDF e historial; no hay emisión masiva.
- Guardar abre el detalle. Resumen de totales durante la edición con cálculo en servidor; los campos incompletos no presentan importes como definitivos.
- Detalle consultable en todos los estados, con snapshot de cliente/emisor al emitir, ítems, condiciones, totales, fechas, advertencia de vencimiento e historial con actor.
- Emitir requiere confirmar el cierre y correlativo. PDF en vista previa inline o descarga; emitidos reutilizan siempre el archivo conservado. No se envía correo.
- Registrar decisión requiere estado permitido y evidencia; vuelve al detalle actualizado.
- Los vencidos permanecen Emitidos hasta decisión; no se altera retrospectivamente el PDF.
- No se agregan notificaciones ficticias, soporte ni órdenes de trabajo sin módulo implementado.

Los PDFs ya emitidos y la numeración de la instalación se conservan. No se necesitan migraciones ni carga de demostración para esta actualización.
