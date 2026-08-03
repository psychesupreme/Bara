<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('invoice_number')->unique();
            $table->uuid('customer_id')->index();
            $table->decimal('total_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0.00);
            $table->decimal('balance_amount', 12, 2);
            $table->string('currency', 3)->default('KES')->index();
            $table->string('status')->default('unpaid')->index(); // unpaid, partial, paid
            $table->date('due_date')->index();
            $table->timestamps();
        });

        Schema::create('collections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->unsignedBigInteger('sequence')->default(1)->index();
            
            $table->string('receipt_number')->unique();
            $table->foreignUuid('collector_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('customer_id')->index();
            $table->foreignUuid('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            
            $table->string('payment_mode')->index(); // mpesa_stk, cash, cheque, bank_transfer
            $table->string('currency', 3)->default('KES')->index();
            $table->decimal('exchange_rate', 10, 4)->default(1.0000);
            $table->decimal('amount', 12, 2);
            $table->decimal('base_amount', 12, 2);
            
            $table->string('gateway_reference')->nullable()->index(); // M-Pesa Receipt / Checkout ID for idempotency
            $table->string('status')->default('pending')->index(); // pending, confirmed, reconciled, reversed, failed
            $table->boolean('is_offline_captured')->default(false);
            $table->timestamp('captured_at')->index();
            
            $table->timestamps();
        });

        Schema::create('collection_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('collection_id')->constrained('collections')->cascadeOnDelete();
            $table->foreignUuid('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->decimal('allocated_amount', 12, 2);
            $table->timestamps();
        });

        Schema::create('collection_reconciliations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('collection_id')->constrained('collections')->cascadeOnDelete();
            $table->foreignUuid('reconciled_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('reconciled_at');
            $table->string('status')->default('reconciled')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('collection_reversals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('collection_id')->constrained('collections')->cascadeOnDelete();
            $table->foreignUuid('reversed_by')->constrained('users')->cascadeOnDelete();
            $table->string('reversal_receipt_number')->unique();
            $table->text('reason');
            $table->timestamp('reversed_at');
            $table->timestamps();
        });

        Schema::create('promises_to_pay', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->unsignedBigInteger('sequence')->default(1)->index();
            
            $table->uuid('customer_id')->index();
            $table->foreignUuid('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->decimal('promised_amount', 12, 2);
            $table->date('promised_date')->index();
            $table->string('status')->default('pending')->index(); // pending, fulfilled, defaulted
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promises_to_pay');
        Schema::dropIfExists('collection_reversals');
        Schema::dropIfExists('collection_reconciliations');
        Schema::dropIfExists('collection_allocations');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('invoices');
    }
};
