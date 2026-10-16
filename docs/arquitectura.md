# Arquitectura

## Decisión inicial

Monolito modular sobre Laravel 13, PHP 8.5 y PostgreSQL 17. Interfaz Filament 5/Livewire 4. Un despliegue lógico por empresa; base PostgreSQL y almacenamiento privado propios. Recursos frontend publicados localmente desde las dependencias bloqueadas; no hay compilador Node necesario en v0.1.

## Capas

| Capa | Ubicación | Responsabilidad |
|---|---|---|
| Núcleo | app/Modules/Core | Empresa y auditoría |
| Clientes | app/Modules/Customers | Información comercial del destinatario |
| Catálogo | app/Modules/Catalog | Maestro opcional de productos/servicios; copia de datos a propuestas |
| Presupuestos | app/Modules/Quotes | Cálculo, persistencia, emisión y decisión |
| Interfaz | app/Filament | Formularios, tablas y navegación |
| Documentos | resources/views/pdf | Representación del contenido emitido |
| Infraestructura | Dockerfile, compose.yml, docker | Ejecución y servicios |

Los módulos usan Eloquent y servicios de aplicación. En la escala actual no se agrega una abstracción de repositorios ni un framework de módulos. El módulo Quotes referencia Customer. No se crean dependencias circulares. Integraciones futuras tendrán adaptadores separados y operaciones idempotentes.

## Flujo de emisión

`QuoteService::issue` autoriza al actor, bloquea el presupuesto, comprueba borrador/vigencia, bloquea la secuencia, fija el siguiente número, captura emisor/destinatario, genera y guarda el PDF, persiste ruta/hash, incrementa secuencia y registra evento.

La base garantiza unicidad del número. PostgreSQL aplica bloqueos de fila en producción. SQLite se usa en pruebas rápidas, pero no prueba esos bloqueos concurrentes. La escritura en disco no participa en la transacción SQL: una falla posterior puede dejar un archivo huérfano sin referencia; no un documento emitido parcialmente visible. Una tarea posterior puede detectar huérfanos. El almacenamiento configurado lanza error si no puede guardar.

Los documentos emitidos no se vuelven a generar al descargarlos. El hash permite detectar diferencias con la copia de base; no es una firma electrónica.

## Despliegue

- `web`: Nginx, archivos públicos copiados de la misma construcción y proxy a PHP-FPM.
- `app`: PHP-FPM sin privilegios, código y dependencias dentro de la imagen.
- `db`: PostgreSQL sin puerto publicado al host.
- Volúmenes: datos PostgreSQL y `storage` de Laravel. Respaldar ambos y `.env`.

Las imágenes de app/web se construyen juntas desde el mismo árbol. No ejecutar Composer en cada petición ni resolver nuevas dependencias en cada arranque. Evitar etiquetas `latest`; revisar parches de las imágenes base como mantenimiento planificado. Para reproducibilidad binaria exacta, fijar sus digests después de validar la construcción en el entorno de despliegue.

## Escalar con evidencia

| Señal observada | Paso razonable |
|---|---|
| Correo/PDF o integraciones bloquean peticiones | Colas y worker, operaciones reintentables e idempotentes |
| Consultas lentas con datos reales | Medir, revisar N+1, índices y paginación |
| Más instancias de aplicación | Almacenamiento compartido y estrategia de sesiones/cola |
| Muchas instalaciones por cliente | Automatizar inventario de versiones y despliegue por lotes |
| Necesidad comercial de SaaS compartido | ADR específico de aislamiento, migración y pruebas entre empresas |

Separar un servicio requiere una razón operativa concreta; la existencia de otro módulo no es motivo suficiente.

## Contratos, diseño y crecimiento

Cada módulo declara sus operaciones, persistencia, cálculo y permisos en `docs/modulos`; los consumidores no esquivan sus servicios para escribir directamente sus tablas. El contrato de presupuestos identifica operaciones PPT-01…PPT-06 y reglas de conservación. La especificación de nuevas funciones usa [plantilla](plantillas/funcionalidad.md).

Figma es una herramienta propuesta de diseño y revisión, según [maquetación](maquetacion.md). Los tokens y componentes de la aplicación siguen siendo la implementación efectiva; no hay generación/sincronización automática adoptada. Cambios de reglas requieren actualizar especificación y pruebas; cambios de estructura requieren ADR.

Los límites de confianza y requisitos para laboratorio/publicación están en [entornos](entornos-publicacion.md). El Nginx interno de Compose y el proxy de publicación cumplen roles distintos. La publicación por Cloudflare no convierte el monolito en multiempresa ni resuelve autorización a nivel de registro.
