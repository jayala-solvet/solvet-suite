# Comprobaciones de apariencia

Fecha: 2026-10-15. Cambio: selector de claro, oscuro y sistema.

## Comportamiento esperado

- La primera visita mantiene el diseño claro aprobado, aunque el sistema operativo use oscuro.
- Sol/luna/monitor están disponibles en el acceso y la barra superior, con nombres accesibles y estado pulsado.
- Una elección explícita se conserva al recargar y al navegar por las pantallas.
- Sistema sigue cambios del sistema operativo sin recargar.
- Cambiar el modo no envía el formulario ni borra su contenido.
- El historial usa la misma preferencia y los PDF siguen siendo documentos claros independientes.
- Nuevos colores del CSS cambian automáticamente su versión de URL para evitar que el navegador reutilice una hoja anterior.

## Ejecutado

- Suite PHP completa y pruebas nuevas de apariencia y contraste: ver resultado en el Pull Request.
- Chromium real con escritorio de 1512 × 1000 y móvil de 390 × 844.
- 24 estados de pantalla comprobados automáticamente y una selección inspeccionada visualmente: acceso, vista general, presupuestos, formulario, clientes, empresa, historial, validación, acciones, confirmación y versiones móviles.
- 1070 comprobaciones automáticas de colores computados de texto, fondo y placeholders en esas vistas: sin pares por debajo del umbral correspondiente al tamaño del texto en la última ejecución.
- Selector, persistencia al recargar, seguimiento del sistema y conservación de contenido del formulario comprobados.
- Sin errores JavaScript ni imágenes rotas; sin desbordamiento horizontal global en móvil. Las tablas conservan su desplazamiento interno.
- Hoja fuente y copia publicada idénticas; diferencias de Git sin errores de espacio.

Las capturas utilizan datos ficticios y son materiales internos de comprobación. No se publican ni se incluyen secretos o archivos de entorno.

## Pruebas repetibles

```bash
bash scripts/test.sh
```

`ThemeContrastTest` calcula la luminancia sRGB de los pares semánticos en claro y oscuro. Exige al menos 4.5:1 para texto normal y 3:1 para el foco. `AppearanceTest` verifica controles antes/después del acceso, tema inicial, versión de hoja de estilos e inicialización compartida del historial. Las pruebas comerciales existentes siguen cubriendo cálculos, permisos y conservación de documentos.

La comprobación de colores y la revisión visual no sustituyen una auditoría integral de accesibilidad.

## Revisión manual para próximas pantallas

1. Abrir el acceso sin preferencia guardada y confirmar claro.
2. Elegir oscuro, acceder y navegar por listas, formularios y configuración.
3. Revisar texto secundario, placeholders, estados, errores y foco de teclado.
4. Abrir menús y confirmaciones sin confirmar operaciones comerciales de prueba sobre datos reales.
5. Cambiar de modo con un formulario incompleto y comprobar que conserva sus datos.
6. Recargar y verificar la preferencia; elegir sistema y cambiar el tema del dispositivo.
7. Abrir el historial y comprobar su apariencia.
8. Repetir en 390 px, incluido el selector y el formulario de presupuesto.

## Publicación

El código y los recursos publicados cambian juntos. Después de incorporar el Pull Request a `main`, actualizar el servidor con el procedimiento existente:

```bash
cd /opt/solvet-it/solvet-suite
git pull --ff-only
bash scripts/update.sh
```

El script construye ambas imágenes, respalda, ejecuta las migraciones pendientes y recrea los servicios. Este cambio no agrega una migración ni una dependencia. La construcción/ejecución en Docker debe comprobarse en el entorno de destino; esta validación local usó PHP 8.5.11 y SQLite, sin Docker Engine.
