<?php

namespace App\Actions;

use App\Models\Reservation;
use Carbon\Carbon;

class CheckReservationConflict
{
    /**
     * Determine whether the employee already has an active reservation
     * overlapping the range [startAt, endAt).
     *
     * Overlap: start_at < $endAt AND end_at > $startAt.
     */
    public function handle(int $employeeId, Carbon $startAt, Carbon $endAt, ?int $excludeReservationId = null): bool
    {
        $query = Reservation::query()
            ->where('employee_id', $employeeId)
            ->whereIn('status', [Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED])
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt);

        if ($excludeReservationId !== null) {
            $query->where('id', '!=', $excludeReservationId);
        }

        return $query->exists();
    }
}
