# Módulo Presupuestos

Estado: núcleo funcional implementado en v0.1.0. Revisiones, envío y portal pendientes.

## Entidades

- Quote: destinatario, creador, asunto, moneda, vigencia, líneas, condiciones, totales, estado, número, snapshot y archivo/hash.
- Líneas en JSON: descripción libre, cantidad de hasta 3 decimales, precio, descuento porcentual, tasa IVA, total e IVA incluido calculados. El catálogo opcional copia valores al ítem; no vuelve a calcular documentos guardados al consultar el maestro.
- document_sequences: una secuencia global por instalación, no reinicia por año en v0.1.
- AuditEvent: actor, acción, documento, metadatos y fecha.

## Ciclo

Borrador → Emitido → Aceptado / Rechazado / Anulado.

Borrador no tiene número comercial y puede editarse por admin/comercial. Emitir fija correlativo y contenido. Una decisión solo puede registrarse una vez desde Emitido. Solo borradores sin número pueden darse de baja, con confirmación, soft delete y auditoría; emitidos y estados finales no se eliminan.

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

Revisiones vinculadas con referencia de documento/revisión, aprobación interna configurable, envío con estados separados, evidencia adjunta, historial de modificaciones con diferencias de campos y conversión a orden. El historial actual registra que se guardó un borrador, no cada diferencia individual.

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

Los PDFs ya emitidos y la numeración de la instalación se conservan. La entrega de listado/detalle no requirió migración. La ampliación de catálogo/pago/baja sí incorpora la migración aditiva descrita abajo; no requiere datos demo.

## Contrato de datos para desarrollo

Identificadores estables: `PPT-01` guardar borrador, `PPT-02` calcular, `PPT-03` emitir, `PPT-04` consultar/PDF, `PPT-05` decidir y `PPT-06` listar. Un nuevo cambio debe declarar cuál modifica y conservar los demás.

| Campo / conjunto | Origen y validación actual | Se conserva en |
|---|---|---|
| customer_id | Formulario; cliente existente obligatorio | `quotes.customer_id`, FK con borrado restringido |
| subject | Formulario; texto obligatorio hasta 200 | `quotes.subject` |
| currency | Formulario; PYG o USD | `quotes.currency` |
| valid_until | Formulario; fecha igual o posterior a hoy al guardar; se revisa al emitir | `quotes.valid_until` |
| terms | Formulario; opcional hasta 5000 | `quotes.terms` |
| lines | 1 a 100 ítems válidos; normalizados por calculador | `quotes.lines`, JSON |
| Descripción | Obligatoria hasta 1000 por ítem | Dentro de lines |
| Cantidad | Decimal textual positivo, hasta 6 enteros y 3 decimales | Dentro de lines |
| Precio unitario | No negativo, hasta 9 enteros y 2 decimales; PYG debe ser entero | Dentro de lines |
| Descuento | 0 a 100%, hasta 2 decimales | Dentro de lines |
| IVA | Solo 0, 5 o 10, incluido en precio | Dentro de lines |
| Totales | Servidor; subtotal/discount/tax/total en unidades menores, decimal(20,0) | Columnas `*_minor` y neto/IVA por línea |
| Actor / estado / correlativo | Servidor; no editables por formulario | created_by, status, number; secuencia `quote` |
| Emisión y snapshot | Servidor; fecha y copia de datos actuales del emisor/destinatario | issued_at, snapshot JSON |
| PDF y huella | Servidor; UUID privado y SHA-256 de bytes emitidos | pdf_path, pdf_sha256; archivo en disco local privado |
| Evidencia de decisión | Formulario; texto obligatorio 10 a 2000 | metadata de `audit_events`, no una columna de Quote |

El estado de negocio vive en `quotes.status`; la evidencia y el actor de decisión viven en eventos. No confundir `created_by` con quien emite o registra una respuesta: esos actores se consultan en auditoría. El esquema no define un enum SQL de estados; los servicios actuales validan las transiciones.

## Contrato por operación

| ID | Entrada / permiso | Proceso y escrituras | Salida y errores |
|---|---|---|---|
| PPT-01 | Cabecera/ítems; admin o commercial activo | Valida, recalcula, crea o bloquea borrador existente; guarda Quote y `draft.saved` en transacción | Detalle del borrador; inválido o emitido no se guarda |
| PPT-02 | Ítems y moneda | `QuoteCalculator`; el resumen de interfaz no escribe datos | Totales o aviso de completar valores; el guardado vuelve a validar |
| PPT-03 | ID de borrador; admin/commercial activo | Bloquea Quote y secuencia, revisa vigencia, asigna correlativo, snapshot y fecha, genera/guarda PDF, incrementa secuencia y registra `quote.issued` con número/hash | Detalle emitido; segundo intento se rechaza y no consume otro número; no envía correo |
| PPT-04 | ID; sesión activa de cualquiera de los tres roles | Consulta detalle/historial; emitido usa snapshot y archivo original; preview de borrador se genera con datos actuales | Preview inline o descarga; sesión ausente redirige a login, usuario inactivo denegado, archivo/ID inexistente 404 |
| PPT-05 | ID emitido, accepted/rejected/cancelled y evidencia; admin/commercial activo | Valida, bloquea Quote, modifica estado y registra evento con actor/evidencia | Detalle actualizado; falta de evidencia/estado previo inválido se rechaza |
| PPT-06 | Búsqueda/filtros/paginación; sesión activa | Solo consulta; conteos de pestañas globales, filtro de emisión excluye borradores sin issued_at | Filas y vacío; no altera estado ni documento |

