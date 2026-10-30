<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('footer_text')->nullable()->change();
        });

        DB::table('site_settings')->where('footer_text', '© Numis')->update(['footer_text' => null]);
    }

    public function down(): void
    {
    }
};
