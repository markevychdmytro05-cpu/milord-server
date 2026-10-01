<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('robots', 30)->default('index, follow')->after('og_image');
        });

        DB::table('pages')->where('noindex', true)->update(['robots' => 'noindex, nofollow']);

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('noindex');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('noindex')->default(false)->after('og_image');
        });

        DB::table('pages')->where('robots', 'like', 'noindex%')->update(['noindex' => true]);

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('robots');
        });
    }
};
