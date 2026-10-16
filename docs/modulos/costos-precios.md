# Costos y precios · contrato vigente

Estado: implementado sobre el catálogo único; ampliado por [Operaciones](operaciones.md), OP-01…OP-08. El tipo bien/servicio es independiente del origen del costo: compra de bien, servicio de tercero o trabajo propio. Un N1/N2 admite múltiples referencias sin duplicar el ítem.

## Contexto y permisos

José confirmó IVA venta 10%, precios de distribuidores con IVA incluido y soporte propio/externo con factura del tercero. No se confirmó régimen de impuesto a la renta. Crédito recuperable **previsto** es una hipótesis de costeo; no prueba factura válida ni declaración fiscal. Referencias fiscales y limitaciones al final.

Admin/commercial activos consultan/crean/publican costos, Gates view-costs/manage-costs en recurso y servicio. Reader no consulta costos/proveedor/utilidad ni recibe los JSON privados. Vistas/PDF comerciales mantienen sus campos anteriores. No hay edición/borrado de versiones desde aplicación: crear otra conserva el historial.

## COS-01 · Versiones de costo

| Dato | Regla |
|---|---|
| Ítem | Catálogo existente; servidor copia moneda, unidad e IVA venta |
| Origen | purchase para bien, own/external para servicio; externo requiere ítem comprable |
| Proveedor/RUC | Nombre hasta 200 obligatorio salvo propio; RUC hasta 30, referencia hasta 200 |
| Costo unitario informado | Compra/tercero con IVA incluido; propio interno sin IVA crédito |
| IVA compra | 0/5/10 y recuperable/no recuperable/pendiente; propio exige 0/no recuperable |
| Gastos unitarios | Importe con IVA incluido y tratamiento/tasa propios; no duplicar envío |
| Fecha/vigencia | Fecha no futura, vigencia opcional >= hoy y >= fecha |
| Regla | Margen sobre venta %, recargo sobre costo %, utilidad fija o precio final manual IVA incluido |
| Notas | Internas, hasta 1000 |

Todos los importes ingresados en PYG son enteros; USD hasta dos decimales, nueve enteros. Porcentajes hasta dos decimales; margen <100%, recargo <=1000%, sin negativos. Cero explícito permitido. Pendiente de IVA conserva costo/precio derivados null.

`CostService::create` guarda versión y evento cost.created. **Nunca cambia precio o referencia publicada.** El antiguo argumento apply_price=true ahora se rechaza con mensaje para usar la acción explícita. La interfaz no ofrece publicación al crear. Las versiones y precios ya existentes se conservan al migrar.

`applyPrice`: acción confirmada separada; bloquea ítem, revisa moneda/unidad, vigencia y costo definido, calcula precio con IVA actual de venta, actualiza price_minor/current_cost_id y registra cost.price_applied. No cambia presupuestos o stock. Crear una nueva cotización no desplaza las demás. Precio actual comercial puede diferir de precio sugerido de una versión por decisión explícita.

Proveedor/RUC/referencia se copian en cada versión; no hay maestro de proveedores ni validación tributaria remota. Una cotización no es una compra.

## COS-02 · Fórmulas y desglose

`CostCalculator`, BCMath sin float. Importes base en unidades menores enteras; costo efectivo por unidad conserva ocho decimales de unidad menor.

- Costo base recuperable = bruto × 100 / (100 + tasa compra); no recuperable = bruto.
- Gastos efectivos usan su propia tasa/tratamiento; costo efectivo = base + gastos efectivos, una vez.
- Propio: costo interno sin IVA crédito; no asumir tiempo gratuito.
- Margen m: venta sin IVA = costo efectivo / (1 − m/100).
- Recargo r: venta sin IVA = costo efectivo × (1 + r/100).
- Utilidad fija u: venta sin IVA = costo efectivo + u.
- Precio final manual: conservarlo y derivar venta sin IVA, utilidad y margen.
- Precio final = venta neta × (1 + IVA venta/100), redondeado hacia arriba a unidad menor para reglas calculadas. El precio manual se conserva exactamente.

La pantalla distingue importe informado más gastos, crédito previsto, costo efectivo, venta neta, IVA de venta, precio final, utilidad bruta y margen. Advierte precio por debajo de costo. Redondeo de presentación no reemplaza los decimales internos. Límite de precio del catálogo existente se conserva.

