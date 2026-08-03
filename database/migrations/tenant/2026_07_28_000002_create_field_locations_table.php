<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->unsignedBigInteger('sequence')->default(1)->index();
            $table->string('name');
            $table->string('code')->nullable()->index();
            $table->string('location_type')->default('site')->index(); // site, outlet, customer_hq, warehouse, office
            $table->foreignUuid('parent_id')->nullable()->constrained('field_locations')->nullOnDelete();
            
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->integer('geofence_radius_meters')->default(100);
            
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('county')->default('Nairobi')->index(); // Kenya 47 Counties baseline
            $table->string('country_code', 2)->default('KE')->index();
            
            $table->json('opening_hours')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });

        // Add PostGIS spatial geometry column if PostgreSQL is used
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS postgis;');
            DB::statement('ALTER TABLE field_locations ADD COLUMN coordinates geometry(Point, 4326);');
            DB::statement('CREATE INDEX field_locations_coordinates_spatial_idx ON field_locations USING GIST (coordinates);');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('field_locations');
    }
};
