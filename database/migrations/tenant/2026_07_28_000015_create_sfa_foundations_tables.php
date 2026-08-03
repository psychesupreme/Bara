<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_nodes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('level_name')->index(); // Territory, Zone, Region, Channel, Branch
            $table->foreignUuid('parent_id')->nullable()->constrained('commercial_nodes')->nullOnDelete();
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('commercial_node_closure', function (Blueprint $table) {
            $table->foreignUuid('ancestor_id')->constrained('commercial_nodes')->cascadeOnDelete();
            $table->foreignUuid('descendant_id')->constrained('commercial_nodes')->cascadeOnDelete();
            $table->integer('depth')->default(0);

            $table->primary(['ancestor_id', 'descendant_id']);
        });

        Schema::create('user_commercial_scopes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('commercial_node_id')->constrained('commercial_nodes')->cascadeOnDelete();
            $table->boolean('include_descendants')->default(true);
            $table->date('effective_from')->index();
            $table->date('effective_to')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->unsignedBigInteger('sequence')->default(1)->index();
            
            $table->string('name');
            $table->string('code')->unique();
            $table->string('customer_type')->default('outlet')->index(); // national_account, outlet
            $table->foreignUuid('parent_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignUuid('commercial_node_id')->nullable()->constrained('commercial_nodes')->nullOnDelete();
            
            $table->string('tax_number')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('county')->default('Nairobi')->index();
            
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });

        Schema::create('customer_outlet_extensions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('payment_terms')->default('cash')->index(); // cash, credit_7, credit_14, credit_30
            $table->decimal('credit_limit', 12, 2)->default(0.00);
            $table->string('tax_group')->default('standard')->index();
            $table->string('price_list_code')->default('base')->index();
            $table->string('channel')->default('retail')->index(); // retail, wholesale, HORECA
            $table->string('segment')->default('tier_3')->index();
            $table->timestamps();
        });

        Schema::create('route_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->unique();
            $table->foreignUuid('sales_rep_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('commercial_node_id')->nullable()->constrained('commercial_nodes')->nullOnDelete();
            $table->json('visit_days'); // ["Mon", "Thu"]
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('route_stops', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('route_plan_id')->constrained('route_plans')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->integer('stop_order')->default(1);
            $table->json('guided_call_steps')->nullable();
            $table->timestamps();

            $table->unique(['route_plan_id', 'customer_id']);
        });

        Schema::create('commercial_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->index();
            $table->string('package_type')->default('unit');
            $table->integer('unit_size')->default(1);
            $table->integer('moq')->default(1);
            $table->boolean('is_returnable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('assortments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('channel')->index();
            $table->foreignUuid('commercial_node_id')->nullable()->constrained('commercial_nodes')->nullOnDelete();
            $table->json('items'); // [{product_id, is_mandatory, is_excluded}]
            $table->timestamps();
        });

        Schema::create('price_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->unique();
            $table->foreignUuid('product_id')->constrained('commercial_products')->cascadeOnDelete();
            $table->string('level_type')->index(); // base, country, structure, channel, customer_group, outlet, deal
            $table->string('level_id')->nullable()->index();
            $table->decimal('unit_price', 10, 2);
            $table->string('currency', 3)->default('KES');
            $table->integer('priority')->default(1)->index();
            $table->timestamp('effective_from')->index();
            $table->timestamp('effective_to')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_rules');
        Schema::dropIfExists('assortments');
        Schema::dropIfExists('commercial_products');
        Schema::dropIfExists('route_stops');
        Schema::dropIfExists('route_plans');
        Schema::dropIfExists('customer_outlet_extensions');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('user_commercial_scopes');
        Schema::dropIfExists('commercial_node_closure');
        Schema::dropIfExists('commercial_nodes');
    }
};
