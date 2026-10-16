# Compras, inventario y ejecución · OP-01 a OP-08

Estado: primera implementación funcional para una instalación y un depósito. Se integra con Catálogo, Costos y Presupuestos existentes. No hay facturación fiscal, declaración tributaria ni contabilización automática. Una referencia de costo no constituye una compra ni una existencia.

## Separación de conceptos

- **Catálogo:** identidad de bien/servicio, unidad, comprable, vendible, control de stock solo para bienes, moneda, IVA y precio final de venta incluido IVA. Un servicio N1/N2 puede tener varios proveedores y costo propio sin duplicar el ítem.
- **Costos:** alternativas estimadas con proveedor/RUC/referencia, fecha/vigencia, IVA y gastos. Crear una referencia no publica precios ni revaloriza stock. Publicar requiere `CostService::applyPrice` y la acción confirmada «Aplicar precio al catálogo».
- **Compra:** proveedor/documento, cantidades, condiciones y costo efectivamente informado. Confirmar no mueve stock; recibir confirma todas sus cantidades. Los bienes con stock ingresan al depósito y los servicios/bienes sin stock vinculados se imputan a su intervención del pedido.
- **Pedido:** copia de las condiciones del presupuesto aceptado y de sus componentes; reservas, entregas y ejecución. La venta acordada se cuenta una sola vez, aunque haya varios componentes.
- **Inventario:** movimientos valorados y saldo por ítem. Una cotización o un presupuesto nunca revaloriza existencias.

## Datos y permisos

| Conjunto | Persistencia / responsable |
|---|---|
| Capacidades del ítem | catalog_items.can_buy/can_sell (true inicial), track_stock (false inicial); unit acepta unit/hour/day/package |
| Compras | purchases: proveedor/RUC/referencia obligatorios, moneda, fecha no futura, estado, líneas copiadas, envío y reparto, recepción |
| Pedido | sales_orders: quote_id único, actor, estado, venta/IVA acordados y copia estimada privada |
| Intervenciones | order_lines: línea comercial de origen, ítem, clasificación/unidad/stock copiados, cantidad, reservado, cumplido, costo previsto y ejecutor |
| Saldo | stock_balances: PK/FK ítem, físico, reservado y valor; costo promedio derivado |
| Kardex | stock_movements: fecha/actor, origen, cantidad/valor/costo aplicado, devolución original, clave de reintento y motivo |
| Trabajo/servicio | execution_entries: intervención, usuario, compra cuando corresponda, cantidad/costo, referencia, clave de reintento, anulación y motivo |
| Reintento de entrega | operation_batches: clave única y huella del pedido/operación |

Admin/commercial activos registran costos, compras y operaciones: Gates de costos y comprobación en cada servicio. Reader consulta pedidos e inventario sin costos, valoraciones, proveedor o márgenes; compras se deniegan en listado y detalle, incluso por URL. Gate también en métodos Livewire que devuelven datos financieros. Los modelos ocultan campos privados en serialización; las vistas comerciales/PDF no reciben componentes ni costos. Las acciones no confían en totales/estado/actor enviados desde navegador.

No hay eliminación de documentos operativos o movimientos desde interfaz. Correcciones mediante baja de compra no recibida, devolución, ajuste motivado o anulación de ejecución; siempre conserva el registro anterior.

## OP-01 · Guardar y confirmar compra

`PurchaseService::save`: valida proveedor (200), RUC (30), referencia (200), moneda PYG/USD, fecha <= hoy, 1–100 líneas. Cantidades positivas, hasta 9 enteros/3 decimales. Costo informado con IVA según precisión de moneda, hasta nueve enteros. Cada ítem debe ser comprable y de la moneda elegida; unidad/tipo/stock se copian de servidor. Totales y reparto se calculan en servidor.

`draft → ordered` por confirmación. Solo draft se edita. Reconfirmar ordered/received es idempotente. Anular draft/ordered requiere motivo 10–1000; no altera stock. Una compra recibida no se anula eliminando su recepción.

Cada línea puede vincularse a una intervención abierta del mismo ítem, moneda y unidad. **Vincular servicios antes de recibir** para imputarlos al cliente. En esta primera versión no hay reasignación posterior de una compra ya recibida sin pedido; tampoco un saldo de horas compradas como inventario. No simular esa prestación como una existencia.

## OP-02 · Gastos de envío

Cabecera: monto con IVA, tasa 0/5/10, recuperable/no recuperable y reparto quantity/value/manual. Se excluye el IVA recuperable antes de distribuir costos efectivos. El envío se reparte solo entre bienes, tengan o no control de stock:

- Cantidad: proporción de cantidades compradas de bienes.
- Valor: proporción de costo base neto de bienes antes de envío y otros gastos.
- Manual: importes de envío **con IVA** por línea; suma exacta = cabecera. La proporción se aplica al envío neto.

Una base cero con envío positivo se rechaza. No se admite envío asignado a servicios. Conservar ocho decimales de unidad menor y asignar el residuo al último bien garantiza que el total distribuido coincida con el envío neto. «Otros costos de línea» son totales netos de IVA recuperable, adicionales al envío; no ingresarlos otra vez como flete.

