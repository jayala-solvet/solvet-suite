# Marco legal y publicación de documentos · Paraguay

Revisión técnica: 16/10/2026. Es preparación funcional y borradores, no dictamen jurídico ni certificación de cumplimiento. Debe revisarse la actividad real, contratos, relación laboral/comercial y proveedores antes de publicar.

## Vigencia y fuentes

La Ley 7593/2025 fija en su artículo 57 un plazo de veinticuatro meses desde la publicación oficial para su entrada en vigor; el artículo 60 prevé reglamentación dentro de ese plazo. La memoria oficial MITIC informa publicación el 27/11/2025. Al 16/10/2026 no ha transcurrido ese plazo; planificar adecuación para noviembre de 2027 y confirmar cómputo exacto, Gaceta y disposiciones reglamentarias con asesoría jurídica. Esta revisión no certifica una búsqueda exhaustiva de toda reglamentación ni la inaplicabilidad de otras obligaciones ya vigentes.

Revisar además Ley 6534/2020 si hay tratamiento crediticio; Ley 4868/2013 según actividad de comercio electrónico; Ley 1334/1998 y sus modificaciones si existe relación de consumo; y Ley 6822/2021 para documentos/servicios de confianza. Un clic interno, un hash o el PDF de Solvet no deben presentarse como firma cualificada o sello de tiempo certificado.

Fuentes oficiales consultadas:

- [BACN · Ley 7593/2025](https://www.bacn.gov.py/leyes-paraguayas/12924/ley-n-75932025-de-proteccion-de-datos-personales-en-la-republica-del-paraguay).
- [MITIC · Memoria 2025](https://mitic.gov.py/eoj0cad9uplo/2026/01/Memoria-2025-MITIC_WEB.pdf).
- [BACN · Ley 6534](https://www.bacn.gov.py/leyes-paraguayas/9417/ley-n-6534-de-proteccion-de-datos-).
- [BACN · Ley 4868](https://www.bacn.gov.py/leyes-paraguayas/961/comercio-electronico).
- [BACN · Ley 1334](https://www.bacn.gov.py/leyes-paraguayas/897/ley-n-1334-de-defensa-del-consumidor-y-del-usuario).
- [BACN · Ley 6822](https://www.bacn.gov.py/leyes-paraguayas/10318/ley-n-6822-de-los-servicios-de-).

## LEG-01 · Preparar y publicar

Administración → Versiones legales → Nueva versión. El texto inicial procede de resources/legal. Nombre/RUC/domicilio/correo se toman de Mi empresa al preparar y se conservan en copia. No se inventan datos faltantes. El usuario confirmó usar los datos de empresa; confirmarlos contra la identidad real antes de publicar.

Las plantillas incluyen condiciones de uso interno, aviso de privacidad, cookies y modelo de finalidad opcional. Privacidad/cookies contienen campos COMPLETAR sobre el inventario y operación final. El sistema impide publicar mientras falten datos de responsable, queden marcadores o no se confirme revisión jurídica con una referencia y contraseña del operador. La confirmación humana no demuestra por sí sola validez legal; registrar quién revisó y el documento de aprobación.

Cada versión guarda texto íntegro, huella SHA-256, efecto, clave de finalidad, versión, emisor copiado, actor y fecha. Publicadas son inmutables desde la aplicación; se crea una versión nueva para corregir. Las antiguas siguen accesibles. Solo versiones publicadas se ofrecen antes del login; borradores son privados. No se publican plantillas automáticamente con la migración.

## LEG-02 · Efectos distintos

| Documento | Evidencia | Efecto funcional |
|---|---|---|
| Términos | accepted | Revisión/aceptación de la versión contractual actual antes de entrar en módulos |
| Privacidad/cookies | acknowledged | Constancia voluntaria de información, no consentimiento global |
| Finalidad opcional | consented / withdrawn | Acción separada y retiro, sin casillas premarcadas |

Una finalidad opcional tiene su propia purpose_key y versiones. No agrupar marketing, transferencias o analítica en una autorización indistinta. La evidencia identifica usuario autenticado, documento, propósito, huella y fecha de servidor; se presenta America/Asuncion y se conserva el texto del documento. No se recoge IP/user-agent adicional sin finalidad justificada. No acredita identidad del cliente externo o firma cualificada.

Documentos y privacidad permite consultar versiones actuales y el historial propio paginado. La misma acción repetida no duplica el último evento; retirar preserva evidencia anterior. La aplicación no implementa marketing, analítica opcional o cookies publicitarias: antes de añadirlas, el consumidor debe comprobar autorización efectiva y probar que no se activan previamente. Un registro de consentimiento aislado no instala ese bloqueo.

## Inventario de cookies/datos pendiente de dominio final

Base actual: sesión Laravel con nombre por entorno; mecanismo CSRF nativo cuando se emite; preferencia visual localStorage filament:theme:mode y comportamiento nativo de Filament. No se añade analítica publicitaria. Configuración de Cloudflare/WAF puede introducir otras cookies: inventariar en el navegador sobre el dominio real, sus proveedores y duración. No inventar un banner de aceptación de cookies opcionales si no existen; tampoco afirmar que toda cookie necesita consentimiento.

Definir con el responsable fundamento por finalidad, encargados de hosting/correo/Cloudflare, países/transferencias, plazos de conservación, canal y tiempos de respuesta, supresión/bloqueo por obligación contable o defensa de derechos, y tratamiento de solicitudes. No hay eliminación automática de datos comerciales ni borrado de evidencia por retiro. Los términos de una propuesta al cliente siguen separados de las condiciones de uso del sistema.
