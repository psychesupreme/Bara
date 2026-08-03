<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FieldDevice;
use App\Services\DeviceLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function __construct(
        protected DeviceLifecycleService $deviceLifecycleService
    ) {}

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_uuid' => 'required|string',
            'model' => 'nullable|string',
            'os_version' => 'nullable|string',
            'app_version' => 'nullable|string',
            'public_key_hash' => 'nullable|string',
        ]);

        $device = $this->deviceLifecycleService->registerDevice(
            user: $request->user(),
            deviceUuid: $validated['device_uuid'],
            model: $validated['model'] ?? null,
            osVersion: $validated['os_version'] ?? null,
            appVersion: $validated['app_version'] ?? null,
            publicKeyHash: $validated['public_key_hash'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $device,
        ]);
    }

    public function approve(FieldDevice $device): JsonResponse
    {
        $approved = $this->deviceLifecycleService->approveDevice($device);

        return response()->json([
            'success' => true,
            'data' => $approved,
        ]);
    }

    public function revoke(FieldDevice $device): JsonResponse
    {
        $revoked = $this->deviceLifecycleService->revokeDevice($device);

        return response()->json([
            'success' => true,
            'data' => $revoked,
        ]);
    }
}
