<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('language_lines')
            ->where('group', 'site')->where('key', 'pricing.trial_button')
            ->update(['text' => json_encode(['uk' => 'Отримати ключ'], JSON_UNESCAPED_UNICODE)]);

        Cache::flush();
    }

    public function down(): void
    {
        DB::table('language_lines')
            ->where('group', 'site')->where('key', 'pricing.trial_button')
            ->update(['text' => json_encode(['uk' => 'Отримати пробний ключ'], JSON_UNESCAPED_UNICODE)]);

        Cache::flush();
    }
};
