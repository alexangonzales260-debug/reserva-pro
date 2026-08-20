<?php

use App\Models\Employee;
use App\Models\Reservation;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeTestReservation(): Reservation
{
    $service = Service::create(['name' => 'Corte', 'duration_minutes' => 30, 'price' => 25.00]);
    $employee = Employee::create(['name' => 'María', 'work_start' => '09:00', 'work_end' => '18:00']);

    return Reservation::create([
        'code' => 'RF-TEST01',
        'service_id' => $service->id,
        'employee_id' => $employee->id,
        'start_at' => '2026-08-22 10:00:00',
        'end_at' => '2026-08-22 10:30:00',
        'status' => Reservation::STATUS_PENDING,
    ]);
}

it('stores a reservation with an RF code and computed end_at', function () {
    $service = Service::create(['name' => 'Corte', 'duration_minutes' => 30, 'price' => 25.00]);
    $employee = Employee::create(['name' => 'María', 'work_start' => '09:00', 'work_end' => '18:00']);

    $response = $this->postJson('/api/v1/reservations', [
        'service_id' => $service->id,
        'employee_id' => $employee->id,
        'start_at' => '2026-08-22T10:00:00',
        'notes' => 'Prueba',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.notes', 'Prueba')
        ->assertJsonPath('data.end_at', '2026-08-22T10:30:00-05:00');

    $code = $response->json('data.code');
    expect($code)->toMatch('/^RF-[A-Z0-9]{6}$/');

    $reservation = Reservation::query()->where('code', $code)->first();
    expect($reservation->end_at->format('Y-m-d H:i'))->toBe('2026-08-22 10:30');
});

it('shows a reservation by code', function () {
    $reservation = makeTestReservation();

    $response = $this->getJson("/api/v1/reservations/{$reservation->code}");

    $response->assertOk()
        ->assertJsonPath('data.code', $reservation->code)
        ->assertJsonPath('data.status', Reservation::STATUS_PENDING)
        ->assertJsonPath('data.service.name', 'Corte');
});

it('cancels a pending reservation', function () {
    $reservation = makeTestReservation();

    $response = $this->patchJson("/api/v1/reservations/{$reservation->code}/cancel");

    $response->assertOk()
        ->assertJsonPath('message', 'Reserva cancelada correctamente.');

    $this->assertDatabaseHas('reservations', [
        'id' => $reservation->id,
        'status' => Reservation::STATUS_CANCELLED,
    ]);
});

it('lists reservations with status, employee and date filters', function () {
    $service = Service::create(['name' => 'Corte', 'duration_minutes' => 30, 'price' => 25.00]);
    $employeeA = Employee::create(['name' => 'Ana', 'work_start' => '09:00', 'work_end' => '18:00']);
    $employeeB = Employee::create(['name' => 'Beto', 'work_start' => '09:00', 'work_end' => '18:00']);

    Reservation::create([
        'code' => 'RF-AAAAAA',
        'service_id' => $service->id,
        'employee_id' => $employeeA->id,
        'start_at' => '2026-08-22 10:00:00',
        'end_at' => '2026-08-22 10:30:00',
        'status' => Reservation::STATUS_PENDING,
    ]);
    Reservation::create([
        'code' => 'RF-BBBBBB',
        'service_id' => $service->id,
        'employee_id' => $employeeB->id,
        'start_at' => '2026-08-22 11:00:00',
        'end_at' => '2026-08-22 11:30:00',
        'status' => Reservation::STATUS_CONFIRMED,
    ]);
    Reservation::create([
        'code' => 'RF-CCCCCC',
        'service_id' => $service->id,
        'employee_id' => $employeeA->id,
        'start_at' => '2026-08-23 10:00:00',
        'end_at' => '2026-08-23 10:30:00',
        'status' => Reservation::STATUS_PENDING,
    ]);

    $response = $this->getJson(
        "/api/v1/reservations?status=pending&employee_id={$employeeA->id}&date=2026-08-22"
    );

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', 'RF-AAAAAA');
});

it('rejects a reservation that overlaps directly (10:00-10:30 vs 10:00-10:30)', function () {
    $service = Service::create(['name' => 'Corte', 'duration_minutes' => 30, 'price' => 25.00]);
    $employee = Employee::create(['name' => 'María', 'work_start' => '09:00', 'work_end' => '18:00']);

    $this->postJson('/api/v1/reservations', [
        'service_id' => $service->id,
        'employee_id' => $employee->id,
        'start_at' => '2026-08-22T10:00:00',
    ])->assertCreated();

    $this->postJson('/api/v1/reservations', [
        'service_id' => $service->id,
        'employee_id' => $employee->id,
        'start_at' => '2026-08-22T10:00:00',
    ])->assertStatus(409)
        ->assertJsonPath('message', 'El empleado ya tiene una reserva en ese horario.');
});

it('rejects a reservation that partially overlaps (10:00-10:30 vs 10:15-10:45)', function () {
    $service = Service::create(['name' => 'Corte', 'duration_minutes' => 30, 'price' => 25.00]);
    $employee = Employee::create(['name' => 'María', 'work_start' => '09:00', 'work_end' => '18:00']);

    $this->postJson('/api/v1/reservations', [
        'service_id' => $service->id,
        'employee_id' => $employee->id,
        'start_at' => '2026-08-22T10:00:00',
    ])->assertCreated();

    $this->postJson('/api/v1/reservations', [
        'service_id' => $service->id,
        'employee_id' => $employee->id,
        'start_at' => '2026-08-22T10:15:00',
    ])->assertStatus(409);
});

it('allows an adjacent reservation (10:00-10:30 vs 10:30-11:00)', function () {
    $service = Service::create(['name' => 'Corte', 'duration_minutes' => 30, 'price' => 25.00]);
    $employee = Employee::create(['name' => 'María', 'work_start' => '09:00', 'work_end' => '18:00']);

    $this->postJson('/api/v1/reservations', [
        'service_id' => $service->id,
        'employee_id' => $employee->id,
        'start_at' => '2026-08-22T10:00:00',
    ])->assertCreated();

    $this->postJson('/api/v1/reservations', [
        'service_id' => $service->id,
        'employee_id' => $employee->id,
        'start_at' => '2026-08-22T10:30:00',
    ])->assertCreated();

    $this->assertDatabaseCount('reservations', 2);
});
