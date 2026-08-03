<?php

use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\ActivityExceptionController;
use App\Http\Controllers\Api\V1\CheckInOutletController;
use App\Http\Controllers\Api\V1\CollectionController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\FieldLocationController;
use App\Http\Controllers\Api\V1\LeaveController;
use App\Http\Controllers\Api\V1\PayrollExpenseController;
use App\Http\Controllers\Api\V1\SfaExecutionController;
use App\Http\Controllers\Api\V1\SfaFoundationsController;
use App\Http\Controllers\Api\V1\SosController;
use App\Http\Controllers\Api\V1\SurveyController;
use App\Http\Controllers\Api\V1\SyncController;
use App\Http\Controllers\Api\V1\SystemUtilityController;
use App\Http\Controllers\Api\V1\TimesheetController;
use App\Http\Controllers\Api\V1\TrackingController;
use App\Http\Middleware\EnsureDeviceNotRevoked;
use App\Http\Middleware\InitializeTenancyByHeaderOrDomain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware([InitializeTenancyByHeaderOrDomain::class, EnsureDeviceNotRevoked::class])->prefix('v1')->group(function () {
    
    // Unauthenticated Health API
    Route::get('/health', function () {
        return response()->json([
            'status' => 'healthy',
            'system' => 'BARA Platform Core',
            'version' => '3.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    // Authenticated API Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        // Field Operations Check-in & GPS Telemetry Broadcast Route
        Route::post('/field/check-in', [CheckInOutletController::class, 'checkIn']);

        // Collaboration & System Utilities Routes (Phase 9: Module 4)
        Route::get('/utilities/calendar', [SystemUtilityController::class, 'unifiedCalendar']);
        Route::post('/utilities/notices', [SystemUtilityController::class, 'broadcastNotice']);
        Route::post('/utilities/notices/{notice}/acknowledge', [SystemUtilityController::class, 'acknowledgeNotice']);
        Route::post('/utilities/import/dry-run', [SystemUtilityController::class, 'dryRunImport']);
        Route::post('/utilities/backups/create', [SystemUtilityController::class, 'triggerBackup']);

        // Payroll & Expense Routes (Phase 8: Modules 13 & 14)
        Route::post('/payroll/pay-runs', [PayrollExpenseController::class, 'createPayRun']);
        Route::post('/payroll/pay-runs/{payRun}/review', [PayrollExpenseController::class, 'reviewPayRun']);
        Route::post('/payroll/pay-runs/{payRun}/approve', [PayrollExpenseController::class, 'approvePayRun']);
        Route::post('/expenses/submit', [PayrollExpenseController::class, 'submitExpense']);
        Route::post('/assets/{asset}/assign', [PayrollExpenseController::class, 'assignAsset']);

        // SFA Execution & Commercial Operations Routes (Phase 7: Modules 7, 8, 10, 11)
        Route::get('/sfa/customer-360/{customer}', [SfaExecutionController::class, 'customer360']);
        Route::post('/sfa/orders/create', [SfaExecutionController::class, 'createOrder']);
        Route::post('/sfa/orders/{order}/transition', [SfaExecutionController::class, 'transitionOrder']);
        Route::post('/sfa/merchandising/record', [SfaExecutionController::class, 'recordMerchandising']);

        // SFA Foundations Routes (Phase 6: Modules 5, 6, 9)
        Route::get('/sfa/access-preview/{user}', [SfaFoundationsController::class, 'previewAccess']);
        Route::post('/sfa/prospects/check-duplicates', [SfaFoundationsController::class, 'checkProspectDuplicates']);
        Route::post('/sfa/pricing/{product}/resolve', [SfaFoundationsController::class, 'resolvePrice']);

        // Field Locations Routes (Phase 2)
        Route::get('/field-locations', [FieldLocationController::class, 'index']);
        Route::post('/field-locations/{fieldLocation}/geofence', [FieldLocationController::class, 'updateGeofence']);

        // Activity Engine Routes (Phase 1 & Phase 3)
        Route::get('/activities', [ActivityController::class, 'index']);
        Route::post('/activities/{activity}/start', [ActivityController::class, 'start']);
        Route::post('/activities/{activity}/complete', [ActivityController::class, 'complete']);

        // Collections & Field Transactions Routes (Phase 5)
        Route::get('/collections', [CollectionController::class, 'index']);
        Route::post('/collections/capture', [CollectionController::class, 'capture']);
        Route::post('/collections/stk-push', [CollectionController::class, 'initiateStk']);
        Route::post('/collections/{collection}/reconcile', [CollectionController::class, 'reconcile']);
        Route::post('/collections/{collection}/reverse', [CollectionController::class, 'reverse']);
        Route::post('/collections/promise-to-pay', [CollectionController::class, 'recordPromise']);

        // Survey & Questionnaire Routes (Phase 3)
        Route::post('/forms/versions/{formVersion}/submit', [SurveyController::class, 'submit']);

        // Supervisory Exception Queue Routes (Phase 3)
        Route::get('/exceptions', [ActivityExceptionController::class, 'index']);
        Route::post('/exceptions/{exception}/approve', [ActivityExceptionController::class, 'approve']);
        Route::post('/exceptions/{exception}/reject', [ActivityExceptionController::class, 'reject']);

        // HR & Timesheet Routes (Phase 4)
        Route::get('/timesheets', [TimesheetController::class, 'index']);
        Route::post('/timesheets/{timesheet}/approve', [TimesheetController::class, 'approve']);

        // HR Leave Management Routes (Phase 4)
        Route::get('/leave/balances', [LeaveController::class, 'balances']);
        Route::post('/leave/submit', [LeaveController::class, 'submit']);
        Route::post('/leave/{leaveRequest}/approve', [LeaveController::class, 'approve']);

        // Tracking & Privacy Boundary Routes (Phase 2)
        Route::post('/tracking/sessions/start', [TrackingController::class, 'startSession']);
        Route::post('/tracking/sessions/{session}/stop', [TrackingController::class, 'stopSession']);
        Route::post('/tracking/sessions/{session}/stream', [TrackingController::class, 'ingestStream']);

        // Device Lifecycle Routes (Phase 2)
        Route::post('/devices/register', [DeviceController::class, 'register']);
        Route::post('/devices/{device}/approve', [DeviceController::class, 'approve']);
        Route::post('/devices/{device}/revoke', [DeviceController::class, 'revoke']);

        // Emergency SOS Routes (Phase 2)
        Route::post('/sos/trigger', [SosController::class, 'trigger']);
        Route::post('/sos/{sos}/respond', [SosController::class, 'respond']);
        Route::post('/sos/{sos}/resolve', [SosController::class, 'resolve']);

        // Data Sync Engine Routes (Phase 1)
        Route::post('/sync/push-logs', [SyncController::class, 'pushLogs']);
        Route::get('/sync/pull-deltas', [SyncController::class, 'pullDeltas']);
    });
});
