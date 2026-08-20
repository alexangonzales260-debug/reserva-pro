<?php

namespace Database\Seeders;

use App\Models\Negocio;
use App\Models\User;
use Illuminate\Database\Seeder;

class NegocioSeeder extends Seeder
{
    /**
     * Seed the default demo negocio owned by the admin user.
     */
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@reservapro.pe')->firstOrFail();

        Negocio::query()->updateOrCreate(
            ['slug' => 'peluqueria-demo'],
            [
                'nombre' => 'Peluquería Demo',
                'user_id' => $admin->id,
            ]
        );
    }
}
