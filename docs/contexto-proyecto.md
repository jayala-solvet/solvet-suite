# Contexto del proyecto · Solvet Gestión

Fecha de referencia: 15/10/2026. Responsable de producto: José / Solvet-IT.

## Propósito y límites

Producto web propio para gestionar la actividad comercial y, gradualmente, operativa de Solvet-IT. Primer módulo: presupuestos trazables y con un documento comercial cuidado. Próximos módulos se incorporan sobre el núcleo común, sin reescribir lo que funciona. Se prevé ofrecer el producto a otras empresas.

La base adoptada es un monolito modular: PHP 8.5, Laravel 13, Filament 5/Livewire 4, PostgreSQL 17 y Docker Compose. Versiones concretas en `composer.lock` y `Dockerfile`. No cambiar stack, agregar SPA, microservicios ni SaaS compartido como efecto incidental de otra función.

Una instalación corresponde a una empresa emisora. Sus clientes son destinatarios comerciales; no son tenants. Todos los usuarios activos de la instalación pueden consultar el conjunto de clientes/presupuestos según su rol. Para otra empresa, usar despliegue, base, secretos y volúmenes independientes. Una futura plataforma multiempresa requiere una decisión y un proyecto de aislamiento específicos.

## Entornos conocidos

| Entorno | Dirección / estado | Uso |
|---|---|---|
| Laboratorio de José | http://192.168.112.22:8088/ — informado por José | Prueba funcional y visual; no equivale a producción |
| Repositorio | `jayala-solvet/solvet-suite` | Código, documentación y cambios revisados |
| Carpeta en el servidor | `/opt/solvet-it/solvet-suite` | Instalación Docker existente |
| Producción prevista | Dominio por definir, detrás de Nginx y Cloudflare | Pendiente de configuración y validación |

No se verificó desde esta documentación el estado de red del laboratorio ni la topología del futuro proxy. No inferir dominio público, direcciones del proxy, NAT, certificado, restauración operativa o acceso SSH. Ver [entornos y publicación](entornos-publicacion.md).

## Estado funcional actual

Clientes; datos de emisor; acceso por roles; presupuestos PYG/USD con borrador, totales durante edición, correlativo al emitir, PDF privado conservado, decisiones con evidencia e historial. Listado con pestañas, filtros, búsqueda, columnas y selección contextual. Modo claro/oscuro/sistema.

No hay envío de correo, portal de aceptación, órdenes de trabajo, integración GLPI, catálogo, revisiones, contabilidad ni facturación fiscal. No mostrar estas funciones como disponibles. Tampoco hay administración web de usuarios, recuperación de contraseña ni MFA implementados; ver las prioridades en [seguridad](seguridad.md).

## Identidad y experiencia

Base operativa sobria aprobada: [diseño](diseno.md) y [render](referencias/presupuestos-base.png). Presupuestos es la pantalla inicial y la referencia real para futuros listados. Conservar marca original transparente, Inter Variable local, superficies neutras, cabecera grafito e iconos compactos de una familia coherente.

La maqueta se propone en Figma y se vincula desde la especificación. Todavía no hay un archivo Figma creado ni sincronización automática. Los tokens efectivos viven en `public/css/solvet.css`; ver [maquetación](maquetacion.md).

## Invariantes que deben sobrevivir a nuevos módulos

- Permisos en servidor; no confiar en campos ocultos, botones ni IDs enviados por el navegador.
- Cálculo monetario decimal en servidor, sin `float`; totales de pantalla y persistencia usan el mismo calculador.
- No editar emitidos, regenerar sus PDFs, renumerar, reiniciar secuencias ni perder snapshots como parte de un rediseño.
- Conservar APP_KEY y persistencia de base/archivos. No cargar datos demo sobre la instalación ni restaurar sobre ella como prueba incidental.
- Sin secretos, presupuestos reales, bases de clientes ni respaldos en Git, Figma o prompts compartidos.
- Migraciones compatibles y aditivas; no modificar migraciones aplicadas ni mezclar una actualización general de dependencias con una función.
- Fuentes de verdad claras: reglas en especificaciones y servicios; diseño en componentes/tokens; versiones en lock/Git; configuración privada en el entorno.

## Cómo continuar una sesión de desarrollo

Leer `AGENTS.md`, este contexto, arquitectura y especificación afectada. Revisar el estado real del repositorio antes de editar. Informar brevemente el alcance, implementar una porción completa y comprobarla. Actualizar documentos y evidencia de pruebas en el mismo PR.

Los documentos orientan al desarrollador y a la IA. La protección frente a regresiones la aportan además los permisos, servicios, restricciones de base, pruebas, revisión del diff y despliegues con respaldo. No considerar suficiente «la IA leyó el contexto».
