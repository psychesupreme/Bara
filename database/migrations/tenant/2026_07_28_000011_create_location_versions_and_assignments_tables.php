<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('field_location_id')->constrained('field_locations')->cascadeOnDelete();
            $table->integer('version_number')->default(1);
            
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->integer('geofence_radius_meters')->default(100);
            
            $table->timestamp('effective_from')->index();
            $table->timestamp('effective_to')->nullable()->index();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->text('change_reason')->nullable();
            $table->timestamps();

            $table->unique(['field_location_id', 'version_number']);
        });

        Schema::create('location_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->unsignedBigInteger('sequence')->default(1)->index();
            
            $table->foreignUuid('field_location_id')->constrained('field_locations')->cascadeOnDelete();
            $table->string('assignee_type')->default('user')->index(); // user, team, territory
            $table->foreignUuid('assignee_id')->constrained('users')->cascadeOnDelete();
            
            $table->string('role')->default('primary_rep')->index();
            $table->boolean('is_primary')->default(true);
            
            $table->timestamp('effective_from')->index();
            $table->timestamp('effective_to')->nullable()->index();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_assignments');
        Schema::dropIfExists('location_versions');
    }
};
