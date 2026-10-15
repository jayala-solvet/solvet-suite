# Instrucciones para agentes de desarrollo · Solvet Gestión

## Contexto y lectura

Este es el producto modular de Solvet-IT; primero presupuestos y después módulos operativos. Antes de trabajar, leer `README.md`, `docs/arquitectura.md`, `docs/diseno.md` y la especificación del módulo afectado. Para cambios de acceso, datos, archivos o despliegue, leer también `docs/seguridad.md` y `docs/operacion.md`.

La documentación expresa decisiones y alcance; verificar siempre el código y las versiones reales. Si hay discrepancia, resolverla explícitamente y actualizar la documentación correspondiente. No inventar funcionalidad existente ni informar una comprobación que no se ejecutó.

## Reglas de implementación

- Mantener PHP/Laravel + Filament/Livewire y PostgreSQL salvo decisión técnica registrada.
- Monolito modular. No agregar microservicios, Kubernetes, un SPA separado ni motores de plugins por anticipación.
- Mantener reglas de negocio en `app/Modules/<Modulo>/Services`; la interfaz Filament delega en ellas.
- Una misma base de código por producto. Personalización de empresa por configuración; nunca una rama permanente por cliente.
- Una empresa emisora por instalación en esta versión. No presentarla como SaaS multiempresa ni como autorización por cliente.
- Nunca usar `float` para cálculos monetarios. Usar BCMath y unidades menores enteras; presentación separada.
- No aceptar total, número, estado, actor o snapshot desde datos de formulario sin validación del servicio.
- Los emitidos no son editables. Conservar PDF y datos originales. Una nueva revisión será una operación explícita, no sobrescritura.
- Numeración dentro de transacción con bloqueo de secuencia y unicidad en base.
- Autorización en servidor. Ocultar un botón no reemplaza verificar permisos.
- No guardar secretos en Git. No registrar contraseñas, tokens ni contenidos de `.env`.
- Usar migraciones aditivas cuando sea posible; no modificar migraciones ya aplicadas a instalaciones reales.
- Mantener `composer.lock`. Instalar con `composer install`; no ejecutar actualizaciones globales de dependencias para resolver tareas aisladas.
- Antes de agregar una dependencia, justificar necesidad, licencia, mantenimiento y alternativa; registrar cambios arquitectónicos en un ADR.

## Diseño obligatorio

Leer `docs/diseno.md`. Reutilizar recursos de Filament y tokens de `public/css/solvet.css`. Pantallas de gestión con navegación por módulos, encabezado, acción principal, filtros y tabla. Evitar estilos aislados, gradientes decorativos y paneles de métricas ficticias. Verde/naranja/rojo representan estados; el color siempre lleva una etiqueta.

Mantener Inter local, contraste, foco visible, etiquetas y comportamiento móvil.

Toda nueva pantalla debe funcionar en claro y oscuro usando los tokens semánticos existentes. No fijar fondos blancos ni texto oscuro en las vistas de aplicación. El selector comparte la preferencia nativa de Filament y ofrece claro, oscuro y sistema. Verificar texto principal/secundario, placeholders, estados, errores, menús y ventanas en ambos modos. Los PDF mantienen su paleta de documento y no heredan el tema del navegador. El logo entregado se conserva sin redibujarlo. No sustituirlo por el logo distinto que aparece en la referencia cromática.

## Flujo por tarea

1. Leer especificación y cambios existentes; acotar el objetivo y criterios de aceptación.
2. Elegir la solución más simple coherente con la arquitectura.
3. Implementar una porción completa sin cambios ajenos a la tarea.
4. Ejecutar pruebas pertinentes de reglas de negocio, permisos y regresión; no crear pruebas triviales que dupliquen cada línea.
5. Si cambia la interfaz o PDF, inspeccionar el resultado real en escritorio/móvil o páginas renderizadas.
6. Actualizar especificación, CHANGELOG y ADR cuando corresponda.
7. Informar comportamiento final, comprobaciones ejecutadas y limitaciones reales.

## Comandos

- Instalación: `bash scripts/setup.sh`.
- Pruebas en Docker: `bash scripts/test.sh`.
- Pruebas con dependencias de desarrollo ya instaladas: `php vendor/bin/phpunit`.
- Formato: `vendor/bin/pint`.
- Lista de rutas: `php artisan route:list`.
- Desarrollo sin reconstruir PHP: usar `compose.dev.yml`; recompilar recursos Filament cuando cambie el CSS publicado (`php artisan filament:assets`).

No desplegar cambios a instalaciones de clientes como efecto incidental de una tarea de código. Trabajar en local/pruebas y entregar una versión revisable; aplicar despliegues solo cuando formen parte del alcance autorizado.
