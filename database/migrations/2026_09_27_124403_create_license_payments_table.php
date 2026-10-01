<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('license_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('operation', 16);
            $table->unsignedBigInteger('amount_cents');
            $table->char('currency', 3)->default('USD');
            $table->timestamp('paid_at')->index();
            $table->unsignedInteger('duration_days')->nullable();
            $table->dateTime('period_starts_at')->nullable();
            $table->dateTime('period_ends_at')->nullable();
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->uuid('idempotency_key');
            $table->char('request_hash', 64);
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable();
            $table->timestamps();

            $table->unique(['license_id', 'idempotency_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_payments');
    }
};
