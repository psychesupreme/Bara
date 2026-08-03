<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compensation_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('pay_rate_type')->default('monthly')->index(); // monthly, daily, hourly
            $table->decimal('base_rate', 12, 2);
            $table->string('currency', 3)->default('KES');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('commission_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->unique();
            $table->decimal('commission_percentage', 5, 2)->default(0.00);
            $table->decimal('min_sales_threshold', 12, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pay_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('period_start')->index();
            $table->date('period_end')->index();
            $table->string('status')->default('draft')->index(); // draft, calculated, reviewed, approved, disbursed
            
            $table->foreignUuid('initiated_by')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->decimal('total_gross_pay', 14, 2)->default(0.00);
            $table->decimal('total_statutory_deductions', 14, 2)->default(0.00);
            $table->decimal('total_reimbursements', 14, 2)->default(0.00);
            $table->decimal('total_net_pay', 14, 2)->default(0.00);
            $table->string('currency', 3)->default('KES');
            
            $table->timestamps();
        });

        Schema::create('pay_run_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pay_run_id')->constrained('pay_runs')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            
            $table->decimal('base_pay', 12, 2)->default(0.00);
            $table->decimal('overtime_pay', 12, 2)->default(0.00);
            $table->decimal('commission_pay', 12, 2)->default(0.00);
            $table->decimal('gross_pay', 12, 2)->default(0.00);
            
            $table->decimal('paye_tax', 12, 2)->default(0.00);
            $table->decimal('nssf_deduction', 12, 2)->default(0.00);
            $table->decimal('shif_deduction', 12, 2)->default(0.00);
            
            $table->decimal('expense_reimbursement', 12, 2)->default(0.00);
            $table->decimal('net_pay', 12, 2)->default(0.00);
            
            $table->string('payslip_number')->unique();
            $table->timestamps();
        });

        Schema::create('expense_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('category')->unique(); // fuel, meals, lodging, transport
            $table->decimal('max_claim_amount', 10, 2);
            $table->decimal('receipt_required_above', 10, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('expense_claims', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->unsignedBigInteger('sequence')->default(1)->index();
            
            $table->string('claim_number')->unique();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->foreignUuid('pay_run_id')->nullable()->constrained('pay_runs')->nullOnDelete();
            
            $table->string('category')->index();
            $table->string('merchant');
            $table->decimal('amount', 10, 2);
            $table->string('receipt_url')->nullable();
            
            $table->string('status')->default('pending')->index(); // pending, approved, reimbursed, rejected
            $table->boolean('is_offline_captured')->default(false);
            $table->timestamps();
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('asset_code')->unique();
            $table->string('name');
            $table->string('asset_type')->index(); // vehicle, mobile_device, posm, sample_kit
            $table->string('serial_number')->nullable();
            $table->string('status')->default('in_inventory')->index(); // in_inventory, issued, in_use, maintenance, returned, decommissioned
            $table->timestamps();
        });

        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignUuid('assigned_to_user_id')->constrained('users')->cascadeOnDelete();
            
            $table->timestamp('assigned_at')->index();
            $table->timestamp('returned_at')->nullable();
            $table->string('acceptance_signature')->nullable();
            $table->string('status')->default('active')->index(); // active, returned
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('expense_claims');
        Schema::dropIfExists('expense_policies');
        Schema::dropIfExists('pay_run_items');
        Schema::dropIfExists('pay_runs');
        Schema::dropIfExists('commission_rules');
        Schema::dropIfExists('compensation_profiles');
    }
};
