# Costos y precios · primera versión

Estado: implementado. Una empresa por instalación. Nuevo módulo `app/Modules/Costs` sobre el catálogo existente; no duplica productos/servicios ni instala otro ERP. Referencia funcional Odoo: costo interno, utilidad y margen en cotizaciones. No hay un porcentaje de ganancia universal de Odoo: cada empresa define su objetivo.

## Contexto confirmado por José · 16/10/2026

Solvet factura con IVA 10%. Los distribuidores informan precios con IVA incluido. Soporte N1/N2 puede ejecutarlo José o un proveedor externo que debe emitir su factura. No se confirmó régimen de impuesto a la renta: no calcular IRE/IRP ni utilidad después de esos impuestos.

El costo de terceros puede ser una cotización previa a la compra. «IVA recuperable previsto» es una hipótesis de costeo que requiere factura válida y derecho al crédito; no demuestra una compra efectuada ni un crédito fiscal declarado. El módulo no liquida impuestos ni valida comprobantes ante DNIT.

## COS-01 · Registrar una versión de costo

Acceso: Comercial → Costos y precios. Admin/commercial activos pueden consultar y crear; reader/inactivos no. Gates `view-costs` y `manage-costs`, autorización del recurso y `CostService`. No hay edición/borrado de versiones desde interfaz/servicio. Los cambios se registran con una versión nueva.

| Entrada | Regla / unidad | Persistencia |
|---|---|---|
| Ítem | Catálogo existente | cost_profiles.catalog_item_id |
| Origen | purchase para productos; own/external para servicios | source |
| Proveedor | Nombre obligatorio salvo propio, máximo 200; RUC opcional 30 | supplier_name, supplier_tax_id |
| Referencia | Cotización/factura opcional hasta 200; no se verifica documento | reference |
| Costo informado | Por unidad/hora. Compras/terceros: IVA incluido. Propio: costo interno sin IVA crédito | purchase_minor |
| IVA de costo | 0/5/10. Propio exige 0 | purchase_tax_rate |
| Tratamiento | Recuperable previsto / no recuperable / pendiente. Propio exige no recuperable | vat_treatment |
| Otros costos | Por unidad, netos de IVA recuperable; no duplicar flete/traslado | extra_minor |
| Regla | Margen sobre venta %, recargo sobre costo %, utilidad fija por unidad | pricing_mode, pricing_value |
| Fecha / vigencia | Fecha no futura; vigencia opcional >= hoy y >= fecha | cost_date, valid_until |
| Aplicar precio | Booleano, inicialmente sí; requiere IVA definido | Cambia catalog_items.price_minor dentro de transacción |
| Notas | Internas, máximo 1000 | notes |
| Moneda / unidad / IVA venta | Servidor toma el catálogo; no acepta valores externos | currency, unit, sales_tax_rate |
| Actor / cálculo | Servidor; costo efectivo y precio sugerido derivados | created_by, effective_unit_minor, suggested_price_minor |

PYG entero; USD hasta dos decimales, hasta nueve enteros en importes de entrada. Porcentajes hasta dos decimales, margen 0–99,99%, recargo 0–1000%. Utilidad fija respeta precisión de moneda. Objetivo inicialmente vacío: debe definirse; cero explícito se admite. Precio derivado no supera límites existentes del catálogo.

Guardar bloquea el ítem y crea versión + selecciona `current_cost_id`, opcionalmente actualiza precio + registra `cost.created` con actor, ID, antes/después de costo actual y precio. Todo en transacción. Repetir una creación genera otra versión; no hay clave de idempotencia en esta entrega. La interfaz nativa deshabilita el envío mientras procesa. Los costos con IVA pendiente pueden guardarse si no se aplica precio: quedan sin costo efectivo, no valen cero.

Una nueva versión pasa a ser costo actual aunque no se aplique su precio. «Precio sugerido de versión» y «precio actual del catálogo» pueden diferir por esa decisión o por edición comercial explícita. Las versiones anteriores quedan disponibles como alternativas para presupuestar y como historial. Los proveedores se conservan como datos de cada versión; maestro de proveedores/compras es una ampliación futura.

## COS-02 · Cálculo decimal

