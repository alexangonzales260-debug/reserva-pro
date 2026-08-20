<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Negocio;
use App\Models\Service;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Seed sample employees linked to the demo negocio and to all services.
     */
    public function run(): void
    {
        $negocio = Negocio::query()->where('slug', 'peluqueria-demo')->firstOrFail();

        $employees = [
            ['name' => 'María López', 'email' => 'maria@reservapro.pe', 'phone' => '999111222'],
            ['name' => 'José García', 'email' => 'jose@reservapro.pe', 'phone' => '999333444'],
        ];

        $services = Service::query()->pluck('id');

        foreach ($employees as $data) {
            $data['negocio_id'] = $negocio->id;

            $employee = Employee::query()->updateOrCreate(
                ['email' => $data['email']],
                $data
            );

            $employee->services()->sync($services);
        }
    }
}
