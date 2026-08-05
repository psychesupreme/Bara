<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\IdentificationMiddleware;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancyByHeaderOrDomain extends IdentificationMiddleware
{
    /** @var Tenancy */
    protected $tenancy;

    public function __construct(Tenancy $tenancy)
    {
        $this->tenancy = $tenancy;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->tenancy->initialized) {
            return $next($request);
        }

        try {
            // 1. Resolve by X-Tenant header or tenant query parameter
            $tenantId = $request->header('X-Tenant') ?? $request->query('tenant');

            if ($tenantId) {
                $tenant = config('tenancy.tenant_model')::find($tenantId);
                if ($tenant) {
                    $this->tenancy->initialize($tenant);
                    return $next($request);
                }
            }

            // 2. Resolve by Subdomain or Host
            $host = $request->getHost();
            $domain = config('tenancy.domain_model')::where('domain', $host)->first();

            if ($domain && $domain->tenant) {
                $this->tenancy->initialize($domain->tenant);
                return $next($request);
            }

            // 3. Fallback for On-Premise Single-Tenant deployments
            if (config('app.single_tenant_mode', false)) {
                $defaultTenant = config('tenancy.tenant_model')::first();
                if ($defaultTenant) {
                    $this->tenancy->initialize($defaultTenant);
                }
            }

            if (!$this->tenancy->initialized && !config('app.single_tenant_mode', false) && !app()->environment('testing')) {
                abort(403, 'Tenant scope required. Provide X-Tenant header or configure domain.');
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Tenant resolution failed: ' . $e->getMessage());
            abort(403, 'Unable to resolve tenant scope. Access denied.');
        }

        return $next($request);
    }
}
