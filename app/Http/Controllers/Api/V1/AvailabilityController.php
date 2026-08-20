<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    /**
     * Return the employee's free slots (HH:MM, every 30 minutes) for a given date,
     * excluding slots that overlap any active (pending/confirmed) reservation.
     */
    public function index(Request $request, Employee $employee): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $date = $validated['date'];

        $start = Carbon::parse("{$date} {$employee->work_start}");
        $end = Carbon::parse("{$date} {$employee->work_end}");

        $reservations = $this->activeReservationsForDate($employee, $date);

        $slots = [];

        while ($start->lt($end)) {
            $slotEnd = $start->copy()->addMinutes(30);

            if (! $this->isSlotBusy($start, $slotEnd, $reservations)) {
                $slots[] = $start->format('H:i');
            }

            $start = $slotEnd;
        }

        return response()->json(['data' => $slots]);
    }

    /**
     * Active reservations overlapping the given business day.
     *
     * @return array<int, array{start_at: mixed, end_at: mixed}>
     */
    private function activeReservationsForDate(Employee $employee, string $date): array
    {
        $dayStart = Carbon::parse("{$date} 00:00:00");
        $dayEnd = $dayStart->copy()->addDay();

        return Reservation::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', [Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED])
            ->where('start_at', '<', $dayEnd)
            ->where('end_at', '>', $dayStart)
            ->get(['start_at', 'end_at'])
            ->toArray();
    }

    /**
     * @param  array<int, array{start_at: mixed, end_at: mixed}>  $reservations
     */
    private function isSlotBusy(Carbon $slotStart, Carbon $slotEnd, array $reservations): bool
    {
        foreach ($reservations as $reservation) {
            $reservationStart = Carbon::parse($reservation['start_at']);
            $reservationEnd = Carbon::parse($reservation['end_at']);

            if ($slotStart->lt($reservationEnd) && $slotEnd->gt($reservationStart)) {
                return true;
            }
        }

        return false;
    }
}
