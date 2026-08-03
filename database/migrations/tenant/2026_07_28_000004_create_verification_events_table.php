<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->unsignedBigInteger('sequence')->default(1)->index();
            
            $table->foreignUuid('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('field_location_id')->nullable()->constrained('field_locations')->nullOnDelete();
            
            $table->integer('verification_level')->default(1); // 1 Platform, 2 Company, 3 Activity, 4 Location, 5 Specific Activity
            $table->timestamp('verified_at');
            
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('gps_accuracy_meters', 8, 2);
            $table->decimal('distance_to_target_meters', 10, 2)->nullable();
            
            $table->boolean('is_geofence_valid')->default(false);
            $table->boolean('is_time_window_valid')->default(true);
            $table->boolean('is_device_valid')->default(true);
            $table->boolean('is_attendance_valid')->default(true);
            
            $table->string('attendance_adapter')->default('gps'); // gps, qr_code, face_id, video_call
            $table->string('device_id')->nullable()->index();
            $table->string('signature_hash')->nullable();
            
            $table->string('verification_status')->index(); // passed, failed_accuracy, failed_geofence, failed_device, exception
            $table->text('exception_reason')->nullable();
            
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_events');
    }
};
