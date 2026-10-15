# Sistema de diseño · Solvet

## Base aprobada

El 15/10/2026 José aprobó una dirección operativa sobria para presupuestos. La referencia es [presupuestos-base.png](referencias/presupuestos-base.png). Es un render conceptual con datos ficticios; el código y las reglas de negocio determinan las funciones y datos reales. No copiar sus inconsistencias: un borrador no lleva correlativo comercial, los conteos se calculan en base y los módulos pendientes no aparecen como enlaces operativos.

El listado real de `QuoteResource` y el detalle `filament/quotes/view.blade.php` son la referencia de implementación para nuevos módulos. El acceso inicial abre Presupuestos. Se conserva `/app/workspace` para marcadores anteriores, sin tarjetas promocionales ni módulos futuros.

Referencias de disciplina visual y trabajo: SAP Quartz (superficies reducidas y color funcional), listas/formularios de ERPNext y Odoo. No se copian marcas ni se agregan dependencias de esos productos.

- https://www.sap.com/design-system/fiori-design-web/v1-148/foundations/visual/colors/quartz-light-colors
- https://www.sap.com/design-system/fiori-design-web/v1-148/foundations/visual/iconography/iconography-horizon
- https://docs.frappe.io/framework/user/en/desk/navigation
- https://www.odoo.com/documentation/18.0/applications/studio/views.html

## Composición obligatoria

1. Cabecera grafito estable, marca transparente, nombre de producto, apariencia y menú de usuario. Solo controles con función implementada: no campana, ayuda, accesos o métricas simuladas.
2. Menú lateral por áreas. Comercial: Presupuestos y Clientes. Administración: Mi empresa, según permisos. Selección gris y marcador discreto; sin mosaicos ni recuadros de colores alrededor de iconos.
3. Encabezado de página con título y una acción principal. La información del negocio ocupa la mayor parte del espacio.
4. Listados con pestañas de estado, búsqueda, filtros, selector de columnas, tabla y paginación. Acciones de fila dentro del menú de tres puntos. Selección de un documento para abrir detalle, editar borrador, consultar PDF o historial.
5. Detalle con estado, fechas, partes, ítems, totales, condiciones e historial. Emitir es la acción principal del borrador, con confirmación específica.
6. Formularios agrupados, etiquetas visibles, resumen de totales calculado con el mismo servicio del guardado. No montar otra lógica monetaria en JavaScript.

Sin paneles de indicadores, héroes, gradientes, sombras decorativas, grandes iconos ni tarjetas de acceso repetidas en pantallas de operación. No agregar controles del render si no tienen un comportamiento completo.

## Tokens y temas

Fuente de verdad: `public/css/solvet.css`. Filament primary utiliza `#3B647F` en el proveedor; la hoja publicada se genera con `php artisan filament:assets`. No editar `public/css/app/solvet-theme.css` a mano. La URL incorpora hash del CSS para invalidar caché.

| Rol | Claro | Oscuro |
|---|---|---|
| Fondo | #F3F3F0 | #181C20 |
| Superficie | #FFFFFF | #22272B |
| Cabecera de tabla | #F0F1EF | #2B3136 |
| Texto | #292E32 | #E4E8EB |
| Título | #292E32 | #F2F4F5 |
| Secundario | #5D666B | #B5BFC5 |
| Selección | #E7E9E7 | #343E45 |
| Acción principal | #3B647F | #A0BFD2 |
| Texto de acción | #FFFFFF | #182A35 |
| Enlace / foco | #3B647F | #B0D0E4 |

Cabecera de aplicación: `#29333C`, texto `#F2F4F5`, secundario `#C1CBD2` en ambos modos. Es un contexto de contraste constante, no una inversión global. El resto usa tokens semánticos. Estados etiquetados, sin cápsulas saturadas: neutral para borrador/emitido, verde moderado para aceptación, rojo moderado para rechazo/anulación y advertencias de vencimiento. El color nunca sustituye texto.

Selector claro/oscuro/sistema nativo de Filament, evento `theme-changed`, clave `filament:theme:mode`. Primera visita clara, preferencia por navegador, sistema sigue cambios del SO. Historial independiente comparte esa preferencia. No usar filtros globales ni invertir imágenes.

## Marca e iconografía

Conservar el logo original `public/brand/solvet-it-logo.png`, sin redibujar ni deformar. Aplicación: componente `components.brand`, encuadre SVG del PNG con transparencia, sin placas. La tinta del subtítulo se adapta sobre fondos oscuros usando su máscara alfa original; no cambian las formas ni los colores principales.

Usar la familia Heroicons ya instalada: variante mini sólida de 20 px para navegación (caja visible 18–20 px), variante outline para acciones de 16–20 px. Mantener peso óptico, alineación y función por contexto. No mezclar emoji, iconos 3D, familias externas o dibujos decorativos. Etiquetas accesibles siempre; tooltip en controles que solo tienen icono. No usar un icono distinto para la misma acción en otra pantalla.

## Tipografía y dimensiones

Inter Variable local, nombre exacto instalado, fallback system-ui. Documento PDF: DejaVu Sans por compatibilidad con español y guaraní.

| Elemento | Regla |
|---|---|
| Título | 28 px escritorio / 24 px móvil, peso 650 |
| Texto | 13–14 px; interlineado mínimo 1.5 |
| Secundario | 12–13 px, sin opacidad para bajar contraste |
| Tabla | Cabecera 11–12 px; cuerpo 13 px; divisores finos |
| Importes | Alineación derecha y cifras tabulares |
| Espaciado | 4 / 8 / 12 / 16 / 20 / 24 / 32 px |
| Radios | 3 px detalles, 4 px controles, 5 px superficies |
| Controles | 40 px; objetivos táctiles mínimos 44 px |
| Cabecera / lateral | 68 px / 224 px en escritorio |
| Contenido | Máximo 1600 px; márgenes adaptables |

No duplicar el espaciado nativo: usar `.fi-page-header-main-ctn`, no sumar padding también en `.fi-page`. Pestañas compactas y subrayadas. Mantener validación, foco y estados deshabilitados de Filament.

## Documento comercial

A4 claro, logo completo, número, emisión, vigencia, emisor, destinatario, referencia, líneas, subtotal, descuentos, IVA incluido, total, condiciones y páginas. Paleta neutra independiente del navegador. Borrador inequívoco, sin número comercial. La emisión asigna el siguiente `PPTO-000001` de la secuencia global existente: no cambia prefijo ni reinicia por año para parecerse al render.

Los documentos emitidos conservan bytes, hash y snapshot originales. Los cambios de plantilla afectan únicamente borradores y próximas emisiones. La vista previa del emitido sirve el mismo archivo que la descarga.

## Accesibilidad y mantenimiento

Usar tokens para superficies/textos de nuevas vistas; preservar semántica y teclado nativos de Filament. Foco visible, etiquetas, errores, menús y ventanas legibles. Contraste WCAG AA para texto normal; pruebas de combinaciones no equivalen a auditoría integral.

Validar claro/oscuro, escritorio, 390 px, datos reales de prueba/vacíos/extensos, filtros y acciones. Listados nativos apilados en móvil; tablas de detalle e historial con desplazamiento interno. Sin desbordamiento global. Respetar movimiento reducido.

Reutilizar componentes nativos y `x-ui.panel`, `x-ui.status`, `x-ui.empty-state`. No copiar vistas internas ni modificar `vendor`, no agregar SPA ni biblioteca visual. Ante un patrón nuevo, documentarlo y agregarlo compartido antes de duplicarlo. Ver [componentes](componentes-ui.md) y [módulo](modulos/presupuestos.md).
