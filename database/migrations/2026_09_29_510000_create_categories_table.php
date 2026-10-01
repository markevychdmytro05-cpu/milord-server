<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->timestamps();
        });

        DB::table('pages')->whereNotNull('category')->where('category', '!=', '')->distinct()->orderBy('category')->pluck('category')
            ->each(fn (string $name) => DB::table('categories')->insert(['name' => $name, 'created_at' => now(), 'updated_at' => now()]));
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
