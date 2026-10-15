# Solvet Gestión · base inicial 0.1.0

Aplicación web modular para Solvet-IT. Primera entrega ejecutable: usuarios, clientes, presupuestos, PDF conservado e historial de decisiones. Incluye una identidad visual propia basada en el logo entregado y la referencia azul de Solvet.

**Empezar aquí:** [INICIO_RAPIDO.md](INICIO_RAPIDO.md). Desarrollo asistido por IA: [AGENTS.md](AGENTS.md).

## Qué funciona

- Acceso mediante usuario y contraseña; roles administrador, comercial y consulta.
- Alta y edición de clientes; edición de datos de la empresa por administrador.
- Presupuestos en PYG y USD con líneas, cantidades, descuentos e IVA incluido.
- Cálculo en servidor, correlativo al emitir, PDF descargable y copia comercial de cliente/emisor.
- Decisiones manuales de aceptación, rechazo o anulación con evidencia textual obligatoria.
- Historial por presupuesto, actor, fecha y hash SHA-256 del PDF emitido.
- Listado con pestañas, filtros, selección contextual, detalle completo e historial; PDF A4 con identidad Solvet.
- Apariencia clara, oscura o según el sistema, seleccionable antes y después de iniciar sesión.
- Docker Compose, volúmenes persistentes, respaldo, actualización y pruebas automatizadas.

## Apariencia

Los botones de sol, luna y monitor permiten elegir **claro**, **oscuro** o **sistema** en el acceso y en la barra superior. La primera visita usa claro para conservar el diseño inicial. La elección se recuerda en ese navegador y dirección del sistema; no se sincroniza entre dispositivos. El historial sigue esa preferencia y los PDF conservan el diseño para compartir e imprimir.

La base visual aprobada usa superficies neutras, cabecera grafito, Inter Variable local e iconografía compacta. El inicio abre Presupuestos, con filtros, pestañas y detalle completo; los formularios muestran totales durante la edición. Los componentes compartidos están en `resources/views/components/ui`.

La paleta usa tokens semánticos y pruebas de contraste. Ver [reglas de diseño](docs/diseno.md), [catálogo de componentes](docs/componentes-ui.md) y [comprobaciones de apariencia](docs/pruebas-apariencia.md).

## Alcance de esta versión

Esta base está destinada a instalación de prueba y desarrollo incremental. No es todavía un ERP terminado ni una edición comercial lista para clientes externos. Cada instalación representa una sola empresa; todos sus usuarios autorizados ven los mismos clientes y presupuestos. La base y los archivos son independientes por instalación.

No incluye todavía: revisiones vinculadas de presupuestos emitidos, envío por correo, firma o portal del cliente, recordatorios de vencimiento, importación de planillas, catálogo reutilizable, conversión a orden, integración GLPI, facturación fiscal, licenciamiento comercial, MFA, recuperación de contraseña y administración de usuarios desde pantalla. Las cuentas se crean por CLI y no hay contraseña predeterminada.

`Emitido` significa documento cerrado y numerado, **no** correo enviado. La aceptación es registrada por un usuario interno y no equivale a una firma digital. La auditoría se guarda en la base de datos de la aplicación; no es un registro externo inalterable.

## Tecnología efectiva

PHP 8.5 · Laravel 13 · Filament 5 · Livewire 4 · PostgreSQL 17 · Nginx · Dompdf. Las versiones concretas de bibliotecas están en `composer.lock`. No se necesita Node para ejecutar esta entrega: los recursos de Filament se publican al construir la imagen; la identidad visual usa CSS propio. Docker debe descargar imágenes y paquetes en la primera construcción.

## Instalación

Requisitos: Docker con Compose v2, Bash y OpenSSL. En Windows se recomienda Docker Desktop con WSL2 y ejecutar desde Ubuntu/WSL. En Linux, Docker Engine y Compose v2.

```bash
bash scripts/setup.sh
docker compose exec app php artisan solvet:user
```

Abrir http://localhost:8088/app. La primera compilación puede tardar varios minutos. Elegí una contraseña de 12 o más caracteres; el instalador genera las claves técnicas en `.env` y no las muestra.

Demostración opcional, en una instalación local con clientes y presupuestos vacíos:

```bash
docker compose exec app php artisan solvet:demo
```

Detener conservando datos:

```bash
docker compose down
```

No usar `down -v` si querés conservar la base y los documentos.

## Repositorio

- `app/Modules/Core`: empresa y eventos de auditoría.
- `app/Modules/Customers`: clientes.
- `app/Modules/Quotes`: documentos, cálculos y operaciones comerciales.
- `app/Filament`: adaptadores de interfaz y pantallas.
- `resources/views/pdf`: documentos PDF.
- `public/css/solvet.css`: tokens y estilos de interfaz.
- `docs`: contexto del producto, decisiones y reglas de mantenimiento.
- `tests`: verificaciones funcionales y de autorización.

## Próximo desarrollo recomendado

1. Revisiones de presupuestos conservando el vínculo y el histórico.
2. Catálogo reutilizable.
3. Envío por correo con cola y registro de entregas, separado de emitir.
4. Portal de aprobación y órdenes de trabajo.
5. Integración GLPI.

Ver [roadmap](docs/roadmap.md) y [validación](docs/validacion.md). No hay integración activa con GLPI ni se enviaron mensajes o correos durante la creación de esta base.
