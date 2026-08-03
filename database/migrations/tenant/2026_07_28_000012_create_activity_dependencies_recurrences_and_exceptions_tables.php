<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_dependencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignUuid('prerequisite_activity_id')->constrained('activities')->cascadeOnDelete();
            $table->string('dependency_type')->default('block_start')->index(); // block_start, block_completion
            $table->timestamps();

            $table->unique(['activity_id', 'prerequisite_activity_id']);
        });

        Schema::create('activity_recurrences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_activity_id')->constrained('activities')->cascadeOnDelete();
            $table->string('recurrence_pattern')->default('daily')->index(); // daily, weekly, monthly
            $table->integer('interval')->default(1);
            $table->json('days_of_week')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_exceptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->unsignedBigInteger('sequence')->default(1)->index();
            
            $table->foreignUuid('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('exception_type')->index(); // geofence_failed, evidence_missing, dwell_time_unmet, time_window_exceeded
            $table->text('reason');
            $table->string('status')->default('pending')->index(); // pending, approved, rejected
            
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_exceptions');
        Schema::dropIfExists('activity_recurrences');
        Schema::dropIfExists('activity_dependencies');
    }
};
