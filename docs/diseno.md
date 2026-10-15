# Sistema de diseño · Solvet

## Referencias y dirección

Identidad base: `public/brand/solvet-it-logo.png`, logo aportado por José, conservado íntegro. Referencia cromática: `referencias/referencia-color.png`, lámina Solvet/Encina aportada por José. La lámina sirve para colores y tono; no es un diseño de interfaz ni reemplaza el logo entregado.

Referencias de patrones ERP consolidados:

- ERPNext/Frappe: espacios de trabajo por módulo, navegación persistente y listas de documentos. https://docs.frappe.io/framework/user/en/desk/navigation
- Odoo: vistas de lista/formulario, filtros y acciones contextuales por documento. https://www.odoo.com/documentation/18.0/applications/studio/views.html

Se toman patrones de uso y jerarquía, sin copiar marcas ni interfaces completas. El sistema no es una página promocional: el foco está en consultar, editar, emitir y seguir documentos.

## Paleta de interfaz

Los colores de interfaz son una propuesta derivada de las referencias, ajustada para legibilidad; no constituyen un manual de marca oficial.

| Token | Valor | Uso |
|---|---|---|
| Navy | #102F4F | Texto principal de jerarquía, cabecera de trabajo y PDF |
| Blue | #1769AA | Acción principal, navegación activa, enlaces |
| Sky | #4A9FE8 | Foco y acentos, no texto pequeño sobre blanco |
| Logo blue | #78A5BB | Referencia del isotipo, uso decorativo |
| Sage | #82A6A6 | Referencia del logotipo, uso decorativo |
| Green | #16845C | Aceptado / completado |
| Orange | #C56822 | Atención / pendiente |
| Background | #F3F6FA | Fondo de aplicación |
| Surface | #FFFFFF | Tablas, formularios y paneles |
| Border | #DFE6EF | Separadores y límites |
| Text | #183149 | Cuerpo |
| Muted | #5B6D82 | Etiquetas secundarias |

Fuente de verdad de tokens de interfaz: `public/css/solvet.css`. Filament `primary` usa el mismo Blue. Un cambio de identidad debe actualizar ambos y la plantilla PDF. En esta entrega el PDF utiliza colores explícitos compatibles con Dompdf; no consume variables CSS del navegador.

## Tipografía y densidad

Inter local para interfaz; respaldo system-ui. PDF con DejaVu Sans para caracteres españoles y símbolo de guaraní. Texto habitual 13–14 px, etiquetas 12 px, títulos 24–28 px. Números monetarios alineados a la derecha. Espaciado con escala 4/8/12/16/24/32. Esquinas 8–12 px, sombras casi imperceptibles. No usar tarjetas gigantes ni grandes espacios vacíos donde se necesita una tabla de trabajo.

## Patrones

- Menú lateral claro por áreas y módulo activo destacado.
- Vista general con datos reales y accesos al trabajo, sin métricas inventadas.
- Lista: título, acción principal, búsqueda, filtros, estado con texto y total visible.
- Formulario: cabecera comercial, detalle de ítems y condiciones. Etiquetas visibles.
- Documento emitido: consulta/PDF/historial y acciones válidas; sin edición silenciosa.
- Estados: Borrador gris; Emitido azul; Aceptado verde; Rechazado/Anulado rojo.
- Acciones irreversibles sobre un documento requieren confirmación específica del efecto.
- Módulos futuros se identifican como previstos; no se muestran enlaces que parezcan funcionales.

## Accesibilidad y adaptación

Navegación por teclado y foco visible. El significado nunca depende solo del color. Tablas con cabecera, etiquetas y mensajes de validación. Formularios Filament conservan componentes accesibles. En móvil se apilan paneles y las tablas permiten desplazamiento horizontal. Revisar contraste WCAG AA antes de aprobar nuevos tokens; no aplicar el celeste del logo a texto pequeño sobre blanco.

## PDF

Formato A4, logo, número, emisión, vigencia, emisor, destinatario, referencia, líneas y totales. Precios con IVA incluido; el IVA se desglosa, no se suma otra vez. Cabeceras de tabla repetibles, filas sin división, condiciones y pie. Los borradores llevan marca explícita. Verificar una página, varias páginas, descripción extensa, USD y caracteres acentuados al modificar la plantilla.

## Cambios visuales

Reutilizar los componentes existentes. Preferir hooks CSS estables de Filament. No modificar archivos de `vendor`. Publicar los recursos tras cambios de CSS: `php artisan filament:assets`, o reconstruir imagen. Validar escritorio y ancho móvil de 390 px. Conservar una captura de referencia solo cuando sea entregable solicitado; no sustituir la prueba de interfaz por un mockup.
