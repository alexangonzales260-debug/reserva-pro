<?php

use App\Models\Service;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(AdminUserSeeder::class);
    $this->actingAs(User::query()->where('email', 'admin@reservapro.pe')->first());
});

it('lists only active services', function () {
    Service::create(['name' => 'Corte', 'duration_minutes' => 30, 'price' => 25.00]);
    Service::create(['name' => 'Tinte', 'duration_minutes' => 60, 'price' => 80.00, 'is_active' => false]);

    $response = $this->getJson('/api/v1/services');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Corte');
});

it('stores a new service', function () {
    $response = $this->postJson('/api/v1/services', [
        'name' => 'Corte',
        'duration_minutes' => 30,
        'price' => 25.00,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Corte')
        ->assertJsonPath('data.duration_minutes', 30)
        ->assertJsonPath('data.price', '25.00')
        ->assertJsonPath('data.active', true);

    $this->assertDatabaseHas('services', ['name' => 'Corte']);
});

it('updates a service', function () {
    $service = Service::create(['name' => 'Corte', 'duration_minutes' => 30, 'price' => 25.00]);

    $response = $this->putJson("/api/v1/services/{$service->id}", [
        'name' => 'Corte Premium',
        'duration_minutes' => 45,
        'price' => 40.00,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Corte Premium')
        ->assertJsonPath('data.duration_minutes', 45);

    $this->assertDatabaseHas('services', ['name' => 'Corte Premium']);
});

it('soft deletes a service', function () {
    $service = Service::create(['name' => 'Corte', 'duration_minutes' => 30, 'price' => 25.00]);

    $response = $this->deleteJson("/api/v1/services/{$service->id}");

    $response->assertNoContent();
    $this->assertDatabaseHas('services', ['id' => $service->id, 'is_active' => false]);
});
