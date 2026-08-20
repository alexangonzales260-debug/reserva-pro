<?php

use App\Models\Employee;
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
