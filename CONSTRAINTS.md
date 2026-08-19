# CONSTRAINTS.md — ReservaPro

Reglas duras del proyecto. Toda decisión técnica debe caber dentro de estos límites.
Son obligatorias, no sugerencias.

## Entorno y Setup

- SO: Linux Mint XFCE (base Ubuntu 24.04).
- PHP 8.3.6 (CLI), Composer 2.7.1.
- Ejecutor sin display gráfico: verificar SIEMPRE con `curl`, `pest`, `artisan test`,
  HTTP o CLI. Prohibido abrir navegador.
- Directorio de trabajo: `reservapro/`.
- **Regla de Scaffold**: Al crear proyectos con Composer/NPM, los archivos deben vivir en la
  raíz del repositorio Git. Nunca permitir repositorios Git anidados (submodules accidentales).

## Stack exacto

- Laravel último estable (registrado en DECISIONS.md → D5): **v13.10.0** (framework `laravel/framework` v13.26.1).
- Eloquent ORM.
- SQLite en desarrollo (`database/database.sqlite`).
- Pest como framework de tests.
- Pint para estilo de código (PSR-12).
- Tailwind CSS + Vite (incluidos por defecto en el scaffold).

## APIs y patrones PROHIBIDOS

- `eval()`, `exec()`, `shell_exec()`, `system()`, `passthru()`, `proc_open()`.
- `DB::raw()` sin bindings.
- Mass-assignment sin `$fillable`.
- `dd()` / `dump()` en `app/` (solo permitido depurando en tests/tinker).
- **Recrear a mano la estructura de Laravel**: siempre `composer create-project` o `artisan make:*`.
- Dependencias de pago.
- APIs reales de email/SMS: simular con `log`.

## Seguridad

- Contraseñas con `Hash`/bcrypt.
- CSRF activo en todos los formularios.
- Autorización con Policies (no lógica suelta en controllers).
- Sin secretos hardcodeados; `.env` nunca versionado.
- Validación de entrada en Form Requests.
- Escape de salida en vistas.

## Convenciones senior

- PSR-12 vía Pint (`./vendor/bin/pint --test` debe pasar).
- Validación SIEMPRE en Form Requests.
- Lógica de negocio en Actions/Services; prohibido controllers gordos.
- Salida con API Resources.
- Estados como Enums PHP (p.ej. `EstadoReserva`).
- Código en inglés, UI en español.
- Fechas ISO 8601, timezone `America/Lima`.
- Type hints (y `declare(strict_types=1)`) en todo el código `app/`.
- Nombres de clases PascalCase, métodos camelCase, constantes de enum UPPER_SNAKE.