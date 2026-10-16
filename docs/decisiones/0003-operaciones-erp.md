# ADR 0003 · Operaciones ERP incrementales

Estado: propuesto para implementación, 16/10/2026.

## Contexto verificado
Solvet Suite usa Laravel/Filament, PostgreSQL, un catálogo único (`catalog_items`), costos versionados (`cost_profiles`) y presupuestos (`quotes`) con snapshot económico. No existen aún compras, movimientos de inventario ni ejecución con costos reales. Los presupuestos emitidos y PDFs no se reescriben.

## Decisión
Extender el monolito modular sin reemplazar Catálogo, Costos ni Quotes, aplicando procesos de ERP tipo Odoo/ERPNext, pero con complejidad acorde a Solvet-IT.

Separar **tipo comercial** (producto físico / servicio) de **origen de costo** (compra a proveedor / trabajo propio / servicio tercerizado) y de **capacidad operativa** (comprable, vendible, sujeto a stock). Un servicio puede ser comprable y vendible sin stock. Un producto físico puede venderse sin controlar stock, según configuración.

## Flujo objetivo
Catálogo → presupuesto con costo estimado → aceptación → orden de venta → necesidades de compra/prestación → orden de compra → recepción de bienes o conformidad del servicio → movimientos de inventario / partes de trabajo → costos reales → entrega → facturación futura → comparación de margen estimado vs real.

No deducir cobros, compras ni facturas reales de la aceptación del presupuesto.

## Reglas de datos
- No borrar ni reinterpretar `cost_profiles`. Mantener costos de origen e históricos.
- Preservar líneas, snapshots, correlativos, PDFs y estados de presupuestos emitidos.
- Registrar proveedores como entidades propias; el nombre histórico del proveedor permanece congelado en versiones y snapshots.
- Registrar movimientos de inventario inmutables y auditados (entrada, salida, devolución, ajuste), no editar solo un saldo.
- Costos adicionales capitalizables (por ejemplo flete de adquisición) se distribuyen a bienes recibidos; mano de obra posterior de configuración se imputa a operación/servicio salvo decisión contable explícita.
- Costos reales por operación deben provenir de recepción, compras, horas ejecutadas u otros documentos de costo, nunca del costo sugerido actual.
- IVA: compra con y sin crédito recuperable; no duplicar IVA de venta. Validar con asesoría tributaria paraguaya antes de emitir facturas fiscales.
- Dinero: BCMath y unidades menores; cero conocido != costo desconocido. Monedas distintas no se suman ni convierten sin tipo de cambio explícito.
- Valorización inicial de inventario: costo promedio ponderado, si se aprueba. Definir devoluciones, ajustes y política de costos antes de activar stock.
- Operaciones con estado y autorización de servidor; idempotencia para recepciones y movimientos.
- Los documentos transaccionales guardan precio, impuestos, cantidad, moneda y costo en snapshots al confirmar.

## Orden de entregas
1. Catálogo: comprable/vendible/control stock, defaults compatibles y validación; no permitir stock en servicios.
2. Proveedores, órdenes de compra, líneas y estados con pruebas de permisos.
3. Recepciones, movimientos y saldo/valorización; pruebas de concurrencia en PostgreSQL.
4. Orden de venta/ejecución de servicios y captura de costos reales.
5. Comparativa rentabilidad estimada vs real e integración comercial.
6. Facturación fiscal/contable tras definición funcional y normativa.

Cada fase incluye migraciones aditivas, pruebas de regresión, documentación y revisión visual. No desplegar ni ejecutar migraciones en un servidor como efecto de este ADR.

## Referencias conceptuales
- https://www.odoo.com/documentation/19.0/
- https://docs.frappe.io/erpnext/
