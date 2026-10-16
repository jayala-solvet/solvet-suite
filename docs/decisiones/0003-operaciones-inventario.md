# ADR 0003 · Operaciones, promedio ponderado y componentes privados

Fecha: 16/10/2026. Estado: adoptado; amplía ADR 0002, cuyo flujo de publicación de precio se sustituye por acción explícita separada.

## Decisión

Mantener el monolito y catálogo existentes. Añadir un módulo Operations, documentos separados de compra y pedido, intervenciones ejecutables, saldo y kardex. Un depósito por instalación. La compra no equivale a recepción, la propuesta no reserva, y la aceptación no acredita ejecución/cobro.

La recepción agrega costo efectivo real al saldo. Valor de salida = valor disponible antes de salida × cantidad salida / cantidad física; guardar cantidad/valor/costo aplicado por salida. No inferir saldos en migración ni revalorizar por nuevas cotizaciones. Saldo y reservas se actualizan bajo bloqueo del ítem en orden estable y transacción con movimientos/documentos. Claves únicas/documentos bloqueados hacen idempotentes los reintentos de recepción, entrega, ejecución, devolución y ajuste.

Mantener ocho decimales de unidad menor para costos, BCMath; redondear resultados en moneda al presentar/generar totales. Publicación de precio deja de ser parte de crear referencias. Extras antiguos se mantienen netos; los nuevos permiten IVA separado con migración aditiva.

Conjuntos comerciales se modelan como una línea de venta y componentes en snapshot privado, no como otros ingresos. El pedido materializa esos componentes en intervenciones; agrupación por índice comercial evita duplicar ventas y permite comparar previstos/realizados. Los proveedores/costos continúan fuera de PDF y roles de consulta.

Los servicios comprados se imputan al recibir una compra vinculada a intervención; los propios por ejecución real registrada. Las cantidades no se pueden cumplir dos veces. Cambio de ejecutor antes de ejecutar preserva estimación y exige motivo. Correcciones por devoluciones/anulaciones; no borrar evidencia.

## Alcance y consecuencias

Recepción completa por documento, entrega de todo lo reservado, reserva parcial por disponibilidad. Horas de servicios no son stock. No hay valuación FIFO, múltiples depósitos, lotes/series, prorrata de crédito fiscal, facturación o contabilidad general. Una compra de servicio debe vincularse antes de recibir para imputar al pedido. Estas ampliaciones futuras requieren contrato y pruebas propios.

El esquema mantiene FKs e IDs únicos; actor/estado/cálculos son de servidor. No añade dependencias ni procesos de fondo. Validación funcional local con SQLite no prueba bloqueos concurrentes de PostgreSQL; se registra ese límite y se debe ensayar antes de producción.
