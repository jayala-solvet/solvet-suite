# Roadmap

| Etapa | Contenido | Estado |
|---|---|---|
| 0 | Arquitectura, identidad, AGENTS y operación Docker | Incluido en v0.1 |
| 1 | Clientes, borradores, cálculo, emisión PDF e historial | Incluido en v0.1 |
| 1.1 | Validar instalación PostgreSQL/Docker en infraestructura Solvet | Antes de operar con datos reales |
| 1.2 | Totales durante edición y detalle completo | Implementado en PR #4 |
| 1.3 | Catálogo opcional, pago, carga y baja auditada de borradores | Implementado en ampliación de carga |
| 1.3.1 | Revisiones vinculadas | Próxima entrega comercial recomendada |
| 1.4 | Gestión de usuarios, recuperación de acceso y MFA | Implementado para prueba; operación/SMTP/publicación pendientes de verificar |
| 2 | Correo con cola, portal de aceptación y vencimientos | Planificado |
| 3 | Órdenes de trabajo y vínculo GLPI | Planificado |
| 4 | Contratos y servicios recurrentes | Propuesto |
| 5 | Compras/inventario/pedidos y ejecución mínima | Implementado; ampliaciones por uso real |
| 6 | Facturación fiscal e integración contable | Propuesto |

Cada etapa debe tener especificación acotada y criterios de aceptación antes de implementarse. No vender como funciones disponibles las etapas planificadas.

## Preparación de publicación y estabilidad

- Método de maquetación/especificación y contratos: documentado; archivo Figma pendiente de creación.
- Proxy confiable, hosts, TLS y caché privada/origen restringido: pendientes de implementar/validar antes de publicar; tareas P01/P02 en `entornos-publicacion.md`.
- Concurrencia PostgreSQL, restauración externa y controles operativos: pendientes de evidencia del entorno; P03.
- Protección de main/CI y dependencias: definir/verificar su aplicación en GitHub; el flujo documentado no instala reglas del repositorio.
- Auditoría de maestros, control de edición concurrente de borradores e idempotencia de creación: mejoras identificadas, todavía sin implementar.

No mezclar estos pendientes con el hecho de que presupuestos ya funciona en laboratorio. Cada entrega tiene criterios propios y cambios acotados.
