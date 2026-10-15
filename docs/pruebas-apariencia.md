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

## Última verificación de la base visual · 2026-10-15

- PHP 8.5.11: suite completa aprobada, **14 pruebas / 136 aserciones**. Incluye reglas comerciales, permisos, conservación de PDF, apariencia y contraste.
- Chromium real: escritorio 1512 × 1000, comprobaciones adicionales en 1280 × 900, 1920 × 1080 y tablet 768 × 1024; móvil de 390 × 844.
- **37 estados de pantalla** comprobados y una selección inspeccionada visualmente: acceso, vista general, listas, formularios, historial, validación, estado sin resultados, acciones y confirmación en ambos temas; vistas adicionales por ancho.
- **2149 comprobaciones de colores computados** de texto, fondo y placeholders: sin pares por debajo del umbral correspondiente al tamaño en la ejecución final.
- Fuente Inter Variable local realmente cargada en las pantallas de aplicación e historial.
- Selector, persistencia, seguimiento del sistema y conservación del formulario al alternar tema comprobados.
- Guardado de un borrador mediante el formulario: 2 × ₲ 125.000 = ₲ 250.000 conservado; búsqueda por asunto, agregado de ítem, foco visible y colapso/apertura del menú lateral comprobados.
- Controles del selector de 44 × 44 px verificados con emulación táctil. Las capturas completas del navegador reinician temporalmente esa emulación; la medida se comprueba antes de capturar.
- Sin errores JavaScript ni imágenes rotas; sin desbordamiento horizontal global en los anchos verificados. Las tablas nativas se apilan en móvil y las de consulta mantienen desplazamiento interno.
- Hoja fuente y copia publicada idénticas; formato PHP y diferencias de Git comprobados.

Las capturas utilizan datos ficticios y son materiales internos de comprobación. No se publican ni se incluyen secretos o archivos de entorno.

## Corrección de integración del logo · 2026-10-15

- PNG original transparente conservado sin modificar; encuadre SVG del contenido visible y sin placa de fondo.
- Seis estados revisados en Chromium: acceso, cabecera de escritorio y menú móvil, cada uno en claro y oscuro. Contenedor transparente y proporción 102 × 62 px comprobados; máscaras únicas y sin errores JavaScript.
- Inspección visual de cabeceras, acceso y menú móvil. Pruebas existentes de apariencia aprobadas: 2 pruebas / 21 aserciones.

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
