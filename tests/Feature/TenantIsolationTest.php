<?php

use App\Models\Employee;
use App\Models\Negocio;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use App\Support\CurrentNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeTenantOwner(string $email): User
{
    return User::create([
        'name' => 'Dueño',
        'email' => $email,
        'password' => 'password',
        'role' => User::ROLE_ADMIN,
    ]);
}

function makeTenantNegocio(string $slug, User $owner): Negocio
{
    return Negocio::create([
        'nombre' => ucfirst($slug),
        'slug' => $slug,
        'user_id' => $owner->id,
    ]);
}

function seedTenantWorld(): array
{
    $ownerA = makeTenantOwner('a@test.com');
    $ownerB = makeTenantOwner('b@test.com');

    $negocioA = makeTenantNegocio('negocio-a', $ownerA);
    $negocioB = makeTenantNegocio('negocio-b', $ownerB);

    $serviceA = Service::create([
        'name' => 'Corte A',
        'duration_minutes' => 30,
        'price' => 25.00,
        'negocio_id' => $negocioA->id,
    ]);
    $serviceB = Service::create([
        'name' => 'Corte B',
        'duration_minutes' => 30,
        'price' => 25.00,
        'negocio_id' => $negocioB->id,
    ]);

    $employeeA = Employee::create([
        'name' => 'Ana A',
        'work_start' => '09:00',
        'work_end' => '18:00',
        'negocio_id' => $negocioA->id,
    ]);
    $employeeB = Employee::create([
        'name' => 'Ana B',
        'work_start' => '09:00',
        'work_end' => '18:00',
        'negocio_id' => $negocioB->id,
    ]);

    $reservationA = Reservation::create([
        'code' => 'RF-TENA01',
        'service_id' => $serviceA->id,
        'employee_id' => $employeeA->id,
        'start_at' => '2026-08-22 10:00:00',
        'end_at' => '2026-08-22 10:30:00',
        'status' => Reservation::STATUS_PENDING,
        'negocio_id' => $negocioA->id,
    ]);
    $reservationB = Reservation::create([
        'code' => 'RF-TENB01',
        'service_id' => $serviceB->id,
        'employee_id' => $employeeB->id,
        'start_at' => '2026-08-22 11:00:00',
        'end_at' => '2026-08-22 11:30:00',
        'status' => Reservation::STATUS_PENDING,
        'negocio_id' => $negocioB->id,
    ]);

    return compact(
        'negocioA',
        'negocioB',
        'serviceA',
        'serviceB',
        'employeeA',
        'employeeB',
        'reservationA',
        'reservationB'
    );
}

beforeEach(function (): void {
    CurrentNegocio::clear();
});

afterEach(function (): void {
    CurrentNegocio::clear();
});

it('filters services by the active negocio', function () {
    $world = seedTenantWorld();

    CurrentNegocio::set($world['negocioA']->id);
    expect(Service::query()->pluck('name')->all())->toBe(['Corte A']);

    CurrentNegocio::set($world['negocioB']->id);
    expect(Service::query()->pluck('name')->all())->toBe(['Corte B']);
});

it('filters employees by the active negocio', function () {
    $world = seedTenantWorld();

    CurrentNegocio::set($world['negocioA']->id);
    expect(Employee::query()->pluck('name')->all())->toBe(['Ana A']);

    CurrentNegocio::set($world['negocioB']->id);
    expect(Employee::query()->pluck('name')->all())->toBe(['Ana B']);
});

it('filters reservations by the active negocio', function () {
    $world = seedTenantWorld();

    CurrentNegocio::set($world['negocioA']->id);
    expect(Reservation::query()->pluck('code')->all())->toBe(['RF-TENA01']);

    CurrentNegocio::set($world['negocioB']->id);
    expect(Reservation::query()->pluck('code')->all())->toBe(['RF-TENB01']);
});

it('returns everything without an active negocio (backwards compatible)', function () {
    seedTenantWorld();

    expect(Service::query()->count())->toBe(2);
    expect(Employee::query()->count())->toBe(2);
    expect(Reservation::query()->count())->toBe(2);
});

it('never filters the Negocio model itself (root tenant)', function () {
    $world = seedTenantWorld();

    CurrentNegocio::set($world['negocioA']->id);
    expect(Negocio::query()->count())->toBe(2);
});

it('applies the scope to related queries', function () {
    $world = seedTenantWorld();
    $world['employeeA']->services()->attach($world['serviceA']->id);

    CurrentNegocio::set($world['negocioA']->id);
    expect($world['employeeA']->services()->pluck('name')->all())->toBe(['Corte A']);

    CurrentNegocio::set($world['negocioB']->id);
    expect($world['employeeA']->services()->pluck('name')->all())->toBe([]);
});