Costo efectivo de línea = base de compra neta del IVA recuperable + otros costos de línea + envío neto asignado. Costo unitario = costo de línea / cantidad. Se conserva también importe bruto, IVA y origen. Datos declarados por usuario: no se verifica factura/RUC ante DNIT ni se declara crédito fiscal.

## OP-03 · Recepción

`ordered → received`, bloqueo de compra, pedidos relacionados en orden ascendente y luego ítems/saldos en orden ascendente. La recepción es completa y atómica. Se rechaza si cambió unidad/moneda/control de stock del ítem o si la imputación excede la intervención. Un fallo revierte todas sus líneas.

- Bien con stock: entrada valorada, clave única `receipt:compra:índice`, sin costo directo en el pedido hasta entregar.
- Servicio o bien sin stock vinculado: ejecución externa y cantidad cumplida en el pedido. No crea saldo ni movimiento físico.
- Compra sin intervención vinculada: registra recepción; no inventa ejecución de cliente.

Repetir recepción devuelve el mismo documento sin crear movimientos/ejecuciones adicionales. Fecha de movimiento es la confirmación de recepción, no una reconstrucción retroactiva desde fecha de factura. Recepciones parciales requieren compras separadas en esta versión; nunca editar una recibida para simular otra entrada.

## OP-04 · Pedido, reserva y entrega

`OrderService::fromQuote` solo desde accepted. Bloquea presupuesto, usa quote_id único y devuelve pedido existente al repetir. Copia venta/IVA, estimación y componentes. No cambia el presupuesto ni reserva automáticamente. `/app/quotes` ofrece Confirmar pedido y Ver pedido.

`reserve` toma únicamente el disponible, hasta cantidad pendiente de cada intervención. Puede reservar parcialmente por falta de stock. Repetir no sobre-reserva. Físico no cambia; disponible = físico − reservado. Reservas quedan vinculadas a intervención/pedido y auditadas. Liberar vuelve esos saldos a disponible, sin salida física.

`deliver` confirma **todas las cantidades actualmente reservadas**. Descuenta físico y reserva; guarda el valor y costo aplicados en cada salida. Reintentos de la misma clave UUID no entregan nuevamente, aunque se repita una petición. No entrega sin reserva o por encima de lo disponible, ni toma unidades reservadas para otro pedido. Con recepción futura se puede volver a reservar/entregar el pendiente. No hay selección manual de una cantidad menor a la reserva en esta primera pantalla.

Cancelar pedido solo sin cantidades realizadas/entregadas vigentes; libera reservas y conserva documentos. Para cancelar después de ejecutar primero deben registrarse devoluciones/anulaciones pertinentes.

## OP-05 · Valoración y correcciones

BCMath, sin float. Cantidades decimal(18,3), valor y costo unitario decimal(28,8), moneda del ítem. Promedio = valor del saldo / cantidad física. En una entrada se suma cantidad y costo efectivo; en una salida se aplica valor × cantidad salida / cantidad física. Al agotar el saldo se usa exactamente su valor restante, evitando residuos. Nuevas tarifas del proveedor y publicaciones de precio no cambian valor físico. No se permiten saldo físico/valor negativos ni consumir reservas ajenas.

Devolución referenciada a receipt/delivery, cantidad positiva no superior al original menos devoluciones previas, motivo obligatorio y UUID con huella. Cliente: entra al costo aplicado en su salida original, incluyendo el residuo de la última devolución; reabre el pedido y reduce cantidad cumplida. Proveedor: sale al promedio vigente, con control de disponible. La devolución de proveedor no calcula un reintegro de dinero ni un documento fiscal.

Ajuste in/out con motivo 10–1000 y UUID. Entrada requiere costo efectivo explícito (hasta ocho decimales por unidad, neto de IVA recuperable; cero permitido). Salida usa promedio vigente. Reutilizar clave con otros datos se rechaza. Movimientos identifican usuario, hora, ítem, documento/intervención y motivo; la auditoría identifica reserva/liberación y decisiones. No hay ajustes silenciosos o borrado de kardex.

## OP-06 · Configuración y ejecución

Soporte/configuración son intervenciones service, sin stock. El ejecutor inicialmente viene de la referencia elegida al presupuestar. «Definir ejecutor del servicio» permite propio/externo antes de ejecutar; conserva la estimación original y audita motivo. Revisar compras pendientes al cambiarlo.

- Propio: cantidad/horas realmente realizadas, costo interno efectivo unitario y referencia. No genera IVA crédito.
- Externo: costo desde compra recibida vinculada, proveedor/factura y cantidad. No volver a cargar esas horas como trabajo propio.

Cada intervención tiene un ejecutor vigente, cantidades acumuladas <= pedido y entradas con clave única. No sumar propio y externo para la misma intervención. Para un reparto real de trabajos distintos, usar componentes/intervenciones separadas. Anular una ejecución conserva costo y referencia originales marcados como anulados, revierte cantidad cumplida y reabre pedido; no anula compra ni crea documento fiscal. Repetir anulación no vuelve a restar.

