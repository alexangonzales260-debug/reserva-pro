# ARCHITECTURE.md — ReservaPro

## Modelos de dominio

- `Negocio` — tenant: nombre, slug único, dirección, teléfono, horarios.
- `User` — dueños y clientes; rol implícito por relación con `Negocio` (`owner_of` vs. cliente).
- `Servicio` — servicio ofrecido por un negocio: nombre, duración (minutos), precio, activo.
- `Empleado` — empleado de un negocio; pivote `empleado_servicio` para servicios que domina.
- `Reserva` — una cita: `negocio_id`, `servicio_id`, `empleado_id`, cliente (invitado o `user_id`),
  `inicio`/`fin` (ISO 8601, tz America/Lima), `estado`, `codigo`.

## Multi-tenancy: single-DB con global scope

- Un solo esquema; toda tabla tenant lleva `negocio_id`.
- `app/Scopes/NegocioScope.php` aplica automáticamente el filtro `where(negocio_id = $negocioActual)`.
- `Negocio` (el tenant) NO tiene scope (es la raíz).
- El negocio actual se resuelve desde el usuario autenticado (dueño) o desde la URL pública (cliente), nunca de input no validado.
- El scope se puede `withoutGlobalScope()` solo en contextos internos de tenant (nunca expuesto).

## Capas

```
Controller → FormRequest (validación) → Action/Service (lógica) → Model (Eloquent)
                │                                │                        │
                └────────────────────────────────┴────────────────────────┘
                          Resource (salida JSON) · Policy (autorización)
```

1. **Controller**: entrada HTTP mínima; delega en Action.
2. **FormRequest**: toda la validación de entrada.
3. **Action/Service**: lógica de negocio (crear reserva, chequear conflicto, cambiar estado).
4. **Model**: Eloquent, mutators/casts, relaciones; NO validación, NO lógica de negocio.
5. **Resource**: forma la respuesta JSON.
6. **Policy**: decide quién puede hacer qué sobre cada recurso.

## Enums

`app/Enums/EstadoReserva.php`:
- `PENDIENTE` (pendiente)
- `CONFIRMADA` (confirmada)
- `CANCELADA` (cancelada)
- `COMPLETADA` (completada)

## Flujos principales

1. **Registro de tenant (DUE-01)**: registrar usuario + crear `Negocio` en una transacción (Action `RegisterTenant`).
2. **Reserva de cliente (CLI-03/04)**: resolver disponibilidad → validar slot libre → transacción que persiste reserva con código único (Action `CreateReserva`).
3. **Validación de conflictos (SIS-02)**: chequear solapamiento sobre reservas activas (pendiente/confirmada) del mismo `negocio_id` + `empleado_id` en el rango `[inicio, fin)`.

## Estructura de carpetas (objetivo)

```
app/
  Actions/
  Enums/
  Http/
    Controllers/
    Requests/
    Resources/
  Models/
  Notifications/
  Policies/
  Scopes/
  Services/
bootstrap/
config/
database/
  factories/
  migrations/
  seeders/
public/
resources/ (vistas Blade + JS/CSS)
routes/
tests/ (Pest)
```

## Decisiones de arquitectura

- Single-DB multi-tenant con global scope (ver DECISIONS.md → D4).
- Capas con responsabilidad única (ver DECISIONS.md → D6).
- Invitados (clientes no registrados) identificados por `codigo` de reserva + email/nombre.