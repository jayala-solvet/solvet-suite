# Documentación de Solvet Gestión

Este índice es la entrada al contexto del proyecto. Los documentos están versionados junto con el código. Última revisión de esta base: 15/10/2026, sobre la implementación de presupuestos integrada en PR #4.

## Leer según la tarea

| Necesidad | Documento de referencia |
|---|---|
| Objetivo, alcance y entorno de José | [Contexto del proyecto](contexto-proyecto.md) |
| Capas, dependencias y escalado | [Arquitectura](arquitectura.md), [ADR 0001](decisiones/0001-monolito-modular.md) |
| Apariencia aprobada y componentes reales | [Diseño](diseno.md), [componentes](componentes-ui.md) |
| Crear maquetas y pasar a desarrollo | [Maquetación y especificación](maquetacion.md) |
| Qué hace, valida, calcula y guarda cada operación | [Presupuestos](modulos/presupuestos.md), [clientes y empresa](modulos/clientes-empresa.md), [catálogo](modulos/catalogo.md), [dashboard](modulos/dashboard.md), [costos y precios](modulos/costos-precios.md) |
| Definir una función nueva | [Plantilla de funcionalidad](plantillas/funcionalidad.md) |
| Trabajar con IA sin perder compatibilidad | [Desarrollo](desarrollo.md), [AGENTS](../AGENTS.md) |
| Accesos y protección de datos | [Seguridad](seguridad.md) |
| Pasar del laboratorio a Nginx y Cloudflare | [Entornos y publicación](entornos-publicacion.md) |
| Instalar, respaldar, actualizar o restaurar | [Operación](operacion.md) |
| Usar el módulo | [Emitir presupuesto](tareas/emitir-presupuesto.md) |
| Comprobaciones realizadas y sus límites | [Validación](validacion.md) |
| Qué falta y en qué orden | [Roadmap](roadmap.md) |

Las reglas implementadas se contrastan con el código y las pruebas. Las propuestas se identifican como pendientes; escribirlas aquí no las instala. Una maqueta define presentación e interacción, no habilita permisos ni demuestra que una regla funciona.

Si cambia una regla, actualizar su especificación en el mismo PR. Si cambia una decisión estructural, registrar un ADR. No duplicar contratos en varios documentos: enlazar el documento responsable.
