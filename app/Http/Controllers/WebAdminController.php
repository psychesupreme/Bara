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

    public function customer360(\Illuminate\Http\Request $request): Response
    {
        $customerId = $request->query('customer_id');
        $customer = null;
        if ($customerId && Schema::hasTable('customers')) {
            try {
                $customer = Customer::find($customerId);
            } catch (\Throwable $e) {}
        }
        return Inertia::render('Customer360', [
            'customer' => $customer,
        ]);
    }

    public function exceptionQueue(): Response
    {
        $exceptions = [];
        try {
            if (Schema::hasTable('activity_exceptions')) {
                $exceptions = \App\Models\ActivityException::with(['activity', 'user', 'reviewer'])
                    ->where('status', 'pending')
                    ->latest()
                    ->get()
                    ->map(function ($ex) {
                        return [
                            'id' => $ex->id,
                            'code' => 'EXP-' . strtoupper(substr($ex->exception_type ?? 'GEN', 0, 4)) . '-' . str_pad($ex->id, 3, '0', STR_PAD_LEFT),
                            'type' => str_contains($ex->exception_type ?? '', 'credit') ? 'credit' : 'geofence',
                            'rep' => $ex->user->name ?? 'Field Rep',
                            'customer' => $ex->activity->title ?? 'Nairobi Outlet',
                            'reason' => $ex->reason ?? 'Supervisory Override Request',
                            'description' => "Exception #{$ex->id}: {$ex->exception_type} — {$ex->reason}",
                            'severity' => str_contains($ex->exception_type ?? '', 'credit') ? 'high' : 'medium',
                            'timestamp' => $ex->created_at?->format('Y-m-d h:i A') ?? now()->format('Y-m-d h:i A'),
                            'processing' => false,
                        ];
                    })
                    ->toArray();
            }
        } catch (\Throwable $e) {
            $exceptions = [];
        }

        return Inertia::render('ExceptionQueue', [
            'exceptions' => $exceptions,
        ]);
    }

    public function routeManager(): Response
    {
        $routePlans = [];
        try {
            if (Schema::hasTable('route_plans')) {
                $routePlans = \App\Models\RoutePlan::with('user')->get();
            }
        } catch (\Throwable $e) {}
        return Inertia::render('RouteManager', [
            'routePlans' => $routePlans,
        ]);
    }
}
