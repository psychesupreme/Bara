<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DatabaseBackup;
use App\Models\SystemNotice;
use App\Services\BackupRestoreService;
use App\Services\DataImporterService;
use App\Services\SystemUtilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemUtilityController extends Controller
{
    public function __construct(
        protected SystemUtilityService $systemService,
        protected DataImporterService $importerService,
        protected BackupRestoreService $backupService
    ) {}

    public function unifiedCalendar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        $calendar = $this->systemService->getUnifiedCalendar(
            user: $request->user(),
            start: Carbon::parse($validated['start']),
            end: Carbon::parse($validated['end'])
        );

        return response()->json([
            'success' => true,
            'data' => $calendar,
        ]);
    }

    public function broadcastNotice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'message' => 'required|string',
            'target_role' => 'nullable|string',
            'is_mandatory' => 'nullable|boolean',
        ]);

        $notice = $this->systemService->broadcastNotice(
            title: $validated['title'],
            message: $validated['message'],
            targetRole: $validated['target_role'] ?? null,
            isMandatory: $validated['is_mandatory'] ?? false
        );

        return response()->json([
            'success' => true,
            'data' => $notice,
        ], 201);
    }

    public function acknowledgeNotice(Request $request, SystemNotice $notice): JsonResponse
    {
        $ack = $this->systemService->acknowledgeNotice($notice, $request->user());

        return response()->json([
            'success' => true,
            'data' => $ack,
        ]);
    }

    public function dryRunImport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file_name' => 'required|string',
            'entity_type' => 'required|string|in:customers,products,users',
            'rows' => 'required|array|min:1',
            'commit' => 'nullable|boolean',
        ]);

        $job = $this->importerService->validateAndImport(
            fileName: $validated['file_name'],
            entityType: $validated['entity_type'],
            rows: $validated['rows'],
            commit: $validated['commit'] ?? false
        );

        return response()->json([
            'success' => true,
            'data' => $job,
        ]);
    }

    public function triggerBackup(Request $request): JsonResponse
    {
        $backup = $this->backupService->createBackup();

        return response()->json([
            'success' => true,
            'data' => $backup,
        ], 201);
    }
}
