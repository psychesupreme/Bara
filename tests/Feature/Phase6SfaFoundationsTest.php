<?php

namespace Tests\Feature;

use App\Models\CommercialNode;
use App\Models\CommercialProduct;
use App\Models\Customer;
use App\Models\CustomerOutletExtension;
use App\Models\PriceRule;
use App\Models\User;
use App\Models\UserCommercialScope;
use App\Services\CommercialScopeResolver;
use App\Services\PriceWaterfallEngine;
use App\Services\RoutePlanningService;
use App\Services\VerificationResultEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase6SfaFoundationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--path' => 'database/migrations/tenant']);
    }

    public function test_closure_table_computes_ancestor_descendant_relationships(): void
    {
        $region = CommercialNode::create([
            'id' => (string) Str::uuid(),
            'name' => 'Nairobi Region',
            'level_name' => 'Region',
            'code' => 'REG-NRB',
        ]);

        $zone = CommercialNode::create([
            'id' => (string) Str::uuid(),
            'name' => 'Westlands Zone',
            'level_name' => 'Zone',
            'parent_id' => $region->id,
            'code' => 'ZON-WST',
        ]);

        $territory = CommercialNode::create([
            'id' => (string) Str::uuid(),
            'name' => 'Parklands Territory',
            'level_name' => 'Territory',
            'parent_id' => $zone->id,
            'code' => 'TER-PRK',
        ]);

        $resolver = new CommercialScopeResolver();
        $resolver->rebuildClosureTable();

        // Ancestor count for territory should be 3 (Region, Zone, Territory self)
        $ancestorCount = DB::table('commercial_node_closure')
            ->where('descendant_id', $territory->id)
            ->count();

        $this->assertEquals(3, $ancestorCount);
    }

    public function test_commercial_scope_resolver_filters_unauthorized_outlets(): void
    {
        $region = CommercialNode::create([
            'id' => (string) Str::uuid(),
            'name' => 'Coast Region',
            'level_name' => 'Region',
            'code' => 'REG-CST',
        ]);

        $zone = CommercialNode::create([
            'id' => (string) Str::uuid(),
            'name' => 'Mombasa Zone',
            'level_name' => 'Zone',
            'parent_id' => $region->id,
            'code' => 'ZON-MBA',
        ]);

        $rep = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Sales Rep 1',
            'email' => 'rep1@bara.app',
            'password' => bcrypt('password'),
            'role' => 'rep',
        ]);

        UserCommercialScope::create([
            'id' => (string) Str::uuid(),
            'user_id' => $rep->id,
            'commercial_node_id' => $zone->id,
            'include_descendants' => true,
            'effective_from' => now()->subDay(),
        ]);

        $resolver = new CommercialScopeResolver();
        $resolver->rebuildClosureTable();

        $permittedIds = $resolver->getPermittedNodeIds($rep);

        $this->assertContains($zone->id, $permittedIds);
        $this->assertNotContains($region->id, $permittedIds); // Parent is not in scope
    }

    public function test_access_preview_returns_permitted_nodes_and_outlets(): void
    {
        $node = CommercialNode::create([
            'id' => (string) Str::uuid(),
            'name' => 'Central Branch',
            'level_name' => 'Branch',
            'code' => 'BR-CTL',
        ]);

        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Manager 1',
            'email' => 'mgr1@bara.app',
            'password' => bcrypt('password'),
        ]);

        UserCommercialScope::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'commercial_node_id' => $node->id,
            'include_descendants' => true,
            'effective_from' => now()->subDay(),
        ]);

        $resolver = new CommercialScopeResolver();
        $resolver->rebuildClosureTable();

        $preview = $resolver->previewUserAccess($user);

        $this->assertEquals(1, $preview['permitted_node_count']);
        $this->assertEquals('Central Branch', $preview['permitted_nodes']->first()->name);
    }

    public function test_duplicate_prospect_detection_flags_matching_tax_or_coordinates(): void
    {
        Customer::create([
            'id' => (string) Str::uuid(),
            'name' => 'Kenyatta Avenue Mart',
            'code' => 'OUT-101',
            'tax_number' => 'P0511223344A',
            'phone' => '0711223344',
            'latitude' => -1.2833300,
            'longitude' => 36.8166700,
        ]);

        $service = new RoutePlanningService(new VerificationResultEngine());
        $duplicates = $service->checkForDuplicates([
            'name' => 'New Prospect Shop',
            'tax_number' => 'P0511223344A', // Matching tax number
            'latitude' => -1.2833310,       // ~1 meter away
            'longitude' => 36.8166710,
        ]);

        $this->assertNotEmpty($duplicates);
        $this->assertEquals('tax_number', $duplicates[0]['field']);
    }

    public function test_waterfall_price_resolution_engine_applies_strict_precedence(): void
    {
        $product = CommercialProduct::create([
            'id' => (string) Str::uuid(),
            'name' => 'Premium Soft Drink 500ml',
            'sku' => 'PSD-500',
        ]);

        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'name' => 'Supermarket Chain A',
            'code' => 'CUST-001',
        ]);

        CustomerOutletExtension::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'channel' => 'retail',
            'segment' => 'tier_1',
        ]);

        // Base price rule ($100)
        PriceRule::create([
            'id' => (string) Str::uuid(),
            'name' => 'Base Price',
            'code' => 'PR-BASE',
            'product_id' => $product->id,
            'level_type' => 'base',
            'unit_price' => 100.00,
            'priority' => 1,
            'effective_from' => now()->subDays(10),
        ]);

        // Channel price rule ($90)
        PriceRule::create([
            'id' => (string) Str::uuid(),
            'name' => 'Retail Channel Price',
            'code' => 'PR-RETAIL',
            'product_id' => $product->id,
            'level_type' => 'channel',
            'level_id' => 'retail',
            'unit_price' => 90.00,
            'priority' => 4,
            'effective_from' => now()->subDays(10),
        ]);

        // Outlet specific price rule ($85 - Higher Precedence)
        PriceRule::create([
            'id' => (string) Str::uuid(),
            'name' => 'Outlet Specific Special',
            'code' => 'PR-OUTLET-01',
            'product_id' => $product->id,
            'level_type' => 'outlet',
            'level_id' => $customer->id,
            'unit_price' => 85.00,
            'priority' => 6,
            'effective_from' => now()->subDays(10),
        ]);

        $engine = new PriceWaterfallEngine();
        $resolved = $engine->resolvePrice($product, $customer);

        // Should resolve to Outlet level price ($85) overriding Channel ($90) and Base ($100)
        $this->assertEquals(85.00, $resolved['unit_price']);
        $this->assertEquals('PR-OUTLET-01', $resolved['price_rule_code']);
        $this->assertEquals('outlet', $resolved['level_type']);
    }
}