Decisión posterior a vencimiento está permitida hoy. Vencido es una advertencia derivada para emitidos sin respuesta; no un nuevo estado persistido. Los estados finales no admiten una segunda decisión ni se vuelven a borrador. Una revisión requiere una operación nueva que preserve el documento de origen.

## Ejemplos de cálculo comprobables

| Entrada | Bruto | Descuento | Neto / total | IVA incluido |
|---|---|---|---|---|
| PYG: cantidad 2, precio 110000, descuento 10%, IVA 10% | ₲ 220.000 | ₲ 22.000 | ₲ 198.000 | ₲ 18.000 |
| USD: cantidad 3, precio 0.10, descuento 0%, IVA 0% | US$ 0,30 | US$ 0,00 | US$ 0,30 | US$ 0,00 |

Guardar USD 0,30 como 30 unidades menores, no 0.30 en una variable float. Redondear por línea con half-up antes de sumar; no redondear solo la suma global. PYG 1.50 se rechaza, no se convierte silenciosamente en 2. No sumar importes de monedas distintas ni inventar conversión cambiaria.

## Concurrencia, fallos y compatibilidad

- Emisión usa bloqueo de fila y secuencia en una transacción, y unicidad SQL del número. La segunda emisión del mismo documento devuelve validación, no una segunda emisión exitosa. Validar bloqueo real con PostgreSQL antes de producción; SQLite no lo demuestra.
- Guardado de borrador no tiene clave de idempotencia para creación: reenviar una creación puede crear otro borrador. El bloqueo de edición serializa escrituras, pero no detecta que dos pestañas editaron versiones diferentes; la última escritura puede sustituir cambios del borrador. El control de versión optimista está pendiente.
- Generación y guardado de archivo se hacen dentro del flujo de emisión, pero el disco no participa en el rollback SQL. Una falla posterior a escribir puede dejar un PDF huérfano, no visible como emitido sin confirmación en base. No hay limpieza automática de huérfanos implementada.
- El historial registra guardados, emisión y decisiones; no conserva diferencias de cada campo ni todas las consultas/descargas. No presentar el hash como firma digital ni la auditoría local como inalterable.
- Mantener lectura de snapshots antiguos con campos opcionales y el archivo original al cambiar marca, PDF o datos maestros. Agregar campos mediante migraciones compatibles; actualizar este contrato y pruebas del módulo.
- Permisos de servidor son obligatorios para cada nuevo punto de entrada. No exponer Eloquent directo desde una API o acción que permita esquivar QuoteService.

## Trazabilidad hacia verificación

`QuotesTest` cubre cálculo, precisión, PDF conservado, escritura bloqueada/segunda emisión, permisos, evidencia y persistencia Livewire. `QuoteWorkspaceTest` cubre pestañas/filtros/búsqueda, selección, emisión/decisión desde detalle, preview original, reader y vencimiento. `AppearanceTest`/`ThemeContrastTest` cubren apariencia compartida y pares de contraste. Ver ejecución y límites en [validación](../validacion.md).

Pendientes identificados no se consideran implementados por estar descritos en este contrato. Cada uno debe tener tarea propia, criterio de aceptación y prueba antes de declararse disponible.

## Carga, catálogo, pago y baja de borradores · ampliación

Estado: implementado en la ampliación de carga. Mantiene las reglas de cálculo y los estados comerciales anteriores.

### Datos y experiencia de carga

- Alta de cliente desde el selector, con formulario reutilizado y validación/permiso en `CustomerService`. Crear y seleccionar conserva la carga de la propuesta.
- Ítems compactos con descripción amplia, cantidades, precio con prefijo monetario, descuento, IVA e importe de línea calculado en servidor. Se pueden duplicar y contraer con los controles nativos. No hay guardado automático.
- Catálogo opcional: buscar productos/servicios activos de la moneda del presupuesto o dejar «Carga manual». La selección copia descripción/precio/IVA; el comercial puede ajustarlos. Al quitar selección no se borran esos valores. Se pueden mezclar ítems manuales y de catálogo.
- `catalog_item_id` y `sku` son metadatos de línea. La ID debe existir y coincidir en moneda; el servicio toma el SKU del maestro, no de un campo arbitrario. El calculador conserva estos metadatos junto con los valores comerciales copiados. No convertir moneda silenciosamente.
- Desactivar un producto impide seleccionarlo en nuevas cargas, pero no invalida líneas guardadas que ya lo referencian. Al abrir una propuesta no se reemplazan automáticamente sus descripciones ni precios por los actuales del catálogo. Volver a seleccionar un producto es una copia explícita; guardar una línea vinculada resuelve su SKU actual.
- Acciones de guardado accesibles al desplazarse y aviso nativo de cambios sin guardar. Términos generales contraídos inicialmente y editables.

