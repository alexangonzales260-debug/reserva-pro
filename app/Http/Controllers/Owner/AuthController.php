<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginOwnerRequest;
use App\Http\Requests\RegisterOwnerRequest;
use App\Models\Negocio;
use App\Models\User;
use App\Support\CurrentNegocio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Register an owner: creates User + Negocio atomically and logs in.
     */
    public function register(RegisterOwnerRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated): User {
            $user = User::create([
                'name' => $validated['nombre'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => User::ROLE_OWNER,
            ]);

            $negocio = Negocio::create([
                'nombre' => $validated['nombre'],
                'slug' => $this->uniqueSlug($validated['nombre']),
                'user_id' => $user->id,
            ]);

            $user->update(['negocio_id' => $negocio->id]);

            return $user;
        });

        Auth::login($user);
        CurrentNegocio::set($user->negocio_id);

        $request->session()->regenerate();

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'negocio' => [
                    'id' => $user->negocio_id,
                    'nombre' => $user->negocio->nombre,
                    'slug' => $user->negocio->slug,
                ],
            ],
        ], 201);
    }

    /**
     * Authenticate an owner and activate their negocio context.
     */
    public function login(LoginOwnerRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::query()->where('email', $validated['email'])->first();

        if ($user === null || ! Auth::attempt($validated)) {
            return response()->json(['message' => 'Credenciales inválidas.'], 422);
        }

        if (! $user->isOwner()) {
            Auth::logout();

            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        CurrentNegocio::set($user->negocio_id);
        $request->session()->regenerate();

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'negocio' => $user->negocio !== null ? [
                    'id' => $user->negocio->id,
                    'nombre' => $user->negocio->nombre,
                    'slug' => $user->negocio->slug,
                ] : null,
            ],
        ]);
    }

    /**
     * Close the owner's session and clear the negocio context.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        CurrentNegocio::clear();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    private function uniqueSlug(string $nombre): string
    {
        $base = Str::slug($nombre);
        $slug = $base;
        $i = 1;

        while (Negocio::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
