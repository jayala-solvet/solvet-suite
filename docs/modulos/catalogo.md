# Catálogo opcional · productos y servicios

Estado: implementado. CAT-01 alta/edición, CAT-02 selección desde presupuesto. Monolito existente, sin dependencias nuevas. No implementa inventario, costos, compras, facturación ni conversiones cambiarias.

## Datos y autorización

`catalog_items`: ID, SKU opcional único hasta 50, nombre obligatorio hasta 200, descripción opcional hasta 1000, tipo product/service, moneda PYG/USD, precio en unidades menores decimal(20,0), IVA 0/5/10, disponibilidad y timestamps. No hay borrado desde interfaz: usar desactivación. SKU vacío se normaliza a null.

Usuarios activos consultan el catálogo según el alcance compartido de la instalación. Admin/commercial crean y editan; reader no escribe. `CatalogService` comprueba Gate, valida y utiliza `QuoteCalculator` para convertir el precio incluido IVA a unidades menores sin float. PYG entero, USD hasta dos decimales y precio con hasta nueve enteros. La interfaz muestra el error de precio en el campo correspondiente.

Guardar genera `catalog.created` o `catalog.updated` con actor, fecha, SKU y disponibilidad en auditoría. No se capturan todas las diferencias del maestro ni hay una pantalla específica de auditoría de catálogo en esta entrega.

## Uso desde una propuesta

Selector opcional, con búsqueda por código/nombre, primeros 25 activos y filtro por moneda. Copia descripción (o nombre cuando está vacía), precio e IVA. Se conserva cantidad/descuento de la línea; elegir un producto no sobreescribe esos ajustes. El usuario puede modificar la descripción y el precio ofrecido. Quitar la selección deja los valores y permite continuar manualmente.

El guardado de presupuesto valida ID y moneda y toma el SKU del maestro. Ítems manuales no requieren crear catálogo. Las líneas conservan precios y descripciones propios; no se actualizan al consultar el maestro. Una línea existente puede conservar referencia a un producto desactivado. Los emitidos son inmutables por el flujo comercial; editar catálogo no modifica su PDF ni datos.

El ID dentro de JSON es procedencia, no una relación que se consulte para reconstruir el documento. No eliminar físicamente maestros desde procesos auxiliares sin evaluar las propuestas que los referencian. El precio del catálogo no constituye una autorización o límite de descuento: comercial puede cotizar manualmente según el alcance actual del producto.

## Criterios y límites

- Alta/edición autorizadas, código repetido rechazado, decimal PYG inválido rechazado, precio USD conserva centavos.
- Mezcla de ítems manuales/catálogo, totales con el calculador compartido y moneda incompatible rechazada sin conversión.
- Cambio/desactivación del catálogo no modifica presupuestos ya guardados en consultas; la selección explícita vuelve a copiar datos actuales.
- No exponer este servicio en API o importación sin la misma validación/autorización.
- Pruebas en `QuoteEntryTest`, revisión visual y recorrido de navegador detallados en `docs/validacion.md`.
