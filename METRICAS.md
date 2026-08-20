# METRICAS.md — Registro de métricas por fase

Tabla: `fase | tests | warnings | incidentes | validate | hash commit`

| Fase | Tests | Warnings | Incidentes | validate | Hash commit |
|------|-------|----------|------------|----------|-------------|
| F0 Bootstrap | 2 (Pest) | 0 | 0 | OK (código 0) | `f8fcfde` |
| F1 Modelos/Migraciones/Seeders | 7 (Pest) | 0 | 0 (APP_KEY testing resuelto) | OK (código 0) | `2d2c512` |
| F2 API REST + Controllers + Validación | 21 (Pest, 93 assertions) | 0 | 1 (Xdebug + `after:work_start` en PHP 8.3 → regla closure) | OK (código 0) | `PENDIENTE` |