# SPEC.md — ReservaPro

SaaS multi-tenant de reservas para peluquerías y negocios de servicios. Cada negocio
(tenant) gestiona sus servicios, empleados y reservas; los clientes reservan online.

Los requisitos usan el formato EARS: **Cuando `<disparador>`, el sistema deberá `<respuesta>`**.
IDs trazables: prefijo por actor (`DUE`, `CLI`, `SIS`) + número.

---

## Actor: Dueño (tenant)

| ID | Requisito |
|----|-----------|
| DUE-01 | Cuando un usuario se registra como dueño, el sistema deberá crear su cuenta y su `Negocio` asociado (nombre, slug único, dirección, teléfono, horario de atención). |
| DUE-02 | Cuando un dueño crea un servicio, el sistema deberá guardarlo bajo SU `negocio_id` con nombre, duración en minutos, precio y estado activo. |
| DUE-03 | Cuando un dueño edita o desactiva un servicio, el sistema deberá persistir el cambio solo dentro de SU negocio. |
| DUE-04 | Cuando un dueño registra un empleado, el sistema deberá asociarlo a SU negocio y permitir asignarle uno o más servicios. |
| DUE-05 | Cuando un dueño define los horarios de atención, el sistema deberá almacenarlos por negocio y por día de semana. |
| DUE-06 | Cuando un dueño consulta sus reservas, el sistema deberá mostrar SOLO las reservas de SU negocio con estado, servicio, empleado y cliente. |
| DUE-07 | Cuando un dueño confirma, completa o cancela una reserva, el sistema deberá permitir el cambio de estado y persistirlo. |
| DUE-08 | Cuando un dueño ve su dashboard, el sistema deberá mostrar métricas de SU negocio: reservas por estado, ingresos estimados, ocupación por empleado y próximas reservas. |
| DUE-09 | Cuando un dueño intenta acceder a datos de otro negocio, el sistema deberá denegar el acceso (scope global + políticas). |

## Actor: Cliente

| ID | Requisito |
|----|-----------|
| CLI-01 | Cuando un cliente accede al perfil público de un negocio, el sistema deberá mostrar sus servicios, empleados y horarios disponibles. |
| CLI-02 | Cuando un cliente pide disponibilidad (fecha, servicio, empleado), el sistema deberá devolver los slots libres calculados contra los horarios del negocio y las reservas existentes. |
| CLI-03 | Cuando un cliente crea una reserva, el sistema deberá validar que el slot no se solape y guardarla con estado `pendiente`. |
| CLI-04 | Cuando un cliente crea una reserva, el sistema deberá generar un código único de reserva válido dentro del negocio. |
| CLI-05 | Cuando un cliente cancela su reserva, el sistema deberá marcarla como `cancelada` y liberar el slot. |
| CLI-06 | Cuando un cliente consulta su historial, el sistema deberá mostrar sus reservas pasadas y futuras con su estado (invitado mediante código o registrado). |

## Actor: Sistema

| ID | Requisito |
|----|-----------|
| SIS-01 | Cuando se ejecute cualquier query sobre modelos tenant-scoped, el sistema deberá aplicar el `NegocioScope` y aislar los datos por `negocio_id`. |
| SIS-02 | Cuando se intente crear/confirmar una reserva en un slot ocupado, el sistema deberá rechazarla para evitar no-overbooking (sin dependencia de orden de llegada). |
| SIS-03 | Cuando una reserva cambie de estado, el sistema deberá emitir una notificación simulada (log) al cliente y/o al negocio. |
| SIS-04 | Cuando se persista una fecha/hora, el sistema deberá usar ISO 8601 con timezone `America/Lima`. |
| SIS-05 | Cuando un modelo se cree/modifique, el sistema deberá aplicar las reglas de validación definidas en Form Requests antes de tocar la base de datos. |

---

## Fuera de alcance v1

- Pagos reales (sin pasarela, sin cobro).
- SMS/WhatsApp/email reales (solo notificaciones simuladas por `log`).
- Aplicaciones nativas (solo web responsive).
- Multi-idioma.
- Backups automáticos ni facturación.
- API pública para terceros.
