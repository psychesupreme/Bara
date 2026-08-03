<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Timesheet;
use App\Services\TimesheetEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimesheetController extends Controller
{
    public function __construct(
        protected TimesheetEngineService $timesheetEngineService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $timesheets = Timesheet::where('user_id', $request->user()->id)
            ->latest('date')
            ->paginate(25);

        return response()->json([
            'success' => true,
            'data' => $timesheets,
        ]);
    }

    public function approve(Request $request, Timesheet $timesheet): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $approved = $this->timesheetEngineService->approveTimesheet(
            timesheet: $timesheet,
            reviewer: $request->user(),
            notes: $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $approved,
        ]);
    }
}
