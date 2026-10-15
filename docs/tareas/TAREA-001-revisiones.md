# TAREA-001 · Revisiones de presupuestos

Estado: pendiente. Responsable por asignar. Base: v0.1.0.

## Objetivo

Permitir corregir una propuesta emitida sin modificar el documento que recibió el cliente.

## Resultado esperado

Desde un emitido, acción «Crear revisión» genera un borrador vinculado al original, con líneas y condiciones copiadas, sin sobrescribir PDF/snapshot ni decisión histórica. Antes de implementar, acordar si el identificador será `PPTO-000001 / Rev. 02` y cuándo una revisión sustituye comercialmente a la anterior.

## Criterios

- Usuario de consulta no puede crear revisiones.
- Versiones históricas siguen descargables.
- Dos solicitudes simultáneas no crean el mismo número de revisión.
- La aceptación identifica la revisión exacta.
- La interfaz muestra origen, revisión actual y documentos anteriores.
- Regla y migración documentadas; pruebas de concurrencia en PostgreSQL.

## Prompt para trabajar con IA

Leé AGENTS.md y los documentos del módulo. Revisá el código real de v0.1.0. Implementá únicamente la tarea de revisiones con la política de referencia acordada, conservando diseño, cálculos, permisos y PDFs históricos. Incluí las migraciones necesarias, verificá el flujo y actualizá documentación y CHANGELOG. Informá cualquier prueba que no puedas ejecutar.
