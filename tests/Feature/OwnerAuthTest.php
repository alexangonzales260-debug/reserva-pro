<?php

use App\Models\Negocio;
use App\Models\User;
use App\Support\CurrentNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function registerOwnerPayload(string $email = 'dueno@test.com'): array
{
    return [
        'nombre' => 'Peluquería Test',
        'email' => $email,
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];
}

beforeEach(function (): void {
    CurrentNegocio::clear();
});

afterEach(function (): void {
    CurrentNegocio::clear();
});

it('registers an owner creating user and negocio atomically with auto-login', function () {
    $response = $this->postJson('/api/v1/auth/register', registerOwnerPayload());

    $response->assertCreated()
        ->assertJsonPath('data.user.role', User::ROLE_OWNER)
        ->assertJsonPath('data.negocio.nombre', 'Peluquería Test');

    $this->assertDatabaseHas('users', [
        'email' => 'dueno@test.com',
        'role' => User::ROLE_OWNER,
    ]);

    $user = User::query()->where('email', 'dueno@test.com')->firstOrFail();
    $negocio = Negocio::query()->where('user_id', $user->id)->firstOrFail();

    $this->assertDatabaseHas('negocios', [
        'nombre' => 'Peluquería Test',
        'user_id' => $user->id,
    ]);

    expect($user->negocio_id)->toBe($negocio->id);

    $this->assertAuthenticatedAs($user);
});

it('rejects registration with a duplicate email', function () {
    User::create([
        'name' => 'Otro',
        'email' => 'dueno@test.com',
        'password' => 'password123',
        'role' => User::ROLE_CLIENT,
    ]);

    $this->postJson('/api/v1/auth/register', registerOwnerPayload())
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('logs in an owner and sets CurrentNegocio', function () {
    $this->postJson('/api/v1/auth/register', registerOwnerPayload())->assertCreated();

    CurrentNegocio::clear();

    $this->postJson('/api/v1/auth/login', [
        'email' => 'dueno@test.com',
        'password' => 'password123',
    ])->assertOk()
        ->assertJsonPath('data.user.role', User::ROLE_OWNER);

    expect(CurrentNegocio::get())->toBe(Negocio::query()->firstOrFail()->id);
});

it('rejects owner login with an incorrect password', function () {
    $this->postJson('/api/v1/auth/register', registerOwnerPayload())->assertCreated();

    $this->postJson('/api/v1/auth/login', [
        'email' => 'dueno@test.com',
        'password' => 'wrong-pass',
    ])->assertStatus(422)
        ->assertJsonPath('message', 'Credenciales inválidas.');
});

it('rejects owner login for a non-owner user', function () {
    User::create([
        'name' => 'Cliente',
        'email' => 'cliente@test.com',
        'password' => 'password123',
        'role' => User::ROLE_CLIENT,
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'cliente@test.com',
        'password' => 'password123',
    ])->assertStatus(403)
        ->assertJsonPath('message', 'Acceso denegado.');
});

it('logs out clearing session and CurrentNegocio', function () {
    $this->postJson('/api/v1/auth/register', registerOwnerPayload())->assertCreated();

    $this->postJson('/api/v1/auth/logout')->assertOk();

    expect(CurrentNegocio::isSet())->toBeFalse();
    $this->assertGuest();
});

it('sets CurrentNegocio via middleware for an authenticated owner on subsequent requests', function () {
    $this->postJson('/api/v1/auth/register', registerOwnerPayload())->assertCreated();

    $negocio = Negocio::query()->firstOrFail();

    CurrentNegocio::clear();
    $this->getJson('/api/v1/services')->assertOk();

    expect(CurrentNegocio::get())->toBe($negocio->id);
});
