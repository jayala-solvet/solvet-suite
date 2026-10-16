# ADR 0002 · Versiones de costo y copias internas de presupuesto

Fecha: 16/10/2026. Estado: adoptado para la primera versión. Sin cambio de stack o dependencias.

## Problema

Un ítem puede tener costo propio o varios proveedores, cambiar de precio y necesitar utilidad estimada al cotizar. Consultar el costo actual para reconstruir presupuestos anteriores cambia artificialmente su rentabilidad. Introducir costos en las líneas comerciales expone información al PDF o usuarios de consulta.

## Decisión

Mantener un único catálogo. `cost_profiles` tiene versiones con procedencia, unidad, moneda, hipótesis de IVA y reglas de precio. Catálogo apunta a una versión actual mediante FK nullable; el perfil también referencia su ítem. Estas relaciones bidireccionales de datos son una excepción acotada al criterio inicial de evitar ciclos: no crean llamadas recursivas de servicios ni un framework de módulos.

`CostService` es el escritor de versiones y publica precio/costo actual bajo bloqueo del ítem. `CatalogService` consulta existencia de versiones para proteger la identidad monetaria y dimensional del ítem, sin invocar CostService. No se permiten borrar versiones desde la aplicación. Para nueva moneda/unidad/tipo con historial, crear otro ítem. No instalar un maestro de proveedores ni un módulo de compras por anticipación; nombre/RUC/referencia pertenecen a cada versión.

`QuoteService` calcula ventas con el calculador existente y solicita a `QuoteProfitability` una copia privada de costos. El JSON es nullable y oculto en serialización, no pertenece al snapshot comercial o líneas públicas. Emitidos no se recalculan. Permisos de costos son separados de las consultas comerciales.

## Consecuencias

La estimación se puede auditar por documento y versión; contratos futuros de compras/ejecución deben añadir costos reales y comparación con estimados. No es contabilidad fiscal. Precio sugerido y costo efectivo se calculan con BCMath; costo unitario retiene ocho decimales de unidad menor y costo de línea redondea con la cantidad. La venta mantiene las reglas anteriores. No cargar costos faltantes como cero ni presentar utilidad con cobertura incompleta.

La FK circular requiere crear primero versiones y luego agregar current_cost_id. La migración inversa elimina primero ese vínculo; revertir después de uso pierde datos y requiere evaluación. El diseño evita migrar documentos históricos o renumerar. Bloqueos concurrentes se validan con PostgreSQL antes de producción; SQLite solo prueba reglas funcionales.
