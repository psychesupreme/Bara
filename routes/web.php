<?php

use App\Http\Controllers\Api\V1\ActivityExceptionController;
use App\Http\Controllers\WebAdminController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::get('/', function () {
        return redirect('/dashboard');
    });

    Route::get('/dashboard', [WebAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/customers-360', [WebAdminController::class, 'customer360'])->name('customer360');
    Route::get('/exception-queue', [WebAdminController::class, 'exceptionQueue'])->name('exceptionQueue');
    Route::get('/supervisory-queue', [WebAdminController::class, 'exceptionQueue'])->name('supervisoryQueue');
    Route::get('/route-manager', [WebAdminController::class, 'routeManager'])->name('routeManager');

    // Supervisory Exception Approval & Rejection Endpoints
    Route::post('/api/v1/exceptions/{exception}/approve', [ActivityExceptionController::class, 'approve']);
    Route::post('/api/v1/exceptions/{exception}/reject', [ActivityExceptionController::class, 'reject']);
    Route::post('/exceptions/{exception}/approve', [ActivityExceptionController::class, 'approve']);
    Route::post('/exceptions/{exception}/reject', [ActivityExceptionController::class, 'reject']);
});
