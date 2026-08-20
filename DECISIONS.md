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

## D7 — Contrato API `work_start`/`work_end` vs. columnas `start_time`/`end_time`

**Contexto**: La API (Fase 2) expone horario de empleado como `work_start`/`work_end`, pero las columnas de BD se crearon en F1 como `start_time`/`end_time`.
**Decisión**: No renombrar columnas; alias virtuales con accessor+mutator en `Employee` que mapean `work_start`→`start_time` y `work_end`→`end_time`. El contrato REST queda estable y la BD intacta.
**Consecuencia**: `$fillable` incluye ambos nombres; el API y la UI hablan el mismo idioma mientras la persistencia no cambia.

## D8 — `reservations.user_id` nullable para reservas de invitados

**Contexto**: La Fase 2 expone la API sin autenticación y el SPEC permite clientes invitados que reservan solo con código; la columna `user_id` era NOT NULL.
**Decisión**: Migración que hace `user_id` nullable; `ReservationResource` devuelve `user: null` cuando no hay usuario vinculado.
**Consecuencia**: Reservas de invitados posibles desde la API pública; la relación queda disponible cuando el cliente se registre o inicie sesión.

## D9 — Autenticación del dueño con sesión nativa; multi-tenancy postergado

**Contexto**: En Fase 2 toda la API era pública; había que proteger la escritura administrativa (servicios/empleados) sin Breeze/Sanctum/Fortify y el multi-tenancy no existe aún.
**Decisión**: Sesión nativa de Laravel sobre el grupo `api` (EncryptCookies + AddQueuedCookiesToResponse + StartSession añadidos vía `Middleware::api(prepend:)`), `Auth::attempt` contra usuarios con `role=admin` (el campo `role` de F1 ya distinguía al dueño vía `isAdmin()`, no se añadió `is_admin` redundante), rutas de escritura protegidas con `middleware('auth')` y 401 JSON en español vía render de `AuthenticationException`. CSRF no se aplica a `api/*` (patrón JSON API con cookie SameSite=Lax, coherente con el flujo de tests/curl).
**Consecuencia**: Solo el dueño autenticado crea/edita/elimina servicios y empleados; lecturas y flujo de reservas del cliente siguen anónimos; el multi-tenancy se posterga hasta tener el dueño autenticado como base para el `NegocioScope`.

## D10 — `negocio_id` NULLABLE en BD; aislamiento a nivel de aplicación

**Contexto**: El multi-tenancy (SIS-01) exige `negocio_id` en services/employees/reservations, pero SQLite maneja mal los ALTER a NOT NULL sobre columnas existentes.
**Decisión**: `negocio_id` queda NULLABLE a nivel de BD (foreignId nullable constrained); el aislamiento real se hará con `NegocioScope` en la fase siguiente, nunca por constraint.
**Consecuencia**: Migraciones limpias y seguras en SQLite; los datos sin negocio conviven mientras no haya scope; la garantía de aislamiento pasa a la capa de aplicación (scope + policies).