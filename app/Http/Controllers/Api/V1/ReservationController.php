<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CheckReservationConflict;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class ReservationController extends Controller
{
    private CheckReservationConflict $conflictChecker;

    public function __construct(CheckReservationConflict $conflictChecker)
    {
        $this->conflictChecker = $conflictChecker;
    }

    /**
     * List reservations with optional filters (status, employee_id, date).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Reservation::query()
            ->with(['user', 'service', 'employee.services'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        if ($request->filled('date')) {
            $query->whereDate('start_at', $request->string('date'));
        }

        return ReservationResource::collection($query->get());
    }

    /**
     * Store a newly created reservation with a unique RF-XXXXXX code.
     */
    public function store(StoreReservationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $service = Service::query()->findOrFail($data['service_id']);
        $startAt = Carbon::parse($data['start_at']);
        $endAt = $startAt->copy()->addMinutes($service->duration_minutes);

        if ($this->conflictChecker->handle($data['employee_id'], $startAt, $endAt)) {
            return response()->json([
                'message' => 'El empleado ya tiene una reserva en ese horario.',
            ], 409);
        }

        $reservation = Reservation::query()->create([
            'code' => $this->generateUniqueCode(),
            'service_id' => $service->id,
            'employee_id' => $data['employee_id'],
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => Reservation::STATUS_PENDING,
            'notes' => $data['notes'] ?? null,
        ]);

        return ReservationResource::make(
            $reservation->load(['user', 'service', 'employee.services'])
        )->response()->setStatusCode(201);
    }

    /**
     * Show a reservation by its unique code.
     */
    public function show(string $code): ReservationResource|JsonResponse
    {
        $reservation = $this->findByCode($code);

        if ($reservation === null) {
            return response()->json(['message' => 'Reserva no encontrada.'], 404);
        }

        return new ReservationResource($reservation);
    }

    /**
     * Cancel a reservation (only when pending or confirmed).
     */
    public function cancel(string $code): JsonResponse
    {
        $reservation = $this->findByCode($code);

        if ($reservation === null) {
            return response()->json(['message' => 'Reserva no encontrada.'], 404);
        }

        if (! in_array($reservation->status, [Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED], true)) {
            return response()->json([
                'message' => 'Solo se pueden cancelar reservas pendientes o confirmadas.',
            ], 422);
        }

        $reservation->update(['status' => Reservation::STATUS_CANCELLED]);

        return response()->json(['message' => 'Reserva cancelada correctamente.']);
    }

    private function findByCode(string $code): ?Reservation
    {
        return Reservation::query()
            ->with(['user', 'service', 'employee.services'])
            ->where('code', $code)
            ->first();
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'RF-'.Str::upper(Str::random(6));
        } while (Reservation::query()->where('code', $code)->exists());

        return $code;
    }
}
