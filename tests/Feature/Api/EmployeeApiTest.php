<?php

use App\Models\Employee;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists active employees with their services', function () {
    $service = Service::create(['name' => 'Corte', 'duration_minutes' => 30, 'price' => 25.00]);
    $employee = Employee::create(['name' => 'María', 'work_start' => '09:00', 'work_end' => '18:00']);
    $employee->services()->attach($service);

    Employee::create(['name' => 'Inactiva', 'work_start' => '09:00', 'work_end' => '18:00', 'is_active' => false]);

    $response = $this->getJson('/api/v1/employees');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'María')
        ->assertJsonPath('data.0.work_start', '09:00')
        ->assertJsonPath('data.0.work_end', '18:00')
        ->assertJsonPath('data.0.services.0.id', $service->id);
});

it('stores an employee with synced services', function () {
    $service = Service::create(['name' => 'Corte', 'duration_minutes' => 30, 'price' => 25.00]);

    $response = $this->postJson('/api/v1/employees', [
        'name' => 'María',
        'work_start' => '09:00',
        'work_end' => '18:00',
        'services' => [$service->id],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'María')
        ->assertJsonPath('data.work_start', '09:00')
        ->assertJsonPath('data.services.0.id', $service->id);

    $this->assertDatabaseHas('employee_service', [
        'employee_id' => $response->json('data.id'),
        'service_id' => $service->id,
    ]);
});

it('updates an employee and re-syncs services', function () {
    $service = Service::create(['name' => 'Corte', 'duration_minutes' => 30, 'price' => 25.00]);
    $employee = Employee::create(['name' => 'María', 'work_start' => '09:00', 'work_end' => '18:00']);
    $employee->services()->attach($service);

    $other = Service::create(['name' => 'Tinte', 'duration_minutes' => 60, 'price' => 80.00]);

    $response = $this->putJson("/api/v1/employees/{$employee->id}", [
        'name' => 'María López',
        'work_start' => '10:00',
        'work_end' => '19:00',
        'services' => [$other->id],
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'María López')
        ->assertJsonPath('data.work_start', '10:00')
        ->assertJsonPath('data.services.0.id', $other->id);

    $this->assertDatabaseMissing('employee_service', [
        'employee_id' => $employee->id,
        'service_id' => $service->id,
    ]);
});

it('soft deletes an employee', function () {
    $employee = Employee::create(['name' => 'María', 'work_start' => '09:00', 'work_end' => '18:00']);

    $response = $this->deleteJson("/api/v1/employees/{$employee->id}");

    $response->assertNoContent();
    $this->assertDatabaseHas('employees', ['id' => $employee->id, 'is_active' => false]);
});
