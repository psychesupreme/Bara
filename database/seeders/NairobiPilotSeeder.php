<?php

namespace Database\Seeders;

use App\Models\CommercialNode;
use App\Models\CommercialProduct;
use App\Models\CompensationProfile;
use App\Models\Customer;
use App\Models\CustomerOutletExtension;
use App\Models\ExpensePolicy;
use App\Models\PriceRule;
use App\Models\RoutePlan;
use App\Models\RouteStop;
use App\Models\User;
use App\Models\UserCommercialScope;
use App\Services\CommercialScopeResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NairobiPilotSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Commercial Structure (Module 5)
        $region = CommercialNode::create([
            'id' => (string) Str::uuid(),
            'name' => 'Nairobi Region',
            'level_name' => 'Region',
            'code' => 'REG-NRB',
            'is_active' => true,
        ]);

        $centralZone = CommercialNode::create([
            'id' => (string) Str::uuid(),
            'name' => 'Nairobi Central Zone',
            'level_name' => 'Zone',
            'parent_id' => $region->id,
            'code' => 'ZON-NRB-CTL',
            'is_active' => true,
        ]);

        $westZone = CommercialNode::create([
            'id' => (string) Str::uuid(),
            'name' => 'Nairobi West Zone',
            'level_name' => 'Zone',
            'parent_id' => $region->id,
            'code' => 'ZON-NRB-WST',
            'is_active' => true,
        ]);

        $cbdTerritory = CommercialNode::create([
            'id' => (string) Str::uuid(),
            'name' => 'CBD Territory',
            'level_name' => 'Territory',
            'parent_id' => $centralZone->id,
            'code' => 'TER-CBD',
            'is_active' => true,
        ]);

        $westlandsTerritory = CommercialNode::create([
            'id' => (string) Str::uuid(),
            'name' => 'Westlands Territory',
            'level_name' => 'Territory',
            'parent_id' => $westZone->id,
            'code' => 'TER-WST',
            'is_active' => true,
        ]);

        $kilimaniTerritory = CommercialNode::create([
            'id' => (string) Str::uuid(),
            'name' => 'Kilimani/Yaya Territory',
            'level_name' => 'Territory',
            'parent_id' => $westZone->id,
            'code' => 'TER-KLM',
            'is_active' => true,
        ]);

        // Rebuild closure table for O(1) tree lookups
        $resolver = new CommercialScopeResolver();
        $resolver->rebuildClosureTable();

        // 2. Commercial Product Portfolio & Pricing Rules (Module 9)
        $p1 = CommercialProduct::create([
            'id' => (string) Str::uuid(),
            'name' => 'Safari Fresh Juice 1L',
            'sku' => 'SFJ-1000',
            'barcode' => '6001234567890',
            'package_type' => 'carton',
            'unit_size' => 12,
            'moq' => 1,
            'is_returnable' => false,
            'is_active' => true,
        ]);

        $p2 = CommercialProduct::create([
            'id' => (string) Str::uuid(),
            'name' => 'Safari Spring Water 500ml',
            'sku' => 'SSW-500',
            'barcode' => '6001234567891',
            'package_type' => 'pack',
            'unit_size' => 24,
            'moq' => 2,
            'is_returnable' => false,
            'is_active' => true,
        ]);

        $p3 = CommercialProduct::create([
            'id' => (string) Str::uuid(),
            'name' => 'Safari Energy Drink 250ml',
            'sku' => 'SED-250',
            'barcode' => '6001234567892',
            'package_type' => 'case',
            'unit_size' => 24,
            'moq' => 1,
            'is_returnable' => false,
            'is_active' => true,
        ]);

        PriceRule::create([
            'id' => (string) Str::uuid(),
            'name' => 'Base Price - Juice 1L',
            'code' => 'PR-BASE-SFJ',
            'product_id' => $p1->id,
            'level_type' => 'base',
            'unit_price' => 150.00,
            'currency' => 'KES',
            'priority' => 1,
            'effective_from' => now()->subYear(),
        ]);

        PriceRule::create([
            'id' => (string) Str::uuid(),
            'name' => 'Base Price - Water 500ml',
            'code' => 'PR-BASE-SSW',
            'product_id' => $p2->id,
            'level_type' => 'base',
            'unit_price' => 40.00,
            'currency' => 'KES',
            'priority' => 1,
            'effective_from' => now()->subYear(),
        ]);

        PriceRule::create([
            'id' => (string) Str::uuid(),
            'name' => 'Base Price - Energy 250ml',
            'code' => 'PR-BASE-SED',
            'product_id' => $p3->id,
            'level_type' => 'base',
            'unit_price' => 100.00,
            'currency' => 'KES',
            'priority' => 1,
            'effective_from' => now()->subYear(),
        ]);

        // 3. User Accounts & Commercial Scopes (HR)
        $supervisor = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Nairobi Field Supervisor',
            'email' => 'nairobi.supervisor@bara.app',
            'password' => bcrypt('Password123!'),
            'role' => 'supervisor',
        ]);

        $rep1 = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Central Field Rep (CBD)',
            'email' => 'nairobi.rep1@bara.app',
            'password' => bcrypt('Password123!'),
            'role' => 'rep',
        ]);

        $rep2 = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Westlands Field Rep',
            'email' => 'nairobi.rep2@bara.app',
            'password' => bcrypt('Password123!'),
            'role' => 'rep',
        ]);

        $collector = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Nairobi Collection Officer',
            'email' => 'nairobi.collector@bara.app',
            'password' => bcrypt('Password123!'),
            'role' => 'collector',
        ]);

        $finance = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Nairobi Finance Reviewer',
            'email' => 'nairobi.finance@bara.app',
            'password' => bcrypt('Password123!'),
            'role' => 'finance',
        ]);

        UserCommercialScope::create([
            'id' => (string) Str::uuid(),
            'user_id' => $supervisor->id,
            'commercial_node_id' => $region->id,
            'include_descendants' => true,
            'effective_from' => now()->subYear(),
        ]);

        UserCommercialScope::create([
            'id' => (string) Str::uuid(),
            'user_id' => $rep1->id,
            'commercial_node_id' => $centralZone->id,
            'include_descendants' => true,
            'effective_from' => now()->subYear(),
        ]);

        UserCommercialScope::create([
            'id' => (string) Str::uuid(),
            'user_id' => $rep2->id,
            'commercial_node_id' => $westZone->id,
            'include_descendants' => true,
            'effective_from' => now()->subYear(),
        ]);

        // Compensation Profiles
        CompensationProfile::create([
            'id' => (string) Str::uuid(),
            'user_id' => $rep1->id,
            'pay_rate_type' => 'monthly',
            'base_rate' => 45000.00,
        ]);

        CompensationProfile::create([
            'id' => (string) Str::uuid(),
            'user_id' => $rep2->id,
            'pay_rate_type' => 'monthly',
            'base_rate' => 45000.00,
        ]);

        // 4. Customers & Outlets across Nairobi County (Module 6)
        $c1 = Customer::create([
            'id' => (string) Str::uuid(),
            'name' => 'Naivas Supermarket CBD Branch',
            'code' => 'CUST-NAI-001',
            'customer_type' => 'national_account',
            'commercial_node_id' => $cbdTerritory->id,
            'tax_number' => 'P0511223344A',
            'phone' => '0711000111',
            'email' => 'cbd@naivas.co.ke',
            'address' => 'Moi Avenue, Nairobi CBD',
            'county' => 'Nairobi',
            'latitude' => -1.2833300,
            'longitude' => 36.8166700,
            'is_active' => true,
        ]);

        CustomerOutletExtension::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $c1->id,
            'payment_terms' => 'credit_30',
            'credit_limit' => 500000.00,
            'tax_group' => 'standard',
            'price_list_code' => 'base',
            'channel' => 'key_account',
            'segment' => 'tier_1',
        ]);

        $c2 = Customer::create([
            'id' => (string) Str::uuid(),
            'name' => 'Sarit Center Mart',
            'code' => 'CUST-NAI-002',
            'customer_type' => 'outlet',
            'commercial_node_id' => $westlandsTerritory->id,
            'tax_number' => 'P0511998877B',
            'phone' => '0722000222',
            'email' => 'orders@saritmart.co.ke',
            'address' => 'Karuna Road, Westlands',
            'county' => 'Nairobi',
            'latitude' => -1.2612000,
            'longitude' => 36.8041000,
            'is_active' => true,
        ]);

        CustomerOutletExtension::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $c2->id,
            'payment_terms' => 'credit_14',
            'credit_limit' => 150000.00,
            'tax_group' => 'standard',
            'price_list_code' => 'base',
            'channel' => 'retail',
            'segment' => 'tier_1',
        ]);

        $c3 = Customer::create([
            'id' => (string) Str::uuid(),
            'name' => 'Yaya Center MiniMart',
            'code' => 'CUST-NAI-003',
            'customer_type' => 'outlet',
            'commercial_node_id' => $kilimaniTerritory->id,
            'tax_number' => 'P0511554433C',
            'phone' => '0733000333',
            'email' => 'info@yayamart.co.ke',
            'address' => 'Argwings Kodhek Road, Kilimani',
            'county' => 'Nairobi',
            'latitude' => -1.2917000,
            'longitude' => 36.7865000,
            'is_active' => true,
        ]);

        CustomerOutletExtension::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $c3->id,
            'payment_terms' => 'cash',
            'credit_limit' => 100000.00,
            'tax_group' => 'standard',
            'price_list_code' => 'base',
            'channel' => 'retail',
            'segment' => 'tier_2',
        ]);

        $c4 = Customer::create([
            'id' => (string) Str::uuid(),
            'name' => 'Kasarani Live Test Store',
            'code' => 'CUST-NAI-004',
            'customer_type' => 'outlet',
            'commercial_node_id' => $cbdTerritory->id,
            'tax_number' => 'P0511332211D',
            'phone' => '0744000444',
            'email' => 'store@kasaranilive.co.ke',
            'address' => 'Thika Road, Kasarani, Nairobi',
            'county' => 'Nairobi',
            'latitude' => -1.2002000,
            'longitude' => 36.8344000,
            'is_active' => true,
        ]);

        CustomerOutletExtension::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $c4->id,
            'payment_terms' => 'cash',
            'credit_limit' => 50000.00,
            'tax_group' => 'standard',
            'price_list_code' => 'base',
            'channel' => 'retail',
            'segment' => 'tier_3',
        ]);

        // 5. Route Plans & Call Cycles (Module 6)
        $route1 = RoutePlan::create([
            'id' => (string) Str::uuid(),
            'name' => 'Nairobi Central Mon/Wed/Fri Route',
            'code' => 'RTE-NRB-CTL-01',
            'sales_rep_id' => $rep1->id,
            'commercial_node_id' => $centralZone->id,
            'visit_days' => ['Mon', 'Wed', 'Fri'],
            'is_active' => true,
        ]);

        RouteStop::create([
            'id' => (string) Str::uuid(),
            'route_plan_id' => $route1->id,
            'customer_id' => $c4->id,
            'stop_order' => 1,
            'guided_call_steps' => ['check_in', 'stock_check', 'order_entry', 'promotions', 'merchandising', 'collection', 'check_out'],
        ]);

        RouteStop::create([
            'id' => (string) Str::uuid(),
            'route_plan_id' => $route1->id,
            'customer_id' => $c1->id,
            'stop_order' => 2,
            'guided_call_steps' => ['check_in', 'stock_check', 'order_entry', 'promotions', 'merchandising', 'collection', 'check_out'],
        ]);

        $route2 = RoutePlan::create([
            'id' => (string) Str::uuid(),
            'name' => 'Nairobi West Tue/Thu Route',
            'code' => 'RTE-NRB-WST-01',
            'sales_rep_id' => $rep2->id,
            'commercial_node_id' => $westZone->id,
            'visit_days' => ['Tue', 'Thu'],
            'is_active' => true,
        ]);

        RouteStop::create([
            'id' => (string) Str::uuid(),
            'route_plan_id' => $route2->id,
            'customer_id' => $c2->id,
            'stop_order' => 1,
            'guided_call_steps' => ['check_in', 'stock_check', 'order_entry', 'promotions', 'merchandising', 'collection', 'check_out'],
        ]);

        RouteStop::create([
            'id' => (string) Str::uuid(),
            'route_plan_id' => $route2->id,
            'customer_id' => $c3->id,
            'stop_order' => 2,
            'guided_call_steps' => ['check_in', 'stock_check', 'order_entry', 'promotions', 'merchandising', 'collection', 'check_out'],
        ]);

        // Expense Policies
        ExpensePolicy::create(['id' => (string) Str::uuid(), 'category' => 'fuel', 'max_claim_amount' => 5000.00, 'receipt_required_above' => 1000.00]);
        ExpensePolicy::create(['id' => (string) Str::uuid(), 'category' => 'meals', 'max_claim_amount' => 2000.00, 'receipt_required_above' => 500.00]);
        ExpensePolicy::create(['id' => (string) Str::uuid(), 'category' => 'lodging', 'max_claim_amount' => 10000.00, 'receipt_required_above' => 2000.00]);
    }
}
