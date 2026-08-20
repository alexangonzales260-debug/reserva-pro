<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ServiceController extends Controller
{
    /**
     * List active services.
     */
    public function index(): AnonymousResourceCollection
    {
        return ServiceResource::collection(
            Service::query()->where('is_active', true)->get()
        );
    }

    /**
     * Store a newly created service.
     */
    public function store(StoreServiceRequest $request): JsonResponse
    {
        $service = Service::query()->create($request->validated())->refresh();

        return ServiceResource::make($service)->response()->setStatusCode(201);
    }

    /**
     * Show the given service.
     */
    public function show(Service $service): ServiceResource
    {
        return new ServiceResource($service);
    }

    /**
     * Update the given service.
     */
    public function update(StoreServiceRequest $request, Service $service): ServiceResource
    {
        $service->update($request->validated());

        return new ServiceResource($service);
    }

    /**
     * Soft delete the given service (is_active = false).
     */
    public function destroy(Service $service): Response
    {
        $service->update(['is_active' => false]);

        return response()->noContent();
    }
}
