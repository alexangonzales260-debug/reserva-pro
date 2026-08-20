# PLAN.md — ReservaPro

> **Avance:** F0 ✅ · F1 ✅ · F2 ✅ · F3 ✅ · F4 ✅ · F5 ✅ · **F6 (NegocioScope + aislamiento real de tenants) ✅** · pendientes: panel dueño (multi-tenant end-to-end), flujo público de reserva, notificaciones, reportes, hardening.

Fases atómicas. Cada fase termina con: archivos creados/modificados, criterio de cierre
y comandos de verificación. Nunca se salta una fase sin aprobación del Arquitecto.

## F0 — Bootstrap de la fábrica (ESTA FASE)

- **Archivos**: scaffold oficial (composer create-project), `.env` configurado, Pest instalado,
  tests de ejemplo en sintaxis Pest, `SPEC.md`, `CONSTRAINTS.md`, `ARCHITECTURE.md`, `PLAN.md`,
  `validate.sh`, `DECISIONS.md`, `METRICAS.md`.
- **Criterio de cierre**: `./validate.sh` código 0, `php artisan test` verde, commit `F0: bootstrap fábrica`.
- **Verificación**: `./validate.sh && php artisan test && php artisan about`.

## F1 — Modelos + migraciones + factories + seeders

- **Archivos**: `Negocio`, `User`, `Servicio`, `Empleado`, `Reserva`; tablas pivote
  `empleado_servicio`; factories y seeders con datos de ejemplo.
- **Criterio de cierre**: migraciones limpias (`migrate:fresh --seed`), factories producen datos válidos.
- **Verificación**: `php artisan migrate:fresh --seed` + Pest unit de modelos/factories.

## F2 — Multi-tenancy base

- **Archivos**: `app/Scopes/NegocioScope.php`, trait `BelongsToNegocio`, helper de negocio actual,
  aplicado en cada modelo tenant-scoped.
- **Criterio de cierre**: queries del tenant A no ven datos del tenant B (tests Pest).
- **Verificación**: `php artisan test` con tests de aislamiento.
- **Nota**: implementado como **Fase 6** (`CurrentNegocio`, `NegocioScope`, trait `BelongsToNegocio`,
  `TenantIsolationTest` con 6 casos). La fase concreta queda registrada en DECISIONS.md (D11).

## F3 — Autenticación (dueño + cliente)

- **Archivos**: Login/Register (Breeze o manual), middleware, roles, registro de tenant (Action `RegisterTenant`).
- **Criterio de cierre**: dueño registra negocio; cliente registra cuenta; sesiones y rutas protegidas.
- **Verificación**: Pest feature de auth + rutas protegidas redirigen.

## F4 — Lógica de reservas (conflictos)

- **Archivos**: `EstadoReserva` enum, `CreateReserva` Action, validador de solapamiento,
  generador de código único.
- **Criterio de cierre**: no-overbooking garantizado (tests de conflicto).
- **Verificación**: Pest unit del validador + feature de creación.

## F5 — Panel dueño

- **Archivos**: CRUD servicios/empleados/horarios, listado y gestión de reservas, dashboard con métricas.
- **Criterio de cierre**: dueño gestiona TODO de su negocio; UI en español.
- **Verificación**: Pest feature + `curl` de rutas (sin navegador).
- **Nota**: esta será la siguiente fase concreta (F7): atar el negocio del dueño autenticado vía
  `CurrentNegocio::set()` para que el panel opere aislado por tenant.

## F6 — Flujo público de reserva

- **Archivos**: página pública del negocio, selector de disponibilidad, formulario de reserva,
  confirmación con código.
- **Criterio de cierre**: cliente (invitado o registrado) reserva de punta a punta.
- **Verificación**: Pest feature del flujo completo + `curl` HTTP.

## F7 — Notificaciones simuladas + pulido

- **Archivos**: Notifications que escriben en `log`, eventos de cambio de estado, pulido UI.
- **Criterio de cierre**: cada transición de estado emite notificación simulada verificable en log.
- **Verificación**: Pest que captura log (Laravel `Log::fake` / `Mail::fake`).

## F8 — Reportes

- **Archivos**: reportes por rango de fechas, ingresos por servicio/empleado, exportación CSV.
- **Criterio de cierre**: dashboard con reportes agregados por negocio.
- **Verificación**: Pest de agregados + inspección de salida.

## F9 — Hardening + cierre

- **Archivos**: revisión de políticas, seguridad, `validate.sh` reforzado, documentación final, tags.
- **Criterio de cierre**: `./validate.sh` verde, tests completos, README de despliegue.
- **Verificación**: `./validate.sh && php artisan test` y revisión final del Arquitecto.

---

## Cierre por fase (recordatorio)

1. Correr `./vendor/bin/pint` antes de commitear.
2. Correr `./validate.sh`.
3. Commit atómico con mensaje `FX: descripción`.
4. Anotar métricas en `METRICAS.md`.
5. Reportar y esperar aprobación.