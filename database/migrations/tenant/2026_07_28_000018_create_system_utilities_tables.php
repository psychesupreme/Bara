<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_notices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('message');
            $table->string('target_role')->nullable()->index();
            $table->foreignUuid('target_commercial_node_id')->nullable()->constrained('commercial_nodes')->nullOnDelete();
            $table->boolean('is_mandatory')->default(false);
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('user_notice_acknowledgments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('system_notice_id')->constrained('system_notices')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('acknowledged_at');
            $table->timestamps();

            $table->unique(['system_notice_id', 'user_id']);
        });

        Schema::create('database_backups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('filename')->unique();
            $table->string('checksum_sha256');
            $table->unsignedBigInteger('size_bytes');
            $table->string('status')->default('completed')->index(); // completed, restored, failed
            $table->timestamps();
        });

        Schema::create('import_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('file_name');
            $table->string('entity_type')->index(); // customers, products, users
            $table->integer('total_rows')->default(0);
            $table->integer('valid_rows')->default(0);
            $table->integer('invalid_rows')->default(0);
            $table->json('errors')->nullable();
            $table->string('status')->default('dry_run')->index(); // dry_run, committed, failed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_jobs');
        Schema::dropIfExists('database_backups');
        Schema::dropIfExists('user_notice_acknowledgments');
        Schema::dropIfExists('system_notices');
    }
};
