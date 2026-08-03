<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->unsignedBigInteger('sequence')->default(1)->index();
            
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('device_uuid')->unique();
            $table->string('model')->nullable();
            $table->string('os_version')->nullable();
            $table->string('app_version')->nullable();
            $table->string('status')->default('pending_approval')->index(); // pending_approval, approved, revoked
            $table->string('public_key_hash')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            
            $table->timestamps();
        });

        Schema::create('tracking_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->unsignedBigInteger('sequence')->default(1)->index();
            
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('field_device_id')->nullable()->constrained('field_devices')->nullOnDelete();
            
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->string('purpose')->default('shift')->index(); // shift, activity, emergency
            $table->boolean('is_active')->default(true)->index();
            
            $table->timestamps();
        });

        Schema::create('tracking_points', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->unsignedBigInteger('sequence')->default(1)->index();
            
            $table->foreignUuid('session_id')->constrained('tracking_sessions')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy_meters', 8, 2)->nullable();
            $table->decimal('speed_mps', 8, 2)->nullable();
            $table->decimal('heading_degrees', 6, 2)->nullable();
            
            $table->timestamp('recorded_at')->index();
            $table->timestamp('received_at');
            $table->boolean('is_mock_location')->default(false);
            $table->timestamps();
        });

        Schema::create('sos_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->unsignedBigInteger('sequence')->default(1)->index();
            
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('status')->default('active')->index(); // active, responding, resolved, false_alarm
            $table->foreignUuid('responder_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            
            $table->timestamp('triggered_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sos_requests');
        Schema::dropIfExists('tracking_points');
        Schema::dropIfExists('tracking_sessions');
        Schema::dropIfExists('field_devices');
    }
};
