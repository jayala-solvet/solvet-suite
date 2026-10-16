# Instrucciones para agentes de desarrollo · Solvet Gestión

## Contexto y lectura

Este es el producto modular de Solvet-IT; primero presupuestos y después módulos operativos. Antes de trabajar, leer `README.md`, `docs/README.md`, `docs/contexto-proyecto.md`, `docs/arquitectura.md`, `docs/diseno.md` y la especificación del módulo afectado. Usar el índice para cargar solo documentos pertinentes. Para cambios de acceso, datos, archivos o despliegue, leer también `docs/seguridad.md`, `docs/entornos-publicacion.md` y `docs/operacion.md`.

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

Mantener **Inter Variable** local (nombre exacto de la fuente instalada), contraste, foco visible, etiquetas y comportamiento móvil. Leer `docs/componentes-ui.md`: reutilizar `x-ui.panel`, `x-ui.status` y `x-ui.empty-state` en vistas propias; usar componentes nativos de Filament en recursos, tablas, formularios y ventanas. No duplicar sus mecanismos de validación, permisos, tema o navegación.

El listado de presupuestos y su detalle son la referencia operativa aprobada; ver `docs/referencias/presupuestos-base.png`. La referencia secundaria Supabase/Refero se interpreta en `docs/referencias/supabase-adaptacion.md`; no importar su prompt/CSS ni sus restricciones de marketing. No agregar héroes, mosaicos, tarjetas de métricas ni controles sin función. Usar Heroicons mini sólidos en navegación y outline compactos en acciones, sin nuevas familias.

Aplicar la escala de espaciado/radios de `public/css/solvet.css`. Una acción principal por encabezado; las acciones de cada fila son secundarias. Formularios agrupados en secciones con título, descripción útil y etiquetas visibles. Los listados nativos se apilan en móvil. Comprobar que los cambios visuales no suman dos veces el espaciado nativo de Filament y no ocultan anillos de error, estados deshabilitados ni menús.

Toda nueva pantalla debe funcionar en claro y oscuro usando los tokens semánticos existentes. No fijar fondos blancos ni texto oscuro en las vistas de aplicación. El selector comparte la preferencia nativa de Filament y ofrece claro, oscuro y sistema. Verificar texto principal/secundario, placeholders, estados, errores, menús y ventanas en ambos modos. Los PDF mantienen su paleta de documento y no heredan el tema del navegador. El logo entregado se conserva sin redibujarlo y se muestra sin placas ni fondos propios en ambos temas mediante el componente `components.brand`. No sustituirlo por el logo distinto que aparece en la referencia cromática.

## Especificación, maquetas y compatibilidad

- Usar `docs/maquetacion.md` para diseño previo. Los futuros frames Figma se vinculan con ID/versión desde la especificación; no hay integración automática configurada. Mantener los tokens y componentes actuales, no importar un frontend paralelo.
- Para nuevas funciones, usar `docs/plantillas/funcionalidad.md`: declarar permisos, entradas, validaciones, qué se calcula/guarda, transiciones, efectos, errores, auditoría y aceptación. Distinguir implementado de propuesto.
- Revisar consumidores y pruebas de un contrato antes de cambiarlo. Conservar compatibilidad con snapshots/datos anteriores y migraciones aplicadas. No mezclar refactors ajenos o actualizaciones globales de dependencias.
- El laboratorio informado es `http://192.168.112.22:8088/`. Producción con dominio/Nginx/Cloudflare es prevista; no asumir que proxies confiables, TLS, host/caché o controles pendientes ya están activos. Cloudflare no sustituye permisos internos.
- No modificar configuración del servidor ni implementar funciones pendientes como efecto incidental de documentarlas. Una edición solo documental se valida por coherencia, referencias y diff, sin inventar pruebas de ejecución.

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

## Regla común de costos y rentabilidad

Para nuevos módulos leer [costos y precios](docs/modulos/costos-precios.md). Un único catálogo; separar costo, precio, IVA recuperable y utilidad. No existe un porcentaje obligatorio de ganancia de Odoo. Utilidad = venta sin IVA menos costos efectivos; distinguir margen sobre venta de recargo sobre costo. Un costo faltante es null, nunca cero implícito. Informar cobertura, moneda, fecha, procedencia y carácter estimado/real de todo indicador. No presentar aprobación como cobro o utilidad realizada.

Reutilizar CostCalculator/QuoteProfitability y los Gates de costos; costo interno y proveedor no aparecen en PDF comercial, serialización pública o vistas de reader. Conservar versiones y snapshots: no recalcular históricos desde maestros actuales. Todo cambio futuro a compras, ejecución o facturación debe declarar cómo registra costos reales, diferencias con estimados y tratamiento fiscal. La configuración de Solvet (IVA 10%, compras informadas con IVA) no es una regla universal para otras instalaciones.

## Operaciones e inventario

Leer [OP-01…OP-08](docs/modulos/operaciones.md) y [ADR 0003](docs/decisiones/0003-operaciones-inventario.md) al tocar productos, compras o ejecución. Bien/servicio es tipo; propio/tercero/compra es origen del costo. No inventar saldos al migrar ni mover stock desde presupuestos. Recepción, reserva, entrega y corrección deben conservar documento/actor/fecha, transacción, orden de bloqueos y claves de reintento. Cotización/publicación de precio no revaloriza stock.

No duplicar ingreso o costo en conjuntos: la venta vive una sola vez en la línea comercial y los componentes en copia privada. Precio de catálogo se publica únicamente con acción explícita. Resultado de ejecución solo al completar cantidades; costos parciales no son margen final. Mantener A/B en OperationsTest, permisos de reader, promedio/salidas conservadas y snapshots/PDF anteriores.

## Entornos y promoción

Leer [entornos separados](docs/entornos-desarrollo-pruebas-produccion.md). No promover bases de ensayo sobre movimientos reales. Usar comandos con perfil explícito y SHA probado; no renombrar proyectos/volúmenes existentes ni compartir APP_KEY. deploy/environments es privado y no entra en Git/contexto Docker. Test automático aislado no acredita construcción o concurrencia PostgreSQL.
