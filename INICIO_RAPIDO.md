# Ejecutar la muestra inicial

## 1. Preparar

Extraé el ZIP y abrí una terminal en la carpeta `solvet-suite`. Requisitos: Docker con Compose v2, Bash y OpenSSL. Windows: usá Ubuntu/WSL2 con integración de Docker Desktop activa. No necesitás instalar PHP, Composer, PostgreSQL ni Node en tu equipo.

## 2. Instalar

```bash
bash scripts/setup.sh
```

El script genera `.env` con claves aleatorias cuando no existe, construye las imágenes, levanta PostgreSQL, ejecuta migraciones y crea la configuración de la empresa. Es repetible: no reinicia datos ni cambia claves existentes.

## 3. Crear tu acceso

```bash
docker compose exec app php artisan solvet:user
```

Ingresá nombre, correo y una contraseña de al menos 12 caracteres. El rol por defecto es administrador. La contraseña no se muestra.

Abrí http://localhost:8088/app e ingresá con ese usuario.

## 4. Ver una muestra con datos

Opcional, antes de cargar tus clientes:

```bash
docker compose exec app php artisan solvet:demo
```

Crea tres clientes ficticios, un borrador, un presupuesto emitido y uno aceptado. Consume números en esa base de demostración. Para una instalación real utilizá otro `COMPOSE_PROJECT_NAME`, claves y volúmenes nuevos; no reutilices sus documentos como ventas reales.

## 5. Probar el flujo

1. Completá `Configuración → Mi empresa`.
2. Entrá a `Clientes` y creá un cliente.
3. Entrá a `Presupuestos → Nuevo presupuesto`.
4. Agregá líneas con cantidad, precio con IVA incluido y descuento.
5. Guardá. Revisá el total calculado y descargá el PDF de borrador.
6. En la lista, elegí `Emitir` y confirmá. Se asigna el número definitivo.
7. Descargá el PDF. Para compartirlo, envialo manualmente por tu canal habitual.
8. Usá `Registrar decisión` para documentar la respuesta, indicando fecha/contacto/referencia.
9. Consultá `Historial`.

El correlativo comienza en `PPTO-000001`. La imagen histórica no confirma el último número real: no se continúa automáticamente el 1087. Antes de iniciar operación real, configurar el próximo correlativo sin sobrescribir números existentes; ver `docs/operacion.md`.

## Otros usuarios

```bash
docker compose exec app php artisan solvet:user --role=commercial
docker compose exec app php artisan solvet:user --role=reader
```

El administrador y el comercial pueden gestionar clientes y presupuestos. Solo el administrador cambia los datos del emisor. Consulta puede leer, descargar PDF e historial; no crear ni editar.

## Ejecutar en un servidor

La muestra escucha solo en `127.0.0.1`. Para verla desde tu PC contra un servidor remoto, podés usar un túnel:

```bash
ssh -L 8088:127.0.0.1:8088 usuario@servidor
```

Luego abrí http://localhost:8088/app. Para un dominio real configurá HTTPS, `APP_URL`, cookies seguras y proxy según `docs/operacion.md`.

## Comprobar y detener

```bash
docker compose ps
docker compose logs --tail=80 app web
docker compose down
```

Si el puerto está ocupado, cambiá `APP_PORT` y `APP_URL` en `.env` y ejecutá `docker compose up -d`. Para respaldar: `bash scripts/backup.sh`.
