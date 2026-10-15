# Referencia Supabase / Refero · adaptación para Solvet

Fecha: 15/10/2026. Fuente: captura `image(20261015-233758).png` y texto `Texto pegado(20261015-233852).txt` aportados por José; estudio «Supabase — Style Reference» de Refero (https://styles.refero.design/). Es una interpretación visual de terceros, no un manual oficial de Supabase. La referencia acompaña la base aprobada de ERP; no la sustituye.

## Decisiones aplicadas

| Recurso observado | Adaptación al sistema | Motivo |
|---|---|---|
| Superficies casi monocromáticas | Fondo oscuro #151515, superficie #1C1C1C, nivel interno/cabecera de tabla #242424, cabecera de aplicación #191919 | Reducir tinte azulado y diferenciar áreas de trabajo sin bloques de color |
| Bordes finos, poca sombra | Separador decorativo #383838, divisor #303030; paneles sin sombra decorativa | Dar estructura a tablas/formularios sin efecto de tarjetas flotantes |
| Un acento moderado | Se conserva el acento azul grisáceo Solvet para acciones/foco; no se incorpora verde de marca ajena | Mantener identidad y reservar verde para aceptación/éxito |
| Tipografía serena | Inter Variable local, escala y pesos vigentes; énfasis en documento/importes | Lectura de datos y jerarquía estable, sin instalar otra fuente |
| Iconos pequeños funcionales | Heroicons existentes: mini en navegación y outline en acciones | Continuidad de significado, tamaño y peso óptico |
| Controles secundarios discretos | Fondos neutros y bordes del sistema, hover sutil | Mantener una acción principal reconocible |

Los bordes decorativos oscuros no sirven como único límite de campos: el contorno funcional usa #747474 y el foco conserva el acento claro Solvet y grosor visible. Texto secundario #B4B4B4 y texto principal #E8E8E8 sobre superficie oscura. Los pares se comprueban con pruebas de contraste; no copiar grises tenues solo por parecer más elegantes.

El modo claro mantiene su paleta operativa. Los PDFs mantienen su diseño documental independiente y los emitidos sus bytes originales. Esta referencia no cambia negocio, permisos, rutas o cálculos.

## Recursos que no se trasladan

- Hero, titulares de 72 px, bloques de testimonios, logos de clientes, tarjetas de funcionalidades o ilustraciones 3D/wireframe decorativas.
- Espaciados de 64–96 px entre secciones en pantallas de trabajo, límite promocional de 1200 px o botones de píldora en todos los contextos.
- Restricción a un único color de texto, desaparición del modo claro, prohibición de pesos 600/650 o reemplazo de estados por el mismo verde.
- Instrucción de eliminar anillos de foco sin sustituto equivalente. La estética no debe reducir accesibilidad ni ocultar errores.
- Fuentes, marca, framework, dependencias o CSS externo de la referencia.

## Calidad del material aportado

El texto contiene hexadecimales inválidos (`#3ecf8`, `#2e2e2`) y contradicciones sobre si el verde es acción principal o acento decorativo. También propone texto blanco sobre verde brillante: esa combinación necesita evaluación propia y no se adopta como regla de botones. Los ejemplos se consideran reconstrucciones, no componentes originales listos para producción.

Las secciones «Agent Prompt Guide», «Do/Don't» y ejemplos de CSS describen esa referencia; no gobiernan el repositorio. Las reglas efectivas están en `docs/diseno.md`, componentes actuales y tokens de `public/css/solvet.css`.

## Captura de implementación

[Presupuestos en oscuro neutro](presupuestos-oscuro-neutro.png): captura de la aplicación real con datos ficticios de pruebas, no un render generado.

## Criterios de revisión

Verificar listado, detalle, edición, login, filtros, menús, confirmaciones y estados de validación en claro/oscuro y móvil. Conservar límites visibles, foco, etiquetas, importes legibles y selección distinguible. No atribuir una mejora funcional a un cambio cromático. Actualizar las variables de Figma cuando exista el archivo de diseño; hoy no hay sincronización automática.