### Pago y condiciones

| Campo | Regla | Persistencia |
|---|---|---|
| payment_mode | cash / credit / to_agree; Contado inicial para propuestas nuevas | `quotes.payment_mode`, nullable para compatibilidad histórica |
| credit_days | Obligatorio en Crédito; entero 1–365; fuera de Crédito se guarda null | `quotes.credit_days` |
| payment_notes | Opcional hasta 1000; medio de pago, anticipo o hito de inicio del plazo | `quotes.payment_notes` |
| delivery_terms | Opcional hasta 500; texto específico de entrega | `quotes.delivery_terms` |
| warranty_terms | Opcional hasta 500; cobertura concreta aplicable | `quotes.warranty_terms` |
| terms | Texto de hasta 5000 propio de cada propuesta | Columna existente `quotes.terms` |
| quote_terms | Base general editable por admin para nuevas propuestas | `companies.quote_terms` |

Crédito se presenta como «Crédito a N días». No es una cuenta por cobrar ni calcula una fecha de cobro: describir desde qué hito se cuentan los días en payment_notes. No confundir vigencia de la oferta con plazo de pago. Registros anteriores sin modalidad muestran «A convenir»; no se rellenan retrospectivamente ni se altera su PDF.

Los términos iniciales aclaran revisión previa, alcance expreso, conformidad y cambios por escrito que pueden afectar precio/plazo. Para Solvet-IT incluyen la identificación aportada por José. Se pueden ajustar por empresa y por propuesta. No se incorpora una renuncia absoluta a reclamos. Esto es contenido comercial configurable, no firma electrónica ni un módulo de validación legal.

### PPT-07 · Eliminar borrador

Admin/commercial activo puede solicitar baja desde el menú del listado/detalle o la cabecera de edición. Confirmación específica. `QuoteService::deleteDraft` bloquea y vuelve a comprobar en servidor estado draft y número nulo, registra `draft.deleted` con actor/fecha/referencia y aplica SoftDeletes en una transacción. No consume correlativo ni borra PDFs emitidos.

El borrador desaparece de listados, pestañas y accesos normales por ID, PDF e historial (404). Se conserva fila y auditoría para revisión administrativa; no hay papelera/restauración web ni eliminación física implementadas. No presentar esa conservación como borrado definitivo de datos personales. Un intento concurrente de emitir y eliminar se resuelve por el bloqueo de la fila según el motor real; verificar en PostgreSQL antes de operación productiva.

### Documento PDF y compatibilidad

Cabecera más compacta, bloques de emisor/cliente, código cuando existe, descripciones multilínea, columnas numéricas e importes, total destacado y secciones distintas de pago/entrega y términos. A4, pie y encabezados de ítems repetidos en varias páginas. La nueva plantilla afecta borradores y nuevas emisiones; archivos emitidos conservan sus bytes y hash.

Migración `2026_10_16_000001_add_quote_entry_and_catalog`: campos nullable de pago/entrega/garantía y deleted_at, términos generales del emisor y tabla de catálogo. No actualiza masivamente documentos existentes ni secuencias. Ejecutar `scripts/update.sh` con respaldo habitual. No revertir esa migración después de usarla sin evaluar pérdida de sus datos.

Pruebas específicas en `QuoteEntryTest`: plazos, contado/crédito, términos, mezcla de catálogo/manual, moneda, conservación, alta inline, autorización y baja auditada. Ver [catálogo](catalogo.md) y [validación](../validacion.md).

## Rentabilidad interna

`cost_snapshot` nullable y oculto en serialización almacena costos versionados por línea al guardar borrador. Selector de versión de costo de catálogo o costo neto manual opcional; utilidad después de descuentos, IVA separado y cobertura explícita. Admin/commercial consultan; reader/PDF no reciben estos datos. Históricos sin costos conservan ese estado, emitidos mantienen copia y archivo. Contrato completo: [Costos y precios](costos-precios.md), COS-03.

## Conjuntos y pedido

Una línea admite componentes internos opcionales: su costo sustituye al principal, sin sumar ventas. Snapshot v2 privado conserva cantidad por conjunto y cantidad total. Edición restaura componentes; emitidos/PDF anteriores no cambian. Aceptados ofrecen Confirmar pedido/Ver pedido. No hay reserva hasta una acción explícita en el pedido. Contrato completo: [OP-04, OP-07 y OP-08](operaciones.md).
