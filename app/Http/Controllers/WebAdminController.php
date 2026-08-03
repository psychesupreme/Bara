<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class WebAdminController extends Controller
{
    public function dashboard(): Response
    {
        $outlets = [];

        try {
            if (class_exists(Customer::class) && Schema::hasTable('customers')) {
                $outlets = Customer::select('id', 'name', 'code', 'address', 'county', 'latitude', 'longitude', 'is_active')
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->get();
            }
        } catch (\Throwable $e) {
            $outlets = [];
        }

        return Inertia::render('Dashboard', [
            'outlets' => $outlets,
        ]);
    }

    public function customer360(): Response
    {
        return Inertia::render('Customer360');
    }

    public function exceptionQueue(): Response
    {
        return Inertia::render('ExceptionQueue');
    }

    public function routeManager(): Response
    {
        return Inertia::render('RouteManager');
    }
}
