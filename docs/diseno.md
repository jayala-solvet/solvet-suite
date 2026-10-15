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

**Inter Variable** local para interfaz; ese es el nombre exacto declarado en los archivos `@font-face` instalados. El proveedor del panel y el CSS usan el mismo nombre. El historial carga la hoja de la fuente local. Respaldo system-ui. PDF con DejaVu Sans para caracteres españoles y símbolo de guaraní.

| Elemento | Regla |
|---|---|
| Título de página | 28 px escritorio / 24 px móvil, peso 650 |
| Título de sección | 14 px, peso 600–650 |
| Texto y campos | 13–14 px, interlineado mínimo 1.5 |
| Etiquetas y descripción | 12–13 px; nunca usar opacidad para atenuar texto |
| Cabecera de tabla | 12 px en Filament / 11 px en tablas compactas propias |
| Importes | Cifras tabulares, alineadas a la derecha en tabla |
| Espaciado | Tokens 4 / 8 / 12 / 16 / 20 / 24 / 32 px |
| Radios | 6 px estados, 8 px controles, 12 px superficies |
| Campos y acciones principales | 40 px de alto |
| Selector de tema | 32 px en escritorio; 44 × 44 px con puntero táctil |
| Animación | 120 ms solo para color; respetar movimiento reducido |

No usar tarjetas gigantes ni grandes espacios vacíos donde se necesita una tabla de trabajo. Las sombras son discretas; borde y fondo organizan las superficies. Las separaciones decorativas no sustituyen el borde visible de los campos.

## Estructura de pantalla

Cabecera de 72 px y barra lateral de 256 px en escritorio, colapsable mediante el control nativo de Filament. El logo conserva el archivo original: se ajusta la ventana CSS y la escala, sin redibujarlo. En oscuro usa una base clara proporcionada al tamaño del logo.

Contenido limitado a 1440 px, con márgenes adaptados al ancho disponible. Aplicar el espaciado vertical a `.fi-page-header-main-ctn`, no también a `.fi-page`: el segundo duplicaría la separación nativa. Usar un título claro, una descripción breve si aporta contexto y una acción principal en el encabezado.

El selector de tema se integra antes del avatar mediante un render hook del panel. No copiar las vistas internas de Filament ni modificar `vendor` para ajustar la estructura.

## Patrones

- Menú lateral por áreas, superficie acorde al tema y módulo activo con fondo, texto y borde de acento.
- Vista general con datos reales y accesos al trabajo, sin métricas inventadas.
- Lista: título, acción principal, búsqueda, filtros, estado con texto y total visible.
- Las acciones de fila se presentan como enlaces o botones secundarios neutros. Reservar el botón azul para la acción principal del contexto; no repetirlo en cada fila.
- Usar documento y referencia como celda de dos líneas. Identificación y monto tienen más peso; el texto descriptivo usa el token secundario, con contraste suficiente.
- Formulario: cabecera comercial, detalle de ítems y condiciones. Etiquetas visibles.
- Las secciones llevan título, una descripción útil y un icono existente cuando facilita reconocerlas. Mantener el orden visual y el orden del teclado. Los campos pasan a una columna en móvil.
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


## Apariencia clara, oscura y del sistema

Se conserva el diseño claro aprobado: navegación, densidad, tipografía, componentes y marca. El modo oscuro adapta roles de color mediante `:root.dark`; no invierte la página ni aplica filtros al logo. El logo original usa un soporte claro en oscuro para mantener visible su subtítulo.

| Rol | Claro | Oscuro |
|---|---|---|
| Fondo | #F3F6FA | #0B1220 |
| Superficie | #FFFFFF | #111F31 |
| Cabecera de tabla | #F6F8FB | #182B43 |
| Texto | #183149 | #E4EDF8 |
| Título | #102F4F | #F4F8FF |
| Secundario y placeholder | #5B6D82 | #A8B8CE |
| Enlace y selección | #1769AA | #8FCBFF |
| Botón principal | #1769AA | #62B1FA |
| Texto del botón | #FFFFFF | #08213A |

El selector accesible ofrece claro/oscuro/sistema y utiliza el evento nativo `theme-changed` de Filament. Filament conserva `filament:theme:mode` en almacenamiento local y aplica el tema antes del primer pintado. El primer acceso sin preferencia utiliza claro. Sistema sigue cambios del sistema operativo en tiempo real; una elección explícita permanece al recargar.

El historial HTML comparte la preferencia, aplica su clase antes de cargar los estilos y escucha cambios de sistema y almacenamiento desde otras pestañas. No se agrega una librería de temas ni una preferencia en la base de datos.

### Contraste y nuevas pantallas

Los tokens se validan para texto normal con una razón mínima de 4.5:1 y para foco con 3:1 frente a sus superficies. La fórmula corresponde a luminancia relativa sRGB; ver [W3C, contraste mínimo](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html). Las pruebas comparan los valores sin redondear antes de decidir si cumplen. Esto comprueba estas combinaciones y no representa una auditoría integral de accesibilidad de toda la aplicación.

No usar colores literales para texto/superficies de nuevas vistas. Usar `--solvet-text`, `--solvet-heading`, `--solvet-muted`, `--solvet-surface`, `--solvet-bg` y pares de estado. Conservar etiquetas junto al color. Los bordes decorativos y el logo no se tratan como texto normal. No sobrescribir estados inválidos/deshabilitados ni anillos de validación nativos de Filament.

Los PDF son documentos independientes con paleta clara estable: una elección de tema no cambia un documento comercial ni modifica sus copias emitidas.

Antes de aprobar cambios, comprobar login, vista general, listas, creación/edición, placeholders, validación, menús, confirmaciones e historial, además del ancho móvil. Ver `pruebas-apariencia.md`.

La hoja de aplicación y la de historial incluyen una versión derivada del contenido CSS en la URL. Mantener ese mecanismo al publicar cambios; evita mezclar un selector nuevo con colores de una hoja anterior almacenada en caché.

## Componentes y cambios futuros

Ver [catálogo de componentes](componentes-ui.md). Reutilizar primero los componentes nativos de Filament para recursos; los componentes `x-ui.*` cubren las vistas propias. No agregar una segunda biblioteca de interfaz ni un compilador de recursos para cambiar estilos.

Una nueva pantalla debe presentarse en claro y oscuro, con datos vacíos y reales de prueba, textos extensos, errores y ancho móvil. La adaptación nativa de listados apila las filas por debajo de 640 px; las tablas de consulta del panel y del historial mantienen desplazamiento interno. No permitir desplazamiento horizontal de toda la página.

Si un nuevo módulo necesita un patrón que no está contemplado, documentarlo aquí y agregarlo a la base compartida antes de repetirlo en pantallas aisladas.
