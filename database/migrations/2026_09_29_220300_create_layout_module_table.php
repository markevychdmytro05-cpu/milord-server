<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('layout_module', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layout_id')
                ->constrained('layouts')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('module_id')
                ->constrained('modules')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('layout_module');
    }
};
