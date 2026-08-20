<?php

use App\Models\Employee;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a service and an employee', function () {
    $service = Service::create([
        'name' => 'Corte',
        'description' => 'Corte clásico',
        'duration_minutes' => 30,
        'price' => 25.00,
        'is_active' => true,
    ]);

    $employee = Employee::create([
        'name' => 'María López',
        'email' => 'maria@example.com',
        'phone' => '999111222',
    ]);

    expect($service->exists)->toBeTrue();
    expect($employee->exists)->toBeTrue();
    expect($service->name)->toBe('Corte');
    expect($service->price)->toBe('25.00');
    expect($employee->email)->toBe('maria@example.com');
});

it('links an employee to a service through the pivot', function () {
    $service = Service::create([
        'name' => 'Tinte',
        'duration_minutes' => 60,
        'price' => 80.00,
    ]);

    $employee = Employee::create([
        'name' => 'José García',
        'email' => 'jose@example.com',
    ]);

    $employee->services()->attach($service);

    expect($employee->services)->toHaveCount(1);
    expect($employee->services->first()->id)->toBe($service->id);
    expect($service->employees->first()->id)->toBe($employee->id);
});

it('creates a reservation with a unique code', function () {
    $user = User::factory()->create();
    $service = Service::create([
        'name' => 'Peinado',
        'duration_minutes' => 30,
        'price' => 20.00,
    ]);
    $employee = Employee::create([
        'name' => 'María López',
        'email' => 'maria@example.com',
    ]);

    $reservation = Reservation::create([
        'code' => 'RES-000001',
        'user_id' => $user->id,
        'service_id' => $service->id,
        'employee_id' => $employee->id,
        'start_at' => '2026-08-20 10:00:00',
        'end_at' => '2026-08-20 10:30:00',
        'status' => Reservation::STATUS_PENDING,
    ]);

    expect($reservation->exists)->toBeTrue();
    expect($reservation->code)->toBe('RES-000001');
    expect($reservation->user->is($user))->toBeTrue();
    expect($reservation->service->is($service))->toBeTrue();
    expect($reservation->employee->is($employee))->toBeTrue();
});

it('enforces a unique reservation code', function () {
    $user = User::factory()->create();
    $service = Service::create([
        'name' => 'Corte',
        'duration_minutes' => 30,
        'price' => 25.00,
    ]);
    $employee = Employee::create([
        'name' => 'María López',
        'email' => 'maria@example.com',
    ]);

    $attributes = [
        'code' => 'RES-UNIQUE',
        'user_id' => $user->id,
        'service_id' => $service->id,
        'employee_id' => $employee->id,
        'start_at' => '2026-08-20 10:00:00',
        'end_at' => '2026-08-20 10:30:00',
    ];

    Reservation::create($attributes);

    expect(fn () => Reservation::create($attributes))->toThrow(
        QueryException::class
    );
});

it('seeds an admin user with the admin role', function () {
    $this->seed(AdminUserSeeder::class);

    $admin = User::query()->where('email', 'admin@reservafacil.test')->first();

    expect($admin)->not->toBeNull();
    expect($admin->role)->toBe(User::ROLE_ADMIN);
    expect($admin->isAdmin())->toBeTrue();
});
