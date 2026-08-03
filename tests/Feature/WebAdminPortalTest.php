<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WebAdminPortalTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--path' => 'database/migrations/tenant']);
        $this->artisan('db:seed', ['--class' => 'NairobiPilotSeeder']);

        $this->adminUser = User::where('email', 'nairobi.supervisor@bara.app')->firstOrFail();
    }

    public function test_dashboard_page_renders_with_inertia(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/dashboard');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard'));
    }

    public function test_customer_360_page_renders_with_inertia(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/customers-360');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->component('Customer360'));
    }

    public function test_exception_queue_page_renders_with_inertia(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/exception-queue');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->component('ExceptionQueue'));
    }

    public function test_route_manager_page_renders_with_inertia(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/route-manager');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->component('RouteManager'));
    }
}