Implementación: `CostCalculator`, BCMath; jamás float. Importes persistidos en unidades menores enteras, excepto costo efectivo por unidad, que conserva ocho decimales de unidad menor (`decimal(28,8)`) para cantidades fraccionarias. No cambiar el contrato monetario del módulo Quotes.

- Recuperable: costo efectivo = costo informado × 100 / (100 + tasa compra) + otros costos.
- No recuperable: costo efectivo = costo informado + otros costos.
- Trabajo propio: no genera IVA crédito; costo efectivo = costo interno + otros costos.
- Pendiente: costo efectivo y precio sugerido null.
- Margen m: venta neta = costo efectivo / (1 − m/100).
- Recargo r: venta neta = costo efectivo × (1 + r/100).
- Utilidad fija u: venta neta = costo efectivo + u.
- Venta con IVA = venta neta × (1 + tasa venta/100).

Precio sugerido: redondeo hacia arriba a unidad menor para no rebajar el objetivo por redondeo. Costos por línea: cantidad × costo efectivo, half-up a unidad menor. Ventas/IVA/descuentos conservan el redondeo ya existente de QuoteCalculator. La utilidad final usa esos importes redondeados: puede variar una unidad por efecto de IVA/cantidad. Margen mostrado trunca a dos decimales. No convertir monedas.

Ejemplos de aceptación PYG con compra 500000, IVA compra/venta 10%, crédito previsto completo y sin extras:

| Regla | Costo efectivo antes de redondeo de línea | Precio sugerido con IVA |
|---|---|---|
| Recargo 10% | 454545,45454545 | 550000 |
| Margen 10% | 454545,45454545 | 555556 |
| Margen 20% | 454545,45454545 | 625000 |
| Utilidad fija 50000 | 454545,45454545 | 555000 |

Propio por hora: costo 50000, margen 50%, IVA venta 10% → tarifa 110000. Tercero factura 55000 con IVA recuperable 10%, mismo margen → tarifa 110000. No tratar el propio tiempo como gratis por no tener factura de compra; el usuario define su costo estándar. No incluye cálculo de remuneraciones, cargas laborales ni tiempos ejecutados.

## COS-03 · Presupuestar con costos

`QuoteProfitability` prepara una copia interna en `quotes.cost_snapshot` al guardar borrador. No acepta snapshot, total, utilidad o actor del navegador. El calculador comercial sigue ignorando/eliminando esos datos de las líneas públicas.

- Seleccionar catálogo copia precio comercial y costo actual. Selector adicional permite una versión propia/tercero/histórica del mismo ítem y moneda; al elegir copia su precio sugerido, si existe. Descripción/precio/IVA siguen editables según contrato de presupuestos.
- La versión enviada debe existir y pertenecer al ítem/moneda. Una ID ajena es rechazada. Quitar catálogo limpia referencia de costo; no transforma el costo de proveedor en otro ítem.
- Líneas manuales admiten costo unitario interno opcional, neto de IVA recuperable. Vacío = desconocido, cero explícito = costo definido cero. No confundir este campo con precio facturado bruto por un proveedor: para ese flujo usar Catálogo/Costos.
- Al editar, se recupera la versión guardada; no se sustituye por costo actual. El servicio también preserva la versión/manual por posición y mismo catálogo/moneda si el consumidor no envía esos campos. Cambios/reordenaciones deben enviar su procedencia explícita, como hace el formulario.
- Costo vencido sigue utilizable con advertencia: no se alteran estados comerciales. Vigencia se evalúa al guardar; el snapshot conserva ese resultado y las fechas. No es un precio garantizado del proveedor.
- La copia guarda versión, moneda, fecha de captura, costo/procedencia por línea, proveedor/referencia, cantidades derivadas en costo de línea, cobertura y totales. Capturar costo no registra compra ni horas trabajadas.

Venta neta = total comercial después de descuentos − IVA incluido. Utilidad bruta estimada = venta neta − costos efectivos de líneas. Margen = utilidad / venta neta × 100, si venta neta > 0; venta cero muestra porcentaje no aplicable, manteniendo la pérdida si hay costo.

Todos los ítems deben tener costo definido para mostrar utilidad total. Cuando falta alguno, mostrar cobertura y mensaje; no sumar costos incompletos como si fueran el costo total. Valor negativo genera advertencia, sin bloqueo ni aprobación de descuento nueva. No hay umbral mínimo configurable en esta entrega.

