# Historial de versiones

## Sin publicar

### Compras, inventario y ejecución

- Capacidades comprable/vendible/stock solo para bienes; catálogo único con unidades y orígenes de costo independientes.
- Compras confirmadas y recepción completa, envío por cantidad/valor/manual, IVA propio de gastos y precios manuales.
- Promedio ponderado, stock físico/reservado/disponible, reservas parciales, entregas valoradas, devoluciones y ajustes motivados con reintentos protegidos.
- Pedidos desde aceptados, ejecución propia/tercerizada, cambio de ejecutor documentado y comparación de costos previstos/realizados por línea y total.
- Conjuntos con componentes privados sin duplicar ventas; referencias alternativas no publican precio salvo acción explícita.
- Migraciones aditivas sin inventar existencias ni modificar PDFs/snapshots emitidos. Casos A/B automatizados y guía completa.


### Costos y precios

- Costos versionados sobre un único catálogo, unidad/hora, productos de distribuidor, soporte propio y de terceros con proveedor/referencia.
- IVA recuperable previsto/no recuperable/pendiente; precio sugerido por margen sobre venta, recargo o utilidad fija con cálculo decimal.
- Elección de versión al cotizar, costo neto manual opcional y copia privada por documento; utilidad después de descuentos con cobertura y advertencia de pérdida/costo vencido.
- Dashboard con utilidad bruta estimada de aprobados y cobertura de costos. Acceso solo admin/commercial; datos internos fuera de reader/PDF.
- Migración aditiva, contratos y reglas comunes para próximos módulos; sin dependencias nuevas ni cambio de documentos emitidos.


### Dashboard comercial · modelo inicial

- Nueva vista de consulta con período/moneda, total cotizado, monto aprobado y oportunidad vigente sobre emisiones reales.
- Distribución reconciliable por estado y seguimiento de hasta seis propuestas con acceso al detalle; vigencias y cliente original.
- Diseño tipográfico sobrio, adaptable a claro/oscuro y móvil. Dashboard como entrada, con acceso directo a Presupuestos.
- Reglas exactas BCMath; sin mezclar monedas, simular ganancias ni modificar documentos o agregar dependencias/migraciones.


### Carga, catálogo, pago y documento comercial

- Catálogo opcional de productos/servicios con moneda, precio, IVA, código y disponibilidad; propuestas con ítems manuales o copiados y ajustables.
- Alta de cliente inline, ítems compactos duplicables/contraíbles, importe por línea, guardado accesible al desplazarse y aviso de cambios sin guardar.
- Pago contado/crédito con días validados, detalle de pago, entrega, garantía y términos generales configurables en Mi empresa.
- Baja auditada de borradores sin número con confirmación; emitidos protegidos. Rutas y listados excluyen bajas.
- PDF A4 pulido con cabecera compacta y secciones comerciales separadas, sin modificar archivos emitidos.
- Migración aditiva nullable para datos nuevos y catálogo; sin dependencias ni cambios de numeración.


### Refinamiento visual con referencia Supabase / Refero

- Modo oscuro con grises neutros por niveles, cabecera oscura integrada, separadores finos y superficies sin sombra decorativa. Se conserva el acento Solvet y la densidad operativa.
- Adaptación documentada: tipografía/iconografía existentes, foco visible y colores semánticos; sin incorporar estructura promocional ni CSS externo.
- Sin cambios de reglas, documentos PDF, datos, permisos o dependencias.


### Contexto, maquetas y contratos de desarrollo

- Índice de contexto, método de Figma sobre componentes/tokens actuales y plantilla de especificación con permisos, datos, cálculos, persistencia, estados y aceptación.
- Contratos ampliados de presupuestos y maestros, con límites reales de auditoría/concurrencia y fuentes de código/pruebas.
- Laboratorio informado y plan Nginx/Cloudflare documentados, distinguiendo controles implementados y pendientes de producción. Sin cambios a aplicación, esquema o configuración activa.
- Reglas de continuidad con IA, compatibilidad y protección de datos incorporadas a AGENTS; referencias obsoletas del ZIP y roadmap corregidas.


### Presupuestos operativos · base visual aprobada

- Cabecera grafito, superficies neutras en claro/oscuro, iconografía mini en navegación y acento moderado; render y reglas registrados en diseño.
- Inicio en Presupuestos, pestañas por estado con conteos, filtros de moneda/fechas/mes/vencidos, columnas configurables y selección contextual de un documento.
- Detalle consultable con snapshots, ítems, condiciones, importes e historial precargado; guardado de borradores redirige al detalle.
- Totales durante la edición reutilizando el calculador del servidor, emisión confirmada y decisiones con evidencia desde detalle/listado.
- Vista previa PDF inline y descarga del mismo archivo conservado; nueva plantilla neutra sin modificar documentos emitidos ni correlativos.
- Sin migraciones ni nuevas dependencias. Órdenes, soporte y correo siguen fuera de esta entrega.

### Cambios anteriores

- Logo integrado con transparencia en cabecera, menú móvil y acceso: encuadre completo, sin placa clara ni posiciones negativas. El subtítulo adapta su tinta en oscuro conservando el PNG y los colores principales originales.
- Base visual Solvet consolidada: Inter Variable local correctamente aplicada, cabecera de 72 px, navegación de 256 px, escala de espaciado/radios y superficies en claro/oscuro.
- Jerarquía de acciones, documento y referencia en la misma columna, importes tabulares y listados apilados en móvil.
- Formularios de presupuestos, clientes y empresa organizados por secciones; guardado explícito de borradores y detalle de ítems adaptable.
- Componentes Blade compartidos para paneles, indicadores, estados y vacíos; catálogo y reglas para futuros módulos.

- Selector de apariencia clara, oscura y del sistema en el acceso y la barra superior, con preferencia persistente del navegador.
- Colores semánticos para paneles, tablas, formularios, estados, historial, placeholders y foco.
- Pruebas de contraste y controles de apariencia, sin cambios de dependencias, esquema ni reglas comerciales. Los PDF conservan su diseño original.

## 0.1.0 · 2026-10-15

Base inicial del producto. Docker Compose, núcleo por módulos, clientes, presupuestos con IVA incluido, PDF privado conservado, registro de decisiones, identidad Solvet y documentación para desarrollo con IA. Accesos por roles y alta por CLI. Datos ficticios opcionales. Ver `docs/validacion.md` para alcance de pruebas ejecutadas y pendientes.
