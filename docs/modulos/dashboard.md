# Dashboard comercial · modelo inicial

Estado: primera versión funcional de consulta. Ruta `/app/dashboard`, navegación Comercial → Dashboard. El acceso inicial abre Dashboard y `/app/workspace` conserva compatibilidad. No es un módulo contable.

## DSH-01 · Consultar resumen

Objetivo: saber cuánto se emitió, qué parte fue aceptada y qué propuestas requieren seguimiento. Consume Quotes sin modificar documentos, secuencias, PDFs ni auditoría. No genera una copia persistida de indicadores.

Actores: admin, commercial y reader activos consultan todos los presupuestos de la instalación. Página y servicio verifican acceso en servidor; sesiones anónimas van al login e inactivos reciben 403. Solo admin/commercial ven el acceso a crear presupuesto, cuya autorización continúa en QuoteResource/QuoteService.

| Entrada | Inicial / validación | Persistencia |
|---|---|---|
| Moneda | PYG; solo PYG o USD | Estado Livewire y query string `currency` |
| Período | month; month/quarter/year/all | Estado Livewire y query string `period` |

Filtros inválidos se rechazan con validación, sin consultas arbitrarias ni escrituras. No hay datos sensibles en los parámetros. Mes/trimestre/año son calendarios, desde su inicio hasta el momento de consulta; historial incluye todo lo emitido hasta ese momento. La zona es America/Asuncion. Se usa **issued_at**, no created_at ni updated_at.

## Contrato de indicadores

Todos los indicadores, composición y seguimiento usan la misma cohorte: moneda seleccionada, emisión en el período, estados issued/accepted/rejected/cancelled y sin baja lógica. Borradores y fechas futuras quedan fuera. Se informa el **estado actual** de documentos emitidos en ese período; no es una foto histórica ni «aceptaciones ocurridas durante el período». Una aceptación posterior cambia la composición de la cohorte original.

| Indicador / segmento | Regla |
|---|---|
| Total cotizado | Suma de total_minor de todos los documentos de la cohorte, incluidos rechazados y anulados para reconciliar su destino |
| Monto aprobado | Suma de total_minor con estado accepted |
| Oportunidad vigente | Suma de total_minor con estado issued y valid_until igual o posterior a hoy |
| Vencidos sin respuesta | issued con valid_until anterior a hoy; no se altera el estado |
| Rechazados / anulados | Estado rejected / cancelled; no forman parte de oportunidad vigente |
| Porcentaje de importe | importe del segmento × 100 / total cotizado; BCMath a 2 decimales truncados; total cero → 0 |
| Vencen pronto | issued cuya vigencia va de hoy a hoy + 7 días, ambos incluidos |

Los cinco segmentos son excluyentes y suman exactamente el total. Ejemplo PYG: aprobados 300 + vigentes 100 + vencidos 200 + rechazados 400 + anulados 500 = cotizado 1500. Oportunidad vigente 100. No sumar nuevamente los aprobados al cotizado.

Importes **con IVA incluido**, unidades menores enteras, BCMath; nunca float ni conversión de monedas. PYG sin decimales, USD dos. Suma por cursor de columnas mínimas para evitar pérdida de precisión en agregados SQLite y cargar líneas/snapshots innecesarios. No se agregan dependencias, migraciones, caché ni jobs. Con más volumen, medir el recorrido de la cohorte y diseñar agregados exactos/índices según evidencia; el cálculo actual recorre los documentos seleccionados.

Aprobación es decisión interna registrada con evidencia. **No equivale a cobro, ingreso contable ni ganancia**. No hay costos de compra/servicio ni pagos registrados: no se simula margen, beneficio, objetivo mensual, probabilidad, crecimiento ni proyección temporal. Próxima etapa propuesta: acordar captura de costos, base con/sin IVA y reglas de margen antes de implementar rentabilidad.

## Interfaz y acciones

- Franja de tres cifras tipográficas con separadores, sin mosaicos ni tarjetas de colores. Superficies, Inter, marca e iconos ya aprobados.
- Distribución por importe en barras horizontales finas, todas con texto y valor; verde moderado únicamente en aprobados. Las barras son decorativas para lectores de pantalla; la información exacta permanece en texto. Redondeo visual puede no sumar 100%.
- Seguimiento: hasta seis emitidos sin respuesta, ordenados por valid_until y luego ID. Vencidos primero. Muestra cliente original del snapshot, referencia, asunto, importe y vigencia; clic abre detalle autorizado. Vencidos siguen en la cola para evaluar cierre/revisión, sin reactivación automática.
- Se explicita cuando hay más de seis y se ofrece enlace al listado, sin fingir que aplica filtros de dashboard a la tabla.
- En selección vacía: ceros y mensaje para cambiar filtros/ir a Presupuestos. Con emisión de importe cero: distribución de barras cero y conteos reales.
- Filtros con etiqueta, aviso de carga, enlaces con foco, claro/oscuro y disposición vertical en móvil. No hay controles sin función ni módulos futuros visibles.
- Fecha/hora de consulta visible. No hay polling: recargar para datos de otras sesiones. Las consultas de agregación/seguimiento pueden observar cambios concurrentes entre sí; es un resumen operativo, no un cierre conciliado o exportación contable.
- Salida escapada en Blade, sin render HTML de asuntos/clientes. Lectura no crea eventos de auditoría; seguimiento lleva al detalle con su historial existente.

## Compatibilidad, operación y aceptación

Cambio aditivo sin escritura de datos ni cambios de despliegue. Actualización habitual reconstruye app/web y publica CSS de la misma versión. Reversión de código no requiere revertir esquema. No modifica la configuración de Nginx/Cloudflare ni controles de producción pendientes.

`CommercialDashboardTest` verifica partición exacta, fechas de emisión/moneda/estado, exclusión de borradores/bajas/futuros, frontera de vigencia hoy, períodos, totales por encima de precisión JS, cero, snapshot, límite/orden de seguimiento, filtros Livewire, permisos e inactivos. Comprobación visual real en claro/oscuro a 1512 y 390 px y navegación a detalle/alta. Evidencia y límites en [validación](../validacion.md).

Referencias visuales aportadas el 16/10/2026: estudios de Ventriloc y ui/shadcn. Se adopta jerarquía tipográfica, neutros y datos sin cromo innecesario. No se importan prompts/CSS de terceros, tipografías, tarjetas redondeadas, rojo decorativo ni estructura de landing. Esta versión es la base para revisión, no una sustitución del sistema de diseño aprobado.
