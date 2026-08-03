<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\TelemetryPingEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckInOutletController extends Controller
{
    /**
     * Handle live mobile check-in and broadcast GPS telemetry.
     */
    public function checkIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|string',
            'outlet_name' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $user = $request->user();
        $repId = 'REP-' . ($user ? $user->id : '001');
        $repName = $user ? $user->name : 'Central Field Rep';
        $lat = (float) $validated['latitude'];
        $lng = (float) $validated['longitude'];
        $timestamp = now()->toTimeString();

        // Broadcast real-time GPS telemetry to WebAdmin dispatch map
        event(new TelemetryPingEvent(
            repId: $repId,
            repName: $repName,
            outletName: $validated['outlet_name'],
            latitude: $lat,
            longitude: $lng,
            timestamp: $timestamp
        ));

        return response()->json([
            'success' => true,
            'message' => 'Check-in recorded and telemetry broadcasted.',
            'data' => [
                'rep_id' => $repId,
                'rep_name' => $repName,
                'outlet_name' => $validated['outlet_name'],
                'latitude' => $lat,
                'longitude' => $lng,
                'timestamp' => $timestamp,
            ],
        ]);
    }
}
