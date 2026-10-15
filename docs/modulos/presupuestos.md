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

## Siguientes tareas

Revisiones vinculadas con referencia de documento/revisión, aprobación interna configurable, envío con estados separados, evidencia adjunta, catálogo, historial de modificaciones con diferencias de campos y conversión a orden. El historial actual registra que se guardó un borrador, no cada diferencia individual.
