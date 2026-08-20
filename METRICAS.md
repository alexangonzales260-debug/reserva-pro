# METRICAS.md — Registro de métricas por fase

Tabla: `fase | tests | warnings | incidentes | validate | hash commit`

| Fase | Tests | Warnings | Incidentes | validate | Hash commit |
|------|-------|----------|------------|----------|-------------|
| F0 Bootstrap | 2 (Pest) | 0 | 0 | OK (código 0) | `f8fcfde` |
| F1 Modelos/Migraciones/Seeders | 7 (Pest) | 0 | 0 (APP_KEY testing resuelto) | OK (código 0) | `2d2c512` |
| F2 API REST + Controllers + Validación | 21 (Pest, 93 assertions) | 0 | 1 (Xdebug + `after:work_start` en PHP 8.3 → regla closure) | OK (código 0) | `47f8481` |
| F3 Lógica de conflictos + Disponibilidad real | 26 (Pest, 112 assertions) | 0 | 0 | OK (código 0) | `43d79e4` |
| F4 Autenticación del dueño + protección de rutas | 34 (Pest, 129 assertions) | 0 | 0 (sesión nativa sobre grupo api; `role` de F1 usado para admin, sin `is_admin`) | OK (código 0) | `5fc141e` |
| F5 Base multi-tenant: Negocio + negocio_id | 34 (Pest, 129 assertions) | 0 | 0 (`negocio_id` nullable en BD por SQLite, D10) | OK (código 0) | `480ecdb` |
| F6 NegocioScope + aislamiento real de tenants | 40 (Pest, 141 assertions) | 0 | 0 (trait `BelongsToNegocio`; gate 7 adaptado: `BelongsToNegocio\|NegocioScope`, D11) | OK (código 0, 12 comprobaciones) | `b4b99ee` |