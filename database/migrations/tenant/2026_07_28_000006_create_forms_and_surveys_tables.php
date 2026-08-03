<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->unsignedBigInteger('sequence')->default(1)->index();
            
            $table->string('title');
            $table->string('code')->unique();
            $table->string('category')->default('survey')->index(); // survey, inspection, feedback, check_in, merchandising
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('form_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('form_template_id')->constrained('form_templates')->cascadeOnDelete();
            $table->integer('version_number')->default(1);
            $table->json('schema_definition'); // JSON schema of questions, branching logic, validation rules
            $table->boolean('is_published')->default(false)->index();
            $table->timestamp('published_at')->nullable();
            
            $table->timestamps();
            
            $table->unique(['form_template_id', 'version_number']);
        });

        Schema::create('form_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->unsignedBigInteger('sequence')->default(1)->index();
            
            $table->foreignUuid('form_version_id')->constrained('form_versions')->cascadeOnDelete();
            $table->foreignUuid('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->foreignUuid('respondent_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->json('response_data'); // Typed question answers
            $table->decimal('score', 6, 2)->nullable();
            $table->decimal('submission_latitude', 10, 7)->nullable();
            $table->decimal('submission_longitude', 10, 7)->nullable();
            
            $table->timestamp('submitted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_responses');
        Schema::dropIfExists('form_versions');
        Schema::dropIfExists('form_templates');
    }
};
