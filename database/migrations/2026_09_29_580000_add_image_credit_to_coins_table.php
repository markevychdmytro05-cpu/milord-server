<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coins', function (Blueprint $table) {
            $table->string('image_credit')->nullable()->after('image_source');
        });

        DB::table('coins')->where('image_source', 'like', '%bank.gov.ua%')->update(['image_credit' => 'Національний банк України']);
    }

    public function down(): void
    {
        Schema::table('coins', function (Blueprint $table) {
            $table->dropColumn('image_credit');
        });
    }
};
