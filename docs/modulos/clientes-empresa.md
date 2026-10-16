# Clientes y empresa emisora · contrato actual

Estado: implementado; revisado 15/10/2026. Código de referencia: `CustomerResource`, `CompanyResource` y sus páginas Filament. Los recursos mantienen persistencia Eloquent nativa. El alta inline desde Presupuestos reutiliza su esquema de campos y usa `CustomerService::create`, con Gate y validación en servidor.

## Datos y permisos

| Entidad | Datos de entrada | Validación de pantalla | Persistencia / permisos |
|---|---|---|---|
| Cliente | name, tax_id, contact, email, phone, address | Nombre obligatorio hasta 200; RUC 50; contacto/correo 200; correo válido; teléfono 50; dirección 1000 | `customers`, timestamps; admin/commercial crean/editan; reader consulta |
| Emisor único | name, tax_id, email, phone, address, quote_terms | Términos generales hasta 5000; Nombre obligatorio hasta 200; RUC/teléfono 50; correo válido hasta 200; dirección 1000 | `companies`, timestamps; solo admin consulta recurso y edita; no alta/baja desde pantalla |

El emisor se inicializa mediante el comando de instalación. La unicidad de una fila emisora es una convención del producto, no una restricción de singleton en la base; no insertar varias empresas para simular multiempresa.

Nombre/RUC de clientes no son únicos en esta versión. No hay detección automática de duplicados ni validación fiscal del RUC. No hay eliminación desde los recursos. Los permisos se apoyan en autorización de los recursos Filament; cualquier nueva API, importador o servicio debe volver a validar datos y permisos en su propio punto de entrada.

## Efectos y trazabilidad

- Guardar actualiza el maestro. No genera correlativos, PDFs, órdenes, correos ni decisiones.
- Los borradores usan el cliente/emisor actual para la vista previa. Al emitir, Quotes copia datos comerciales a snapshot y conserva el PDF.
- Cambiar un maestro no altera snapshots ni bytes de emitidos existentes.
- No existe un historial comercial de modificaciones de clientes/emisor en `audit_events`. `created_at`/`updated_at` no registran qué cambió ni quién lo cambió. Agregar auditoría de maestros es una mejora pendiente con especificación y pruebas propias.

## Criterios para extenderlos sin regresión

1. Cliente nuevo/edición válido permanece disponible al crear presupuesto.
2. Un lector no crea ni edita; comercial no edita el emisor por acceso directo.
3. Campos adicionales deben definir validación, sensibilidad, migración, búsqueda y si pasan al snapshot. Snapshots antiguos sin ese campo siguen siendo legibles.
4. Un campo fiscal obligatorio nuevo requiere plan para datos existentes; no bloquear documentos conservados ni completarlos con datos inventados.
5. Evitar duplicar el maestro en cada módulo. Órdenes/contratos futuros referencian el cliente y capturan snapshot cuando su contrato lo requiera.

La cobertura de pruebas publicada se detalla en `docs/validacion.md`; esta lista define también condiciones para cambios futuros, no certifica que cada una tenga hoy una prueba automatizada específica.

`companies.quote_terms` se copia por defecto solo en propuestas nuevas; cambiarlo no sustituye los términos de borradores existentes ni emitidos. Los valores por propuesta permanecen editables en borrador.
