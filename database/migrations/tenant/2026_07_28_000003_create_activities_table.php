<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->unsignedBigInteger('sequence')->default(1)->index();
            $table->string('reference_no')->unique();
            
            // Unified Activity Types: task, visit, appointment, call, survey, inspection, collection, delivery, merchandising, maintenance
            $table->string('activity_type')->index();
            $table->string('category')->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            
            $table->uuid('customer_id')->nullable()->index();
            $table->foreignUuid('field_location_id')->nullable()->constrained('field_locations')->nullOnDelete();
            $table->uuid('parent_activity_id')->nullable()->index();
            
            $table->timestamp('planned_start_at')->nullable();
            $table->timestamp('planned_end_at')->nullable();
            $table->timestamp('due_at')->nullable()->index();
            $table->string('timezone')->default('Africa/Nairobi');
            $table->integer('allowed_execution_window_minutes')->default(60);
            
            $table->string('priority')->default('medium')->index(); // low, medium, high, urgent
            $table->string('status')->default('pending')->index(); // pending, available, in_progress, completed, failed, exception, cancelled, reviewed, approved, rejected
            
            // Governing Policies
            $table->string('location_policy')->default('strict_geofence'); // none, soft_geofence, strict_geofence, custom_coordinates
            $table->string('attendance_policy')->default('gps'); // none, gps, qr_code, face_id, site_checkin, video_call
            $table->string('evidence_policy')->default('photo_and_note'); // none, photo, note, signature, photo_and_note, survey_response, custom
            $table->string('approval_policy')->default('auto'); // auto, supervisor_review, finance_approval
            
            // Execution details
            $table->timestamp('actual_start_at')->nullable();
            $table->timestamp('actual_end_at')->nullable();
            $table->decimal('start_latitude', 10, 7)->nullable();
            $table->decimal('start_longitude', 10, 7)->nullable();
            $table->decimal('end_latitude', 10, 7)->nullable();
            $table->decimal('end_longitude', 10, 7)->nullable();
            $table->string('device_id')->nullable()->index();
            $table->boolean('is_offline_captured')->default(false);
            
            // Outcome details
            $table->text('completion_notes')->nullable();
            $table->string('outcome_code')->nullable()->index();
            $table->json('payload')->nullable(); // typed payload data (survey answers, collection figures, merchandising observation IDs)
            
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('activity_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->unsignedBigInteger('sequence')->default(1)->index();
            $table->foreignUuid('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->string('assignee_type')->default('user'); // user, team
            $table->foreignUuid('assignee_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('assigned_at');
            $table->string('status')->default('assigned')->index(); // assigned, accepted, declined, completed
            $table->timestamps();
        });

        Schema::create('activity_evidence', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->unsignedBigInteger('sequence')->default(1)->index();
            $table->foreignUuid('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->string('evidence_type')->index(); // photo, signature, file, document, audio, video_call_log, survey_payload
            $table->string('file_path')->nullable();
            $table->string('mime_type')->nullable();
            $table->integer('file_size_bytes')->nullable();
            $table->decimal('captured_latitude', 10, 7)->nullable();
            $table->decimal('captured_longitude', 10, 7)->nullable();
            $table->timestamp('captured_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status');
            $table->string('to_status');
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_events');
        Schema::dropIfExists('activity_evidence');
        Schema::dropIfExists('activity_assignments');
        Schema::dropIfExists('activities');
    }
};
