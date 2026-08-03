<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FieldLocation;
use App\Services\LocationRegistryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FieldLocationController extends Controller
{
    public function __construct(
        protected LocationRegistryService $locationRegistryService
    ) {}

    public function index(): JsonResponse
    {
        $locations = FieldLocation::with(['parent', 'children'])
            ->where('is_active', true)
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $locations,
        ]);
    }

    public function updateGeofence(Request $request, FieldLocation $fieldLocation): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'geofence_radius_meters' => 'required|integer|min:10',
            'reason' => 'nullable|string',
        ]);

        $updated = $this->locationRegistryService->updateGeofenceProspectively(
            location: $fieldLocation,
            latitude: (float) $validated['latitude'],
            longitude: (float) $validated['longitude'],
            radiusMeters: (int) $validated['geofence_radius_meters'],
            reason: $validated['reason'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $updated,
        ]);
    }
}
