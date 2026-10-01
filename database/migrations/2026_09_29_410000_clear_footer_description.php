<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Абзац про проєкт у футері прибрано.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_settings')->update(['footer_description' => null]);
    }

    public function down(): void
    {
        //
    }
};
