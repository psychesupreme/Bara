<?php

namespace App\Http\Middleware;

use App\Models\FieldDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeviceNotRevoked
{
    public function handle(Request $request, Closure $next): Response
    {
        $deviceId = $request->header('X-Device-UUID') ?? $request->input('device_id');

        if ($deviceId) {
            $device = FieldDevice::where('device_uuid', $deviceId)->first();
            if ($device && $device->status === 'revoked') {
                return response()->json([
                    'success' => false,
                    'message' => 'Device access has been revoked by administration.',
                    'code' => 'DEVICE_REVOKED',
                ], 403);
            }
        }

        return $next($request);
    }
}
