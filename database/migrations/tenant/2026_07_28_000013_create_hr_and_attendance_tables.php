<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('shift_type')->default('standard')->index(); // standard, rotating, split
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('grace_period_minutes')->default(15);
            $table->foreignUuid('geofence_id')->nullable()->constrained('field_locations')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('timesheets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->unsignedBigInteger('sequence')->default(1)->index();
            
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('shift_configuration_id')->nullable()->constrained('shift_configurations')->nullOnDelete();
            $table->date('date')->index();
            
            $table->timestamp('clock_in_at')->nullable();
            $table->timestamp('clock_out_at')->nullable();
            $table->decimal('clock_in_latitude', 10, 7)->nullable();
            $table->decimal('clock_in_longitude', 10, 7)->nullable();
            $table->decimal('clock_out_latitude', 10, 7)->nullable();
            $table->decimal('clock_out_longitude', 10, 7)->nullable();
            
            $table->decimal('regular_hours', 5, 2)->default(0.00);
            $table->decimal('overtime_hours', 5, 2)->default(0.00);
            $table->decimal('holiday_overtime_hours', 5, 2)->default(0.00);
            
            $table->boolean('is_late')->default(false);
            $table->boolean('is_early_departure')->default(false);
            
            $table->string('status')->default('pending')->index(); // pending, approved, rejected
            $table->foreignUuid('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            $table->boolean('is_locked')->default(false)->index();
            
            $table->timestamps();
            
            $table->unique(['user_id', 'date']);
        });

        Schema::create('overtime_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->decimal('daily_threshold_hours', 4, 2)->default(8.00);
            $table->decimal('weekly_threshold_hours', 4, 2)->default(40.00);
            $table->decimal('standard_multiplier', 3, 2)->default(1.50);
            $table->decimal('holiday_multiplier', 3, 2)->default(2.00);
            $table->timestamps();
        });

        Schema::create('public_holidays', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->date('holiday_date')->index();
            $table->string('country_code', 2)->default('KE')->index();
            $table->decimal('multiplier', 3, 2)->default(2.00);
            $table->timestamps();
        });

        Schema::create('leave_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('leave_type')->index(); // annual, sick, compassionate, unpaid
            $table->decimal('balance_days', 5, 2)->default(21.00);
            $table->decimal('accrued_days', 5, 2)->default(0.00);
            $table->boolean('allow_negative_balance')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'leave_type']);
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->unsignedBigInteger('sequence')->default(1)->index();
            
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('leave_type')->index();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_days', 4, 2);
            $table->text('reason')->nullable();
            
            $table->string('status')->default('pending')->index(); // pending, approved, rejected
            $table->foreignUuid('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('public_holidays');
        Schema::dropIfExists('overtime_rules');
        Schema::dropIfExists('timesheets');
        Schema::dropIfExists('shift_configurations');
    }
};
