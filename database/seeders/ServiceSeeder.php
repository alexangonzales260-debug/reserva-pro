<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Seed sample services.
     */
    public function run(): void
    {
        $services = [
            ['name' => 'Corte', 'description' => 'Corte de cabello clásico', 'duration_minutes' => 30, 'price' => 25.00],
            ['name' => 'Tinte', 'description' => 'Tinte y coloración completa', 'duration_minutes' => 60, 'price' => 80.00],
            ['name' => 'Peinado', 'description' => 'Peinado y styling', 'duration_minutes' => 30, 'price' => 20.00],
        ];

        foreach ($services as $service) {
            Service::query()->updateOrCreate(
                ['name' => $service['name']],
                $service
            );
        }
    }
}
