<?php

use App\Http\Controllers\WebAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/dashboard', [WebAdminController::class, 'dashboard'])->name('dashboard');
Route::get('/customers-360', [WebAdminController::class, 'customer360'])->name('customer360');
Route::get('/exception-queue', [WebAdminController::class, 'exceptionQueue'])->name('exceptionQueue');
Route::get('/route-manager', [WebAdminController::class, 'routeManager'])->name('routeManager');
