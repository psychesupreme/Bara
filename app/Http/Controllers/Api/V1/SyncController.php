<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityEvidence;
use App\Models\VerificationEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    /**
     * Push offline Isar DB logs (chunk size max 50).
     */
    public function pushLogs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'logs' => 'required|array|max:50',
            'logs.*.client_uuid' => 'required|string|uuid',
            'logs.*.entity_type' => 'required|string|in:activity,verification_event,evidence,sales_order',
            'logs.*.sequence' => 'required|integer',
            'logs.*.payload' => 'required|array',
        ]);

        $processed = [];
        $conflicts = [];

        DB::transaction(function () use ($validated, &$processed, &$conflicts) {
            foreach ($validated['logs'] as $log) {
                $clientUuid = $log['client_uuid'];
                $entityType = $log['entity_type'];
                $sequence = $log['sequence'];
                $payload = $log['payload'];

                switch ($entityType) {
                    case 'activity':
                        $existing = Activity::where('client_uuid', $clientUuid)->first();
                        if (!$existing) {
                            $activity = Activity::create(array_merge($payload, [
                                'client_uuid' => $clientUuid,
                                'sequence' => $sequence,
                                'is_offline_captured' => true,
                            ]));
                            $processed[] = $clientUuid;
                        } else {
                            // LWW (Last Write Wins) sequence check
                            if ($sequence > $existing->sequence) {
                                $existing->update(array_merge($payload, ['sequence' => $sequence]));
                                $processed[] = $clientUuid;
                            } else {
                                $conflicts[] = [
                                    'client_uuid' => $clientUuid,
                                    'reason' => 'Server sequence is equal or newer',
                                    'server_sequence' => $existing->sequence,
                                ];
                            }
                        }
                        break;

                    case 'verification_event':
                        VerificationEvent::firstOrCreate(
                            ['client_uuid' => $clientUuid],
                            array_merge($payload, ['sequence' => $sequence])
                        );
                        $processed[] = $clientUuid;
                        break;

                    case 'evidence':
                        ActivityEvidence::firstOrCreate(
                            ['client_uuid' => $clientUuid],
                            array_merge($payload, ['sequence' => $sequence])
                        );
                        $processed[] = $clientUuid;
                        break;

                    case 'sales_order':
                        $existing = \App\Models\SalesOrder::where('client_uuid', $clientUuid)->first();
                        if (!$existing) {
                            \App\Models\SalesOrder::create(array_merge($payload, [
                                'client_uuid' => $clientUuid,
                                'sequence' => $sequence,
                                'is_offline_captured' => true,
                            ]));
                            $processed[] = $clientUuid;
                        } else {
                            if ($sequence > $existing->sequence) {
                                $existing->update(array_merge($payload, ['sequence' => $sequence]));
                                $processed[] = $clientUuid;
                            } else {
                                $conflicts[] = [
                                    'client_uuid' => $clientUuid,
                                    'reason' => 'Server sequence is equal or newer',
                                    'server_sequence' => $existing->sequence,
                                ];
                            }
                        }
                        break;
                }
            }
        });

        return response()->json([
            'success' => true,
            'processed_uuids' => $processed,
            'conflicts' => $conflicts,
            'synced_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Pull updated entity deltas since timestamp or sequence.
     */
    public function pullDeltas(Request $request): JsonResponse
    {
        try {
            $since = $request->query('since_timestamp');
            $timestamp = $since ? \Carbon\Carbon::parse($since) : now()->subDays(7);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Invalid timestamp format'], 400);
        }

        $activities = Activity::where('updated_at', '>=', $timestamp)->limit(200)->get();
        $verifications = VerificationEvent::where('updated_at', '>=', $timestamp)->limit(200)->get();

        return response()->json([
            'success' => true,
            'timestamp' => now()->toIso8601String(),
            'deltas' => [
                'activities' => $activities,
                'verification_events' => $verifications,
            ],
        ]);
    }
}
