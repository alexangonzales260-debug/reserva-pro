<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    /**
     * Return the employee's available slots (HH:MM, every 30 minutes) for a given date.
     * Placeholder: conflict exclusion arrives in Fase 3.
     */
    public function index(Request $request, Employee $employee): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $date = $validated['date'];

        $start = Carbon::parse("{$date} {$employee->work_start}");
        $end = Carbon::parse("{$date} {$employee->work_end}");

        $slots = [];

        while ($start->lt($end)) {
            $slots[] = $start->format('H:i');
            $start->addMinutes(30);
        }

        return response()->json(['data' => $slots]);
    }
}
