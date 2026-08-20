<?php

namespace Database\Seeders;

use App\Models\Negocio;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Seed sample services linked to the demo negocio.
     */
    public function run(): void
    {
        $negocio = Negocio::query()->where('slug', 'peluqueria-demo')->firstOrFail();

        $services = [
            ['name' => 'Corte', 'description' => 'Corte de cabello clásico', 'duration_minutes' => 30, 'price' => 25.00],
            ['name' => 'Tinte', 'description' => 'Tinte y coloración completa', 'duration_minutes' => 60, 'price' => 80.00],
            ['name' => 'Peinado', 'description' => 'Peinado y styling', 'duration_minutes' => 30, 'price' => 20.00],
        ];

        foreach ($services as $service) {
            $service['negocio_id'] = $negocio->id;

            Service::query()->updateOrCreate(
                ['name' => $service['name']],
                $service
            );
        }
    }
}
