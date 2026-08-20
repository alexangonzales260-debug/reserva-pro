<?php

use App\Models\Employee;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs in the seeded owner with valid credentials', function () {
    $this->seed(AdminUserSeeder::class);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'admin@reservapro.pe',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.email', 'admin@reservapro.pe')
        ->assertJsonPath('data.role', User::ROLE_ADMIN);

    $this->assertAuthenticated();
});

it('rejects login with an incorrect password', function () {
    $this->seed(AdminUserSeeder::class);

    $this->postJson('/api/v1/login', [
        'email' => 'admin@reservapro.pe',
        'password' => 'wrong-password',
    ])->assertStatus(401)
        ->assertJsonPath('message', 'Credenciales inválidas.');
});

it('rejects login for a non-admin client', function () {
    User::create([
        'name' => 'Cliente',
        'email' => 'cliente@reservafacil.test',
        'password' => 'password',
        'role' => User::ROLE_CLIENT,
    ]);

    $this->postJson('/api/v1/login', [
        'email' => 'cliente@reservafacil.test',
        'password' => 'password',
    ])->assertStatus(401)
        ->assertJsonPath('message', 'Credenciales inválidas.');
});

it('rejects unauthenticated service creation', function () {
    $this->postJson('/api/v1/services', [
        'name' => 'Corte',
        'duration_minutes' => 30,
        'price' => 25.00,
    ])->assertStatus(401)
        ->assertJsonPath('message', 'No autenticado.');
});

it('creates a service when the owner is authenticated', function () {
    $this->seed(AdminUserSeeder::class);

    $this->postJson('/api/v1/login', [
        'email' => 'admin@reservapro.pe',
        'password' => 'password',
    ])->assertOk();

    $this->postJson('/api/v1/services', [
        'name' => 'Corte',
        'duration_minutes' => 30,
        'price' => 25.00,
    ])->assertCreated();
});

it('keeps service listing public', function () {
    $this->getJson('/api/v1/services')->assertOk();
});

it('rejects unauthenticated employee creation but allows anonymous reservations', function () {
    $this->postJson('/api/v1/employees', [
        'name' => 'María',
        'work_start' => '09:00',
        'work_end' => '18:00',
    ])->assertStatus(401);

    $service = Service::create(['name' => 'Corte', 'duration_minutes' => 30, 'price' => 25.00]);
    $employee = Employee::create(['name' => 'Ana', 'work_start' => '09:00', 'work_end' => '18:00']);

    $this->postJson('/api/v1/reservations', [
        'service_id' => $service->id,
        'employee_id' => $employee->id,
        'start_at' => '2026-08-22T10:00:00',
    ])->assertCreated();
});

it('creates an employee when the owner is authenticated', function () {
    $this->seed(AdminUserSeeder::class);

    $this->postJson('/api/v1/login', [
        'email' => 'admin@reservapro.pe',
        'password' => 'password',
    ])->assertOk();

    $this->postJson('/api/v1/employees', [
        'name' => 'María',
        'work_start' => '09:00',
        'work_end' => '18:00',
    ])->assertCreated();
});
