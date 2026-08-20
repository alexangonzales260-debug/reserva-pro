<?php

use App\Http\Controllers\Api\V1\AvailabilityController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\ReservationController;
use App\Http\Controllers\Api\V1\ServiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::apiResource('services', ServiceController::class);
    Route::apiResource('employees', EmployeeController::class);

    Route::get('reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::post('reservations', [ReservationController::class, 'store'])->name('reservations.store');
    Route::get('reservations/{code}', [ReservationController::class, 'show'])->name('reservations.show');

    Route::patch('reservations/{code}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');

    Route::get('employees/{employee}/availability', [AvailabilityController::class, 'index'])->name('employees.availability');
});
