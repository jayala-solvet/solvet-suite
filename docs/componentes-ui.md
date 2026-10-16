# Componentes de interfaz · Solvet

Esta es la base para extender el sistema manteniendo el diseño aprobado. Las pantallas actuales son ejemplos reales; no hay una biblioteca paralela ni una pantalla de demostración con controles sin función. Leer primero [diseño](diseno.md) y las instrucciones de [agentes](../AGENTS.md).

## Fuentes de verdad

| Responsabilidad | Ubicación |
|---|---|
| Colores claro/oscuro, espaciado, radios, tipografía y adaptadores | `public/css/solvet.css` |
| Copia publicada del CSS | `public/css/app/solvet-theme.css`, generada por `php artisan filament:assets` |
| Navegación, ancho lateral, fuente y hooks | `app/Providers/Filament/OperationsPanelProvider.php` |
| Componentes propios | `resources/views/components/ui` |
| Apariencia y persistencia | `components/theme-switcher.blade.php` y mecanismo nativo de Filament |
| Vista de referencia | `QuoteResource` y `resources/views/filament/quotes/view.blade.php` |

La hoja está organizada en tokens, estructura, adaptadores de Filament, componentes e historial. Editar las reglas existentes antes de agregar sobrescrituras al final. No modificar la copia publicada a mano.

## Vistas propias

| Componente | Parámetros | Uso existente |
|---|---|---|
| `x-ui.panel` | `title`, `description` opcional; contenido y slot `actions` | Panel e historial |
| `x-ui.stat-card` | Componente heredado | No usar en nuevas pantallas operativas |
| `x-ui.status` | `state`, `label` | Estado con texto y punto de color |
| `x-ui.empty-state` | `title`, `description`, `icon` opcional; contenido opcional | Panel sin presupuestos |

Los atributos HTML se fusionan con la clase del componente. Los textos se escapan con Blade. Los tonos de indicadores disponibles son `info`, `success` y `warning`; los estados son `draft`, `issued`, `accepted`, `rejected` y `cancelled`. El color nunca reemplaza la etiqueta.

```blade
<x-ui.panel title="Documentos recientes" description="Actividad del módulo.">
    <x-slot:actions>
        <a href="{{ $indexUrl }}">Ver todos</a>
    </x-slot:actions>

    {{-- Contenido real de la pantalla --}}
</x-ui.panel>

<x-ui.status :state="$quote->status" :label="$quote->statusLabel()" />
```

No convertir un indicador en botón sin destino y propósito reales. Una tarjeta de métricas muestra un dato existente, nunca una cifra simulada en operación.

## Recursos Filament

Usar `Section`, `TextInput`, `Select`, `Repeater`, `TextColumn`, `Action` y `ActionGroup` nativos. La hoja compartida adapta su apariencia; el framework conserva validación, navegación por teclado, estados de carga, ventanas y menús.

Patrón de un listado: título y descripción breve en la página; `CreateAction` con icono `heroicon-o-plus`; columnas legibles; búsqueda con texto corto; filtros; acciones de fila secundarias; estado vacío que indique el siguiente paso. Aplicar `stackedOnMobile()` y `recordActionsColumnLabel('Opciones')` cuando corresponda.

```php
ActionGroup::make($actions)
    ->label('Acciones')
    ->icon('heroicon-o-ellipsis-horizontal')
    ->color('gray')
    ->iconButton()
    ->tooltip('Acciones del presupuesto');
```

No retirar comprobaciones de `visible()`, `canCreate()`, `canEdit()` ni las del servicio para simplificar una vista. El estilo nunca determina autorización.

Patrón de formulario: secciones con título, descripción que ayude a completar datos, etiquetas permanentes y orden de lectura. Usar `columnSpanFull()` para las secciones y columnas adaptables en sus campos. El detalle de presupuestos usa 1 columna en móvil, 2 desde `md` y 12 desde `xl`, con anchos por campo. No aplicar anchos fijos a cada campo.

## Tokens más utilizados

| Función | Token |
|---|---|
| Fondo y superficie | `--solvet-bg`, `--solvet-surface`, `--solvet-subtle` |
| Texto y jerarquía | `--solvet-text`, `--solvet-heading`, `--solvet-muted` |
| Acciones | `--solvet-link`, `--solvet-primary-bg`, `--solvet-primary-text` |
| Controles | `--solvet-field`, `--solvet-control-border`, `--solvet-focus` |
| Separación | `--solvet-space-1` a `--solvet-space-6`, `--solvet-space-8` |
| Radios | `--solvet-radius-sm`, `--solvet-radius-control`, `--solvet-radius` |
| Estados | Pares `--solvet-{info,success,warning,danger,neutral}-{bg,text}` |

Ejemplo: `padding: var(--solvet-space-5); background: var(--solvet-surface); color: var(--solvet-text);`. Elegir el rol del color, no un valor distinto para cada pantalla.

Los valores de marca originales se conservan como constantes. No todos sirven para texto pequeño. El logo se muestra como imagen original; los PDF tienen su propia paleta de documento.

## Criterios para aceptar una pantalla

1. Se entiende cuál es la acción principal y qué representa cada estado.
2. Se usa la fuente Inter Variable instalada, incluidos los caracteres españoles y el símbolo de moneda disponible.
3. La pantalla usa componentes/tokens compartidos y no modifica `vendor`.
4. Se revisan ambos temas: contenido principal/secundario, campos, placeholders, errores, menús y confirmaciones.
5. Se prueba 390 px y un escritorio de al menos 1280 px; los datos largos no rompen la página.
6. El foco es visible, los campos tienen etiqueta y el cambio de tema conserva el formulario.
7. Las acciones siguen guardando datos por los servicios existentes y respetan permisos.
8. Se publican los recursos y se ejecutan las comprobaciones pertinentes de [apariencia](pruebas-apariencia.md).

## Presupuestos como base

Pestañas nativas con conteos reales, selección máxima de un registro, acciones contextuales nativas y selector de columnas. La selección abre el detalle o sus acciones; en móvil se conserva el listado apilado. No copiar posiciones exactas del render por encima de accesibilidad o comportamiento del framework.

El resumen de edición (`filament/quotes/live-totals`) usa `QuoteCalculator`, sin persistir nada hasta guardar. El detalle consulta los snapshots de documentos emitidos y muestra actividad con actor precargado.

## Patrón de resumen comercial

`filament/pages/dashboard.blade.php` usa una franja `sv-dashboard-metrics` con tres cifras y separadores, paneles `x-ui.panel`, barras discretas con importes etiquetados y filas de seguimiento. Los estilos se limitan al prefijo `sv-dashboard` y usan tokens existentes. No reutilizar como mosaico de tarjetas ni llevar indicadores a los listados operativos. Contrato: [dashboard](modulos/dashboard.md).