## OP-07 · Conjuntos y presupuesto

Línea comercial normal o con 1–30 componentes por unidad, máximo 300 componentes por propuesta. Cada componente tiene ítem/cantidad/referencia de costo o costo neto manual. Cantidad ejecutable = cantidad del conjunto × cantidad por conjunto; debe ser positiva y caber en tres decimales. Los componentes **reemplazan** el costo principal, no se agregan a él. El precio/IVA/descuento se calculan solo en la línea principal; no sumar precios de componentes como otro ingreso.

Componentes van en cost_snapshot v2 privado, nunca en las líneas comerciales que imprime el PDF. Se copian proveedor/RUC, referencia, origen, costo informado/IVA/gastos, tipo/unidad/stock, cantidades, costos exactos y fecha. Al editar se restaura cantidad por conjunto, sin multiplicar de nuevo la cantidad total. Guardar sin reenviar componentes preserva los ya capturados; vaciar componentes explícitamente desagrupa.

IVA recuperable fuera del costo, no recuperable dentro, gastos una vez, venta neta después de descuentos. Utilidad total estimada solo con cobertura completa. Se suman costos exactos y se redondea al total en moneda, sin acumular redondeos unitarios. Margen de mayor precisión se presenta a cinco decimales. Emitidos y PDFs anteriores siguen iguales; cambiar catálogo/proveedor no los reconstruye.

## OP-08 · Previsto frente a ejecución

Cerrar ejecución requiere todas las cantidades cumplidas. Antes del cierre se muestra costo registrado parcial; no un margen final. Cerrado: venta acordada sin IVA − costo de salidas neto de devoluciones − servicios realizados vigentes. Resultado por línea comercial y total; los componentes suman costos dentro de la venta correspondiente. Costos se agregan en lotes, sin una consulta por intervención.

Es **utilidad bruta operativa con costos registrados**, no utilidad neta, ingreso fiscal ni cobro. Los gastos generales no cargados, impuestos a la renta, notas de crédito al cliente o cambios del precio contratado no se suponen. Una devolución de cliente reabre el pedido y suspende el resultado final hasta resolverlo; no se conserva como final un margen de una entrega deshecha.

## Compatibilidad y operación

Migraciones aditivas 000003 y 000004. Ítems existentes: comprable/vendible true, stock false; **no se crea ninguna existencia**. Habilitar control y cargar saldo inicial por ajuste documentado o recibir una compra real. Precios, correlativos, referencias, PDFs y snapshots anteriores no se actualizan masivamente. Extras de costo anteriores conservan su sentido neto (IVA extra 0/no recuperable). Las identidades con historial de costo/stock/pedido no cambian moneda/tipo/unidad; control de stock tampoco se cambia con movimientos o pedidos existentes.

Una propuesta anterior con catálogo pero sin metadatos v2 toma la clasificación actual del ítem al crear el nuevo pedido, con costo estimado ausente si no lo tenía. Revisar sus intervenciones antes de reservar; no se altera el documento original. Una línea libre sin catálogo sigue como servicio manual sin stock: no inferir existencia por su descripción.

Actualizar con respaldo y `scripts/update.sh`; revisar migraciones, `/up` y casos de laboratorio. Sin nuevas dependencias/colas. No revertir estas migraciones después de usar sin evaluar pérdida de documentos y kardex. SQLite valida reglas funcionales; la concurrencia real debe verificarse en PostgreSQL antes de producción. No se accedió al servidor ni se configuró Nginx/Cloudflare.

## Aceptación comprobable

| Caso | Costo efectivo | Venta neta | Utilidad bruta aprox. | Inventario |
|---|---:|---:|---:|---|
| A · N1 Jorge 6 × 83000 IVA 10% recuperable | 452727,27272727 | 600000 | 147273; margen 24,54545% | Ninguno |
| B · equipo 500000 + envío 20000, ambos IVA recuperable + propio 65000 | 537727,27272726 | 600000 | 62273; margen 10,37878% | Una entrada y una salida de equipo |

`OperationsTest` ejecuta A/B, promedio ponderado, reserva parcial/insuficiencia, reintentos, devoluciones al costo correcto, reparto de flete, saldo inicial, conservación PDF/snapshot, precio explícito sin revalorizar, Livewire/reader, cambio de ejecutor, anulación y componentes editados/validados. Ver [evidencia](../validacion.md) y [guía operativa](../tareas/compra-a-ejecucion.md).

Referencia conceptual consultada el 16/10/2026: [Odoo · Supply Chain 19](https://www.odoo.com/documentation/19.0/applications/inventory_and_mrp.html), [costos en destino](https://www.odoo.com/documentation/saas-19.3/es/applications/inventory_and_mrp/inventory/inventory_valuation/landed_costs.html). Se adaptan separación bien/servicio, costo estimado frente a movimientos, AVCO y reparto; no se copia su contabilidad ni se afirma equivalencia completa con Odoo.
