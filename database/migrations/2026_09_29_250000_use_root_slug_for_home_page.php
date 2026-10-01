<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Головна сторінка – це сторінка зі slug «/», як і будь-яка інша вона відкривається за своїм slug.
 * Прапорець is_home більше не потрібен.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('pages')->where('is_home', true)->update(['slug' => '/']);

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('is_home');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('is_home')->default(false)->after('type');
        });

        DB::table('pages')->where('slug', '/')->update(['is_home' => true, 'slug' => 'home']);
    }
};
