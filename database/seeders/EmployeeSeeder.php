<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Service;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Seed sample employees and link them to all services.
     */
    public function run(): void
    {
        $employees = [
            ['name' => 'María López', 'email' => 'maria@reservapro.pe', 'phone' => '999111222'],
            ['name' => 'José García', 'email' => 'jose@reservapro.pe', 'phone' => '999333444'],
        ];

        $services = Service::query()->pluck('id');

        foreach ($employees as $data) {
            $employee = Employee::query()->updateOrCreate(
                ['email' => $data['email']],
                $data
            );

            $employee->services()->sync($services);
        }
    }
}
