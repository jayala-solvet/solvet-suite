# ADR 0001 · Monolito modular e instalaciones independientes

Fecha: 2026-10-15. Estado: adoptado para la base inicial.

## Contexto

Solvet-IT necesita empezar por presupuestos, mantener el sistema con un equipo pequeño, ampliar por módulos y ofrecerlo más adelante a otras empresas.

## Decisión

Laravel/PHP, interfaz Filament/Livewire, PostgreSQL y Docker Compose. Un solo producto/código, módulos por carpetas y servicios. Una empresa por despliegue y almacenamiento separado. Sin frontend SPA ni compilación Node en esta primera entrega. Identidad sobre componentes y CSS local.

## Consecuencias

Instalación y actualización comprensibles, coherencia visual y reutilización de núcleo. Más instalaciones implicarán inventario de versiones y automatización de despliegue. No se obtiene SaaS compartido simplemente agregando un company_id: requiere decisión nueva y aislamiento completo.

## Alternativas

Adaptar GLPI limita el modelo comercial; usar un ERP existente reduce desarrollo pero no cumple el objetivo de producto propio; separar microservicios aumenta operación antes de haber volumen que lo justifique. Se revisará cuando cambien necesidades reales, no por previsión abstracta.
