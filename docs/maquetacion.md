# Maquetación, diseño y entrega a desarrollo

Estado: método adoptado. Herramienta propuesta: Figma Design. No hay archivo Figma creado, conexión activa ni exportación automática configurada en esta entrega.

## Uso de Figma para este producto

Diseñar primero la porción que se va a desarrollar. Para Solvet, empezar reconstruyendo el listado, formulario y detalle actuales como componentes reutilizables. El render sirve para dirección visual; la aplicación validada resuelve proporciones, estados y comportamiento real.

Figma permite usar variables y modos para tokens de diseño, componentes con propiedades y prototipos para recorridos. Dev Mode ayuda a inspeccionar propiedades y entregar diseños; no convierte por sí mismo la maqueta en reglas de Laravel. Disponibilidad de funciones avanzadas depende del plan: confirmar el plan vigente antes de basar el proceso en una función de pago.

Mantener la misma especificación si más adelante se elige otra herramienta. La herramienta de diseño no es un requisito para ejecutar el sistema.

## Organización propuesta del archivo

Nombre: `Solvet Suite · Sistema de diseño y módulos`.

| Página | Contenido |
|---|---|
| 00 · Guía | Versión de referencia, responsable, enlace al repositorio y convenciones |
| 01 · Fundamentos | Logo original, tipografía, variables de color, espacios y radios |
| 02 · Componentes | Cabecera, navegación, botones, campos, pestañas, tabla, estados, ventanas y vacíos |
| 03 · Presupuestos | Listado, creación, detalle, confirmación, decisiones, historial y PDF |
| 04 · Flujos | Recorridos de creación/emisión/decisión y casos de error |
| 90 · Propuestas | Variantes pendientes, separadas de las aprobadas |
| 99 · Archivo | Versiones anteriores identificadas, sin borrarlas durante una revisión |

Nombre de frame: `PPT-03 · Emitir · Confirmación · Oscuro · Escritorio`. Usar el mismo ID de función en Figma, especificación, pruebas y descripción del PR. Referenciar un frame concreto y una versión/fecha, no solo la portada de un archivo que cambia.

## Componentes y tokens

Usar Auto Layout y componentes con variantes cuando corresponda. Replicar los componentes nativos de Filament a nivel visual, sin diseñar comportamientos que exijan sustituirlos sin una necesidad de producto concreta.

| Figma propuesto | Código / referencia |
|---|---|
| `color/bg`, `color/surface` | `--solvet-bg`, `--solvet-surface` |
| `color/text`, `color/muted` | `--solvet-text`, `--solvet-muted` |
| `color/action/bg`, `color/action/text` | `--solvet-primary-bg`, `--solvet-primary-text` |
| `color/border`, `color/focus` | `--solvet-border`, `--solvet-focus` |
| `space/1…8` | Escala de `docs/diseno.md` y CSS |
| `radius/control`, `radius/surface` | `--solvet-radius-control`, `--solvet-radius` |
| `Button`, `Field`, `Dialog` | Componentes nativos de Filament |
| `Panel`, `Status`, `EmptyState` | Componentes `x-ui.*` existentes |

Modos Claro/Oscuro sobre roles semánticos, no colores aislados por pantalla. El PDF usa una paleta clara independiente. Si cambia un token aprobado, actualizar Figma, CSS fuente y documentación en el mismo cambio; publicar assets y verificar contraste. No existe sincronización automática en este momento. No importar/exportar CSS generado sobrescribiendo la hoja o los componentes existentes.

## Cada pantalla debe mostrar

- Escritorio y móvil; referencia de revisión: 1512 px y 390 px, sin fijar el producto a esos dos tamaños.
- Claro y oscuro, foco, validación, carga, deshabilitado, vacío y sin resultados cuando apliquen.
- Datos largos, muchas filas, importes grandes, PYG/USD y salto de página en PDF.
- Acciones de cada rol y estados en los que se permiten. Un control oculto en la maqueta también requiere autorización en servidor.
- Destino real de cada acción, mensajes y resultado de guardar/cancelar/error. No dibujar controles decorativos que parezcan operativos.

Usar datos ficticios. No cargar clientes reales, tokens, contraseñas, respaldos ni capturas con información confidencial en un diseño compartido.

## De la idea al cambio estable

1. Definir la función usando la [plantilla](plantillas/funcionalidad.md): datos, permisos, cálculo, persistencia, estados, errores y aceptación.
2. Maquetar las pantallas y el recorrido de esa función, reutilizando la base aprobada. No hace falta maquetar todo el ERP para avanzar.
3. Registrar referencia/versión y decisiones en la especificación. José valida los cambios de experiencia o negocio que requieran su decisión. La autorización ya dada para arreglos o implementación no se convierte en una nueva aprobación por cada detalle rutinario.
4. Implementar en rama corta, conservando servicios, componentes y contratos. La IA inspecciona el código existente; no genera una aplicación paralela ni sustituye Filament por un frontend exportado.
5. Verificar casos positivos/negativos, permisos, regresión, escritorio/móvil y ambos temas. Comparar interfaz real con los frames, corrigiendo desviaciones relevantes.
6. Actualizar especificación/CHANGELOG, revisar PR y publicar una versión identificable. La maqueta se marca implementada solo tras comprobar la aplicación.

Si maqueta y regla aprobada se contradicen, registrar y resolver la discrepancia antes de cambiar el comportamiento comercial. Nunca asignar correlativo a un borrador solo porque aparece así en una imagen.

## Referencias oficiales consultadas

- [Variables y modos](https://help.figma.com/hc/en-us/articles/15339657135383-Guide-to-variables-in-Figma)
- [Propiedades de componentes](https://help.figma.com/hc/en-us/articles/5579474826519-Explore-component-properties)
- [Dev Mode](https://help.figma.com/hc/en-us/articles/15023124644247-Guide-to-Dev-Mode-in-Figma)

Consulta: 15/10/2026. Comprobar condiciones vigentes del plan al organizar el equipo.
