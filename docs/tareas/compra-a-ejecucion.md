# Compra, presupuesto y ejecución

## Preparación

En Catálogo registrá los equipos como **Bien / producto físico**, comprable/vendible y con control de stock. Los registros anteriores empiezan sin control ni saldo: habilitá control cuando corresponda. Si ya tenés equipos, usá Inventario → Ajustar saldo → Entrada / saldo inicial, con cantidad, costo efectivo y motivo/documento; no cargues una compra ficticia para inventar existencias.

N1/N2: **Servicio**, unidad Hora, comprable/vendible y sin stock. Un único N1 admite referencias de costo propio y de varios proveedores.

En Costos y precios creá alternativas. El costo informado a terceros incluye IVA; indicá su tratamiento y el IVA de gastos adicionales por separado. Trabajo propio usa costo interno, IVA de compra 0 y no recuperable. Elegí margen, recargo, utilidad fija o precio final manual. Crear una referencia conserva alternativas y no cambia catálogo. Para publicar precio usá su acción confirmada **Aplicar precio al catálogo**.

## Caso A · Jorge vende seis horas N1

1. Referencia de costo de N1: origen Servicio de tercero, proveedor Jorge Marecos, su RUC/referencia, costo **83000 por hora**, IVA 10% recuperable para el ejemplo. No cargar 498000 en el costo unitario.
2. Presupuesto: N1, cantidad **6**, referencia de Jorge, venta **110000 por hora** con IVA 10%. Total cliente 660000, costo efectivo aproximado 452727 y utilidad bruta aproximada 147273.
3. Emití y registrá aceptación. Desde acciones del presupuesto elegí **Confirmar pedido**.
4. Nueva compra: Jorge/RUC/documento, N1, **6 × 83000**, IVA 10% recuperable, y seleccioná la intervención del pedido. Total proveedor 498000. Vinculá el pedido antes de recibir.
5. Confirmá compra y luego **Confirmar recepción**: registra las seis horas externas en el pedido, sin stock.
6. En el pedido → Más acciones → **Cerrar ejecución**. Verificá el margen 24,54545% sobre venta sin IVA. No vuelvas a registrar esas horas como trabajo propio.

## Caso B · equipo instalado por Solvet

1. Referencia estimada del equipo: compra 500000 con IVA 10% recuperable, gasto de envío por unidad 20000 con IVA 10% recuperable. Costo efectivo aproximado 472727.
2. Referencia N1 propia: costo interno 65000 por hora, sin IVA crédito.
3. Presupuesto: podés usar líneas separadas de equipo y configuración, o una línea **Equipo instalado y configurado**, cantidad 1, precio final **660000** con IVA 10%. Para agrupar, agregá componentes internos: un equipo con su referencia y una hora N1 con referencia propia. Los componentes no tienen un segundo ingreso.
4. Revisá costo total estimado aproximado 537727 y utilidad bruta 62273. Emití, aceptá y confirmá el pedido.
5. Compra de equipo: una unidad a 500000, IVA 10% recuperable. Cabecera de envío **20000 con IVA 10% recuperable**, reparto por cantidad. Otros gastos de línea **0**: no repetir el envío. Vinculá la intervención de equipo al pedido si querés conservar esa referencia operativa.
6. Confirmá y recibí compra: ingresa una unidad, valor aproximado 472727. El costo estimado del presupuesto sirve de comparación; no se agrega otra vez al costo real.
7. Pedido → **Reservar disponibles** y **Confirmar entrega**: sale la unidad al promedio aplicado y se libera la reserva.
8. Más acciones → **Registrar trabajo propio**: intervención N1, una hora, costo interno 65000 y detalle real del trabajo.
9. Más acciones → **Cerrar ejecución**: resultado bruto aproximado 62273, con todas las cantidades cumplidas.

Si configurará un tercero, seleccioná su referencia al presupuestar o definí el ejecutor en el pedido antes de ejecutar, con motivo. Registrá su compra vinculada y recepción. No cargues también una ejecución propia para esa misma intervención.

## Correcciones y límites de esta versión

- Compra borrador editable; confirmada pendiente puede anularse con motivo. Una recibida se corrige por devoluciones/anulación de ejecución, manteniendo el origen.
- Inventario → bien → **Registrar devolución**, elegí movimiento original, cantidad y motivo. Devolución del cliente reabre el pedido; no emite nota de crédito.
- Anular ejecución conserva el registro y reabre el pedido. No anula la compra del proveedor.
- La reserva puede ser parcial si falta stock. La entrega procesa todo lo actualmente reservado. Para recibir parcialmente un proveedor, usá documentos de compra separados.
- Costos/márgenes solo admin/commercial. Reader consulta cantidades sin costos. PDF al cliente solo muestra líneas comerciales, precio e IVA.
- El resultado compara venta acordada con costos registrados; no acredita cobros ni calcula ganancia neta. No se generan facturas o declaraciones fiscales.

Contrato y reglas de precisión: [Operaciones](../modulos/operaciones.md).
