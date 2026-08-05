<?php

use App\Http\Controllers\Api\V1\ActivityExceptionController;
use App\Http\Controllers\WebAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/dashboard');
});

// Named Authentication Routes
Route::get('/login', [WebAdminController::class, 'login'])->name('login');
Route::post('/login', [WebAdminController::class, 'authenticate']);
Route::post('/logout', [WebAdminController::class, 'logout'])->name('logout');

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/dashboard', [WebAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/customers-360', [WebAdminController::class, 'customer360'])->name('customer360');
    Route::get('/exception-queue', [WebAdminController::class, 'exceptionQueue'])->name('exceptionQueue');
    Route::get('/supervisory-queue', [WebAdminController::class, 'exceptionQueue'])->name('supervisoryQueue');
    Route::get('/route-manager', [WebAdminController::class, 'routeManager'])->name('routeManager');

    // Supervisory Exception Approval & Rejection Endpoints
    Route::post('/exceptions/{id}/approve', [ActivityExceptionController::class, 'approve']);
    Route::post('/exceptions/{id}/reject', [ActivityExceptionController::class, 'reject']);
});
