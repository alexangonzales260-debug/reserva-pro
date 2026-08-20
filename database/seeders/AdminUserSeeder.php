<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed an admin user for development.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@reservafacil.test'],
            [
                'name' => 'Admin ReservaPro',
                'password' => 'password',
                'role' => User::ROLE_ADMIN,
            ]
        );
    }
}
