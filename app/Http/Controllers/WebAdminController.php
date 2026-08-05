<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WebAdminController extends Controller
{
    /**
     * Login view & Development Auto-Auth Fallback for local/staging UAT sessions
     */
    public function login(Request $request): RedirectResponse|Response
    {
        if (!Auth::check() && app()->environment('local', 'staging', 'testing')) {
            $user = User::where('email', 'admin@bara.com')->first()
                ?? User::where('email', 'nairobi.rep1@bara.app')->first()
                ?? User::first();

            if (!$user && Schema::hasTable('users')) {
                try {
                    $user = User::create([
                        'id' => (string) Str::uuid(),
                        'name' => 'Nairobi System Supervisor',
                        'email' => 'admin@bara.com',
                        'password' => bcrypt('password'),
                        'role' => 'Supervisor',
                    ]);
                } catch (\Throwable $e) {}
            }

            if ($user) {
                Auth::login($user);
                return redirect()->intended('/dashboard');
            }
        }

        if (Auth::check()) {
            return redirect('/dashboard');
        }

        return redirect('/dashboard');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/dashboard');
    }
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

    public function customer360(Request $request, ?string $id = null): Response
    {
        $targetId = $id ?? $request->query('customer_id') ?? $request->query('id');
        $customer = null;

        try {
            if (Schema::hasTable('customers')) {
                if ($targetId) {
                    $customer = Customer::where('id', $targetId)
                        ->orWhere('code', $targetId)
                        ->first();
                }
                if (!$customer) {
                    $customer = Customer::first();
                }
            }
        } catch (\Throwable $e) {}

        $customerPayload = [
            'name' => $customer?->name ?? 'Naivas Supermarket CBD Branch',
            'code' => $customer?->code ?? 'CUST-NAI-001',
            'tier' => $customer?->tier ?? 'Key Account Tier 1',
            'channel' => $customer?->channel ?? 'Key Account Tier 1',
            'credit_limit' => (float) ($customer?->credit_limit ?? 500000),
            'outstanding_balance' => (float) ($customer?->outstanding_balance ?? 125000),
            'creditLimit' => (float) ($customer?->credit_limit ?? 500000),
            'balance' => (float) ($customer?->outstanding_balance ?? 125000),
            'tax_pin' => $customer?->tax_pin ?? 'P0511223344A',
            'taxPin' => $customer?->tax_pin ?? 'P0511223344A',
            'address' => $customer?->address ?? 'Moi Avenue, Nairobi CBD',
        ];

        $customersList = [];
        try {
            if (Schema::hasTable('customers')) {
                $customersList = Customer::select('id', 'name', 'code')->get()->toArray();
            }
        } catch (\Throwable $e) {}

        $waterfallData = [
            ['level' => 1, 'name' => 'Base Price', 'price' => 'KES 150.00', 'ruleRef' => 'PR-BASE-SFJ', 'applied' => false],
            ['level' => 2, 'name' => 'Country Tier', 'price' => 'KES 148.00', 'ruleRef' => 'PR-CTRY-KE', 'applied' => false],
            ['level' => 3, 'name' => 'Structure Tier', 'price' => 'KES 145.00', 'ruleRef' => 'PR-STR-NRB', 'applied' => false],
            ['level' => 4, 'name' => 'Channel Tier', 'price' => 'KES 140.00', 'ruleRef' => 'PR-CHN-KA', 'applied' => true],
            ['level' => 5, 'name' => 'Volume Tier', 'price' => 'KES 135.00', 'ruleRef' => 'PR-VOL-TIER2', 'applied' => true],
            ['level' => 6, 'name' => 'Promo Discount', 'price' => 'KES 130.00', 'ruleRef' => 'PR-PROMO-NRB', 'applied' => true],
            ['level' => 7, 'name' => 'Customer Net', 'price' => 'KES 124.80', 'ruleRef' => 'PR-CUST-NAIVAS', 'applied' => true],
        ];

        $ordersData = [
            ['id' => 1, 'number' => 'SO-NAI-2026-001', 'date' => '2026-07-28', 'itemsCount' => 4, 'amount' => 45000, 'status' => 'delivered'],
            ['id' => 2, 'number' => 'SO-NAI-2026-002', 'date' => '2026-07-25', 'itemsCount' => 2, 'amount' => 18000, 'status' => 'delivered'],
        ];

        return Inertia::render('Customer360', [
            'customer' => $customerPayload,
            'customersList' => $customersList,
            'waterfall' => $waterfallData,
            'orders' => $ordersData,
            'mslMetrics' => ['availability' => 92, 'share_of_shelf' => 48],
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
