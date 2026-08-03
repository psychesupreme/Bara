<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->unsignedBigInteger('sequence')->default(1)->index();
            
            $table->string('order_number')->unique();
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignUuid('sales_rep_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            
            $table->decimal('subtotal_amount', 12, 2)->default(0.00);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->string('currency', 3)->default('KES')->index();
            
            $table->string('status')->default('draft')->index(); // draft, pending_approval, approved, allocated, dispatched, delivered, cancelled
            
            $table->string('etims_receipt_number')->nullable()->index();
            $table->text('etims_qr_code')->nullable();
            $table->string('etims_signature')->nullable();
            
            $table->boolean('is_offline_captured')->default(false);
            $table->timestamps();
        });

        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('commercial_products')->cascadeOnDelete();
            
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->string('price_rule_code')->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('line_total', 12, 2);
            
            $table->timestamps();
        });

        Schema::create('order_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('event_type')->index();
            $table->text('notes')->nullable();
            
            $table->timestamp('created_at')->index();
        });

        Schema::create('promotions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('promo_type')->index(); // percentage_discount, buy_x_get_y, bundle_deal
            
            $table->decimal('discount_percentage', 5, 2)->default(0.00);
            $table->foreignUuid('buy_product_id')->nullable()->constrained('commercial_products')->nullOnDelete();
            $table->integer('buy_quantity')->default(0);
            $table->foreignUuid('get_product_id')->nullable()->constrained('commercial_products')->nullOnDelete();
            $table->integer('get_quantity')->default(0);
            
            $table->decimal('budget_cap', 12, 2)->default(0.00);
            $table->decimal('spent_amount', 12, 2)->default(0.00);
            
            $table->date('effective_from')->index();
            $table->date('effective_to')->nullable()->index();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });

        Schema::create('promotion_claims', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignUuid('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->decimal('claimed_amount', 12, 2);
            $table->string('evidence_uuid')->nullable();
            $table->string('status')->default('pending')->index(); // pending, approved, rejected
            $table->timestamps();
        });

        Schema::create('merch_observations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->unsignedBigInteger('sequence')->default(1)->index();
            
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignUuid('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            
            $table->decimal('msl_compliance_score', 5, 2)->default(0.00);
            $table->decimal('share_of_shelf_percentage', 5, 2)->default(0.00);
            $table->string('evidence_photo_url')->nullable();
            $table->string('posm_condition')->default('good');
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });

        Schema::create('merch_observation_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('merch_observation_id')->constrained('merch_observations')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('commercial_products')->cascadeOnDelete();
            
            $table->boolean('is_in_stock')->default(true);
            $table->integer('facing_count')->default(0);
            $table->decimal('shelf_price', 10, 2)->default(0.00);
            
            $table->timestamps();
        });

        Schema::create('competitor_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('merch_observation_id')->constrained('merch_observations')->cascadeOnDelete();
            
            $table->string('competitor_name');
            $table->string('brand_name');
            $table->decimal('price', 10, 2);
            $table->text('promo_details')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitor_products');
        Schema::dropIfExists('merch_observation_lines');
        Schema::dropIfExists('merch_observations');
        Schema::dropIfExists('promotion_claims');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('order_events');
        Schema::dropIfExists('sales_order_lines');
        Schema::dropIfExists('sales_orders');
    }
};