Emitir conserva la copia ya guardada y el PDF habitual. Cambiar costos, proveedores o catálogo no altera emitidos. Documentos históricos sin cost_snapshot siguen sin estimación: no reconstruir usando tarifas actuales. No hay backfill ni modificación de PDFs previos.

## COS-04 · Dashboard y privacidad

Admin/commercial ven utilidad bruta estimada de aprobados de la misma cohorte de emisión/moneda del dashboard. Solo suman documentos con snapshot completo de esa moneda; se informa cuántos aprobados cubre frente al total. Margen agregado = suma de utilidades / suma de ventas netas cubiertas, no promedio de porcentajes. Si no hay cobertura, «Sin costos completos»; si hay costos pero utilidad cero, cero real. No es ganancia realizada, ingreso contable ni cobro. Falta registrar ejecución, gastos y compras reales.

Reader mantiene resumen comercial sin costos/utilidad. Su consulta no carga cost_snapshot en la agregación. `Quote::$hidden` excluye ese JSON de serialización, vistas internas tienen Gate y las páginas de costo devuelven 403. Los PDFs/preview/historial comerciales conservan los campos anteriores y nunca incluyen costos, márgenes ni proveedor. Los datos internos no se introducen en `lines` ni en el snapshot comercial cliente/emisor.

## Compatibilidad y operación

Migración aditiva `2026_10_16_000002_add_costs_and_quote_profitability`: versiones, unidad/costo actual de catálogo y JSON interno nullable. Registros existentes conservan precio, PDFs, número y secuencia; unidad inicial «Unidad», que se puede cambiar a Hora antes de registrar costos.

Con historial de costos, moneda/tipo/unidad no se pueden reinterpretar: crear otro ítem. Cambiar tasa de venta desvincula el costo actual y exige revisión de precio; no modifica versiones históricas. No hay ajustes masivos de precios, inventario, facturación, adjuntos, cotización cambiaria, registro fiscal ni crédito parcial implementados. Para tratamiento fiscal no resuelto usar «Pendiente».

Actualizar con `scripts/update.sh` y respaldo habitual. No revertir la migración después de usar sin evaluar pérdida de historiales/costos. No requiere dependencias, workers o scheduler nuevos. Los bloqueos de fila deben validarse en PostgreSQL; SQLite de pruebas no prueba concurrencia real. No se modifica Nginx/Cloudflare.

## Fuentes y verificación normativa

Consultadas el 16/10/2026, como base para costeo de operaciones locales ordinarias. Validar con la contadora la condición concreta del crédito fiscal y obligaciones del RUC antes de una función de liquidación.

- [Odoo 19 · Márgenes](https://www.odoo.com/documentation/19.0/es_419/applications/sales/sales/sales_quotations/margin.html): utilidad y porcentaje en propuestas, costo separado del precio.
- [Ley 6380/2019 en portal tributario](https://ekuatia.set.gov.py/es/web/portal-institucional/w/ley-n-6380-19), arts. 86–90: liquidación mensual, débito/crédito, requisitos y tasas. La resta por operación es ilustrativa; no equivale al impuesto mensual a pagar.
- [DNIT · Cartilla IVA](https://www.dnit.gov.py/documents/20123/224724/Cartilla%2BInformativa%2Bsobre%2Bel%2BIVA.pdf/13824efd-fcdb-2414-4eec-dec790a7f00f?t=1684507476257.pdf): material explicativo; no tomar su antigüedad como garantía de reglas posteriores.

No se identificó ni adopta una tasa especial para Solvet. Impuestos a la renta, prorrata, retenciones, importaciones y regímenes especiales requieren contratos propios. Al vender a otra empresa, configurar sus datos/reglas y validar su situación: no heredar supuestos de Solvet como normativa universal.

## Criterios de aceptación

`CostsTest`: ejemplos IVA/margen/recargo/utilidad fija, límite/negativos/monedas/precisión, historial, precio aplicado/opcional, IVA pendiente, dimensiones de catálogo, propio/tercero por hora, snapshot con descuento/manual/cero/desconocido, versión conservada y PDF inmutable, vencidos, formulario Livewire, acceso reader y cobertura de dashboard. La regresión de presupuestos y apariencia se mantiene en la suite general. Ver [validación](../validacion.md).