Ejemplo compra 500000 IVA 10% recuperable, sin gastos: costo 454545,45454545. Recargo 10% → final 550000; margen 20% → final 625000; utilidad fija 50000 → final 555000. Son reglas diferentes, no un porcentaje recomendado de Odoo.

## COS-03 · Copia y rentabilidad de propuesta

`QuoteProfitability` captura cost_snapshot v2 privado. Selector de costo no cambia automáticamente el precio comercial de la línea. Al seleccionar catálogo se propone su precio vigente y referencia publicada, visibles/editables; se puede elegir otra referencia compatible o dejar costo desconocido. Fuera de la interfaz, consumidores antiguos que omiten cost_profile_id pueden seguir tomando la referencia publicada; null explícito significa sin referencia. Una nueva versión no publica esa referencia.

Línea libre: costo neto interno opcional; vacío desconocido, cero explícito definido. Conjunto: componentes internos sustituyen el costo principal, multiplicados por la cantidad comercial; sus precios no generan otros ingresos. Hasta 30 por línea y 300 por presupuesto; clasificación, cantidades y procedencia se copian en privado. Validación de tipos/IDs/unidad/moneda y consultas agrupadas para componentes. Detalles en [OP-07](operaciones.md).

Suma de costos exactos, redondeo del total a unidad menor; venta neta = total comercial después de descuentos − IVA incluido. Utilidad bruta = venta neta − costo total. Margen = utilidad / venta neta, si venta >0. Se mantiene margen de dos decimales para consumidores anteriores y cinco decimales de estimación precisa. Solo mostrar utilidad total con cobertura completa. No inferir costo cero ni calcular ganancia neta.

Snapshots conservan proveedor/RUC, referencia, costo/IVA/gastos, origen, cantidades y fecha. Venta y condiciones comerciales permanecen en las líneas del presupuesto. Al editar se restaura referencia/componentes propios, no maestros actuales. Emitidos y PDFs no se recalculan; históricos v1 sin nuevos campos continúan legibles. Costos vencidos se advierten, sin bloquear automáticamente presupuestos.

## COS-04 · Dashboard y ejecución

Dashboard sigue mostrando utilidad **estimada** de aprobados con snapshot completo y cobertura explícita por cohorte/moneda. No mezclar esa cifra con compras reales. La comparación con ejecución está en Pedidos: salidas valoradas más servicios registrados, con resultado final solo al completar cantidades; reader no lo recibe. Contrato [OP-08](operaciones.md).

Ningún costo/proveedor aparece en PDF comercial, líneas públicas o serialización de Quote. No generar compras, stock o impuestos al guardar/emitar un presupuesto.

## Compatibilidad

000002 conserva su implementación histórica. 000003 agrega operaciones/capacidades; 000004 agrega IVA de gastos con default 0/no recuperable: extras anteriores siguen siendo netos, sin cambiar valores. No modificar migraciones aplicadas. Cambios de moneda/tipo/unidad con historial requieren otro ítem; IVA venta nuevo desvincula referencia publicada para revisión. Actualizar con respaldo habitual; no revertir tras uso sin evaluar pérdida de datos.

Sin dependencias/colas nuevas. No se implementan crédito fiscal parcial, importaciones, liquidaciones, contabilidad general o validación de factura. Usar Pendiente si no está resuelto el tratamiento fiscal.

## Referencias y pruebas

Consultadas 16/10/2026: [Odoo 19 · Márgenes](https://www.odoo.com/documentation/19.0/es_419/applications/sales/sales/sales_quotations/margin.html), [Ley 6380/2019 arts. 86–90](https://ekuatia.set.gov.py/es/web/portal-institucional/w/ley-n-6380-19), [cartilla IVA DNIT](https://www.dnit.gov.py/documents/20123/224724/Cartilla%2BInformativa%2Bsobre%2Bel%2BIVA.pdf/13824efd-fcdb-2414-4eec-dec790a7f00f?t=1684507476257.pdf). El IVA mensual considera todas las operaciones y ajustes, no una resta aislada. Validar con la contadora la situación concreta del RUC antes de una función fiscal. Configuración Solvet no es normativa universal para otras instalaciones.

`CostsTest` conserva cálculo, roles, snapshots y PDFs; `OperationsTest` cubre A/B, publicación explícita sin revalorizar, capacidades/componentes y ejecución. Ver [validación](../validacion.md).
