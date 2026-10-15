# Instalación, respaldo y actualización

## Persistencia

`db_data` contiene PostgreSQL; `app_storage` contiene PDFs y estado de Laravel. El código se reconstruye desde el repositorio, sin instalar dependencias al arrancar. `.env` conserva APP_KEY, contraseña de base y configuración. `COMPOSE_PROJECT_NAME` define el grupo de volúmenes: cambiarlo inicia otra instalación lógica.

Para otra empresa: otra carpeta de despliegue, otro nombre de proyecto, puerto/dominio y claves propias. Nunca apuntar dos empresas distintas a los mismos volúmenes.

## Numeración inicial

El inicializador comienza en 1. Antes de emitir datos reales, definir el último correlativo verificado. Con sistema en mantenimiento, respaldo y sin usuarios operando:

```bash
docker compose exec app php artisan tinker
```

En la consola, inspeccionar primero:

```php
App\Modules\Quotes\Models\Quote::max('number');
DB::table('document_sequences')->where('key', 'quote')->first();
```

Si se ha verificado que el siguiente número es 1088 y no existe un número igual o mayor en esta instalación, actualizar esa secuencia a 1088. No asumir que la captura histórica confirma ese valor. Nunca reducirla sobre documentos emitidos ni reutilizar números anulados. Este ajuste administrativo no está expuesto en la interfaz de la muestra.

## Respaldo

```bash
bash scripts/backup.sh
```

Genera `database.dump`, `documents.tar.gz`, `environment.env` y `VERSION` dentro de `backups/<fecha UTC>`, con permisos restringidos. Incluye secretos. Copiarlo a un destino externo protegido y ensayar restauración. No se configura cron ni se transfieren respaldos automáticamente.

Para una copia consistente, detener escrituras durante el backup (`php artisan down`) y volver a habilitar al terminar (`php artisan up`). El script de actualización ya pone en mantenimiento antes de respaldar. Cerrar también workers externos si se agregan en el futuro.

## Restauración ensayada

Hacer primero en una instalación de prueba vacía, con la versión de código del respaldo y PostgreSQL compatible. No cargar un dump sobre operación activa.

1. Preparar `.env` desde `environment.env`, ajustar dominio/puerto/nombre de proyecto y conservar APP_KEY.
2. Construir imágenes y levantar base/aplicación aisladas.
3. Con aplicación en mantenimiento, restaurar dump mediante `pg_restore` dentro del contenedor de base.
4. Restaurar `documents.tar.gz` sobre `storage` usando un contenedor con permiso de escritura del volumen.
5. Limpiar cachés, habilitar y comprobar acceso, PDF e historial.

La restauración es una operación destructiva sobre su destino y debe ejecutarse contra el entorno confirmado. Esta base incluye el respaldo, no un script que restaure automáticamente sobre producción.

## Actualización

Traer el código de una versión revisada y probarlo en una instalación de pruebas. Luego:

```bash
bash scripts/update.sh
```

Construye primero, pone en mantenimiento, respalda, migra, recrea app/web y limpia cachés. No hace `git pull` ni modifica el repositorio. Si un paso falla, se detiene y conserva mantenimiento para que el operador revise. Verificar `docker compose ps`, `/up` y el flujo comercial antes de darla por terminada.

**Reversión:** no basta volver a una imagen vieja si cambió el esquema. Conservar código/imagen anterior, backup de base y archivos, y plan de migración. Preferir cambios compatibles; probar recuperación en una copia antes de usar con clientes.

## HTTPS y proxy

Puerto de muestra: `127.0.0.1:8088`. Para un dominio, colocar un proxy TLS delante y establecer APP_URL con HTTPS, cookies seguras y entorno production. Laravel debe confiar solo en el proxy utilizado para interpretar correctamente el protocolo. `bootstrap/app.php` permite configurar `trustProxies` con direcciones/red reales, sin abrir confianza global por comodidad.

No cambiar BIND_ADDRESS a `0.0.0.0` sin decidir qué red tendrá acceso. Los puertos de base y PHP-FPM permanecen internos.

## Diagnóstico

- `docker compose logs --tail=100 app web db`
- `docker compose exec app php artisan about`
- `docker compose exec app php artisan migrate:status`
- `docker compose exec app php artisan optimize:clear`

APP_KEY no debe cambiar entre reinicios. Si faltan assets después de actualizar, reconstruir app y web con el mismo código; no reutilizar una imagen web anterior.
