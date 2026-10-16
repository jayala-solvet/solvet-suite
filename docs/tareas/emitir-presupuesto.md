# Preparar y emitir un presupuesto

1. En **Mi empresa**, el administrador revisa razón social, RUC y contacto del emisor. En **Clientes**, registrar o completar el destinatario.
2. Abrir **Presupuestos → Nuevo presupuesto**. Elegir cliente y moneda, completar asunto y vigencia.
3. Agregar productos o servicios, cantidades, precios, descuentos e IVA incluido. Revisar el resumen calculado. PYG admite enteros; USD hasta dos decimales.
4. Completar forma de pago, entrega y garantía en condiciones. **Guardar borrador** abre el detalle; todavía no hay número comercial.
5. Desde **Acciones del documento → Vista previa PDF**, revisar el documento. Si necesita ajustes, usar **Editar borrador**.
6. Presionar **Emitir presupuesto** y confirmar. El sistema asigna el siguiente correlativo, captura cliente/emisor y conserva el PDF. El documento deja de ser editable.
7. Usar **Descargar PDF** para compartir por el canal acordado con el cliente. Emitir no envía correo.
8. Al recibir respuesta, usar **Registrar decisión** y escribir fecha, contacto y referencia de la aceptación, rechazo o anulación. Consultar **Historial** para revisar actor y actividad.

Búsqueda y filtros permiten localizar el documento por correlativo, asunto, nombre actual/original del cliente, moneda o fecha de emisión. Las pestañas cuentan documentos por estado en toda la instalación. Una selección habilita acciones para ese documento; no hay operaciones masivas de emisión.

Los documentos vencidos sin respuesta se identifican con advertencia y filtro; no se convierten automáticamente en rechazados. El PDF emitido se conserva incluso si después cambian los datos del cliente o la apariencia del sistema.

## Carga ampliada

- Si falta el cliente, usar el botón de alta junto al selector: **Crear y seleccionar** mantiene la propuesta en curso.
- El catálogo es opcional. En **Catálogo**, registrar productos/servicios con su moneda, precio e IVA. En cada ítem del presupuesto, elegir uno o continuar con **Carga manual**. Se pueden mezclar ambos y duplicar ítems.
- Revisar importes por ítem y total. Cambiar moneda no convierte precios; quitar la selección de catálogo o elegir un producto de la moneda correcta.
- En **Pago y entrega**, elegir Contado, Crédito (1–365 días) o A convenir. Usar detalle del pago para aclarar medio, anticipo y desde qué fecha se cuentan los días.
- Ajustar entrega/garantía y expandir **Términos y condiciones** si hace falta. La base general se configura en **Mi empresa → Presupuestos** y se copia solo a propuestas nuevas.
- Para retirar un presupuesto sin emitir, **Acciones → Eliminar borrador** o el botón de edición. Requiere confirmar y conserva auditoría; no está disponible en emitidos.

## Costos internos opcionales

Antes de cotizar, cargá el producto o servicio en Catálogo (Unidad u Hora). En Costos y precios registrá costo propio/distribuidor/tercero, IVA, proveedor cuando corresponda y objetivo de margen. Podés aplicar su precio al catálogo. Para cambiar un costo, creá otra versión: no se modifica la anterior.

En el presupuesto seleccioná catálogo y la versión interna que corresponda (propio o tercero). Elegir una versión copia su precio sugerido; podés ajustar precio/descuento y revisar la rentabilidad. Para una línea manual, indicá el costo interno neto de IVA recuperable o dejalo vacío si no lo conocés. Sin todos los costos no se muestra utilidad total; cero es una declaración explícita de costo cero.

El resumen de rentabilidad es interno para admin/commercial y no se imprime en el PDF. Emitir conserva la copia de costos guardada; no convierte la estimación en resultado real ni registra compra/cobro. Ver [contrato de costos](../modulos/costos-precios.md).
