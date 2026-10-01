<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('key', 32)->unique();
            $table->string('customer')->nullable();
            $table->string('contact')->nullable();
            $table->unsignedInteger('max_accounts');
            $table->unsignedInteger('max_devices')->default(1);
            $table->unsignedInteger('price')->nullable();
            $table->string('status', 16)->default('active')->index();
            $table->timestamp('expires_at')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
