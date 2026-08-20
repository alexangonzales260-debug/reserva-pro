<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class EmployeeController extends Controller
{
    /**
     * List active employees with their assigned services.
     */
    public function index(): AnonymousResourceCollection
    {
        return EmployeeResource::collection(
            Employee::query()
                ->where('is_active', true)
                ->with('services')
                ->get()
        );
    }

    /**
     * Store a newly created employee and sync their services.
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = Employee::query()->create($request->validated());
        $employee->services()->sync($request->validated('services', []));

        return EmployeeResource::make($employee->load('services'))->response()->setStatusCode(201);
    }

    /**
     * Show the given employee with their services.
     */
    public function show(Employee $employee): EmployeeResource
    {
        return new EmployeeResource($employee->load('services'));
    }

    /**
     * Update the given employee and re-sync their services.
     */
    public function update(StoreEmployeeRequest $request, Employee $employee): EmployeeResource
    {
        $employee->update($request->validated());
        $employee->services()->sync($request->validated('services', []));

        return new EmployeeResource($employee->load('services'));
    }

    /**
     * Soft delete the given employee (is_active = false).
     */
    public function destroy(Employee $employee): Response
    {
        $employee->update(['is_active' => false]);

        return response()->noContent();
    }
}
