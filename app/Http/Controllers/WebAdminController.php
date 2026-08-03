<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class WebAdminController extends Controller
{
    public function dashboard(): Response
    {
        return Inertia::render('Dashboard');
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
