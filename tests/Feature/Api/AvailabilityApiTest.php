<?php

use App\Models\Employee;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns available slots for the employee work hours', function () {
    $employee = Employee::create(['name' => 'María', 'work_start' => '09:00', 'work_end' => '18:00']);

    $response = $this->getJson("/api/v1/employees/{$employee->id}/availability?date=2026-08-22");

    $response->assertOk()
        ->assertJsonCount(18, 'data')
        ->assertJsonPath('data.0', '09:00')
        ->assertJsonPath('data.17', '17:30');

    foreach ($response->json('data') as $slot) {
        expect($slot)->toMatch('/^\d{2}:\d{2}$/');
    }
});

it('requires a valid date parameter', function () {
    $employee = Employee::create(['name' => 'María', 'work_start' => '09:00', 'work_end' => '18:00']);

    $this->getJson("/api/v1/employees/{$employee->id}/availability")
        ->assertUnprocessable();
});

it('excludes slots occupied by active reservations', function () {
    $service = Service::create(['name' => 'Corte', 'duration_minutes' => 30, 'price' => 25.00]);
    $employee = Employee::create(['name' => 'María', 'work_start' => '09:00', 'work_end' => '18:00']);

    $this->postJson('/api/v1/reservations', [
        'service_id' => $service->id,
        'employee_id' => $employee->id,
        'start_at' => '2026-08-22T10:00:00',
    ])->assertCreated();

    $response = $this->getJson("/api/v1/employees/{$employee->id}/availability?date=2026-08-22");

    $slots = $response->assertOk()->json('data');

    expect($slots)->not->toContain('10:00')
        ->and($slots)->toContain('09:30')
        ->and($slots)->toContain('10:30')
        ->and($slots)->toHaveCount(17);
});

it('keeps slots free after a reservation is cancelled', function () {
    $service = Service::create(['name' => 'Corte', 'duration_minutes' => 30, 'price' => 25.00]);
    $employee = Employee::create(['name' => 'María', 'work_start' => '09:00', 'work_end' => '18:00']);

    $created = $this->postJson('/api/v1/reservations', [
        'service_id' => $service->id,
        'employee_id' => $employee->id,
        'start_at' => '2026-08-22T10:00:00',
    ])->assertCreated();

    $code = $created->json('data.code');

    $this->patchJson("/api/v1/reservations/{$code}/cancel")->assertOk();

    $response = $this->getJson("/api/v1/employees/{$employee->id}/availability?date=2026-08-22");

    $slots = $response->assertOk()->json('data');

    expect($slots)->toContain('10:00')->toHaveCount(18);
});
