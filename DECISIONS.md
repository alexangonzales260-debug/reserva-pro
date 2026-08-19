# DECISIONS.md — Registro de Decisiones de Arquitectura (ADR)

Formato: **Contexto / Decisión / Consecuencia** (3 líneas por ADR).

## D1 — Scaffolding oficial, nunca estructura a mano

**Contexto**: La tentación de "montar" Laravel a mano genera esqueletos incompletos e inconsistentes.
**Decisión**: Siempre partir de `composer create-project laravel/laravel` y generar piezas con `artisan make:*`.
**Consecuencia**: Estructura canónica, actualizaciones simples y cero desvíos del layout oficial.

## D2 — SQLite en dev, camino a PostgreSQL en producción

**Contexto**: El proyecto debe arrancar con cero fricción local; Postgres añade setup innecesario en F0.
**Decisión**: SQLite en desarrollo (default de Laravel 13) con `database/database.sqlite`; la conexión quedará parametrizada por `.env` para Postgres en producción.
**Consecuencia**: Migraciones idénticas en ambos motores (SQL sin dialecto exótico) y swappeo vía `.env`.

## D3 — Pest como framework de tests

**Contexto**: PHPUnit clásico es verbose y menos expresivo para suites centradas en comportamiento.
**Decisión**: Pest v4 (instalado sobre el scaffold) como suite oficial de tests del proyecto.
**Consecuencia**: Tests legibles en estilo `it()/test()`, salida clara, y compatibilidad total con PHPUnit bajo el capó.

## D4 — Multi-tenancy single-DB con global scopes vs DB-por-tenant

**Contexto**: Multi-tenant requiere aislamiento; DB-por-tenant complica migraciones, backups y escalado temprano.
**Decisión**: Single-DB con `negocio_id` en cada tabla tenant y `app/Scopes/NegocioScope.php` aplicado globalmente.
**Consecuencia**: Un solo esquema mantenible; el aislamiento se garantiza por código (scope + policies), no por infraestructura.

## D5 — Último Laravel estable compatible con PHP 8.3

**Contexto**: La máquina tiene PHP 8.3.6; hay que fijar la versión exacta instalada para reproducibilidad.
**Decisión**: Instalar la última versión estable de Laravel compatible con PHP 8.3 — **v13.10.0** (framework `laravel/framework` v13.26.1), instalada en F0.
**Consecuencia**: Features modernos (default SQLite, estructura mínima) con requisito PHP 8.3; la versión queda registrada y congelada.

## D6 — Arquitectura por capas (FormRequest/Action/Resource/Policy/Enum)

**Contexto**: Sin estructura, los controllers se convierten en "gordos" e inmantenibles.
**Decisión**: Controller delgado → FormRequest (validación) → Action/Service (lógica) → Model; salida JSON vía Resource; autorización vía Policy; estados vía Enums.
**Consecuencia**: Responsabilidades únicas, código testeable y consistente entre features.