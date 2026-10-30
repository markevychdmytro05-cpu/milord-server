<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $page = DB::table('pages')->where('slug', 'reyestratsiia-v-mahazyni-nbu-bankid-vkhid-i-osobystyy-kabinet')->first();

        if ($page === null) {
            return;
        }

        $content = str_replace('з’єднати Numis з AdsPower', 'з’єднати Numis з антидетект-браузером', (string) $page->content);

        if ($content !== $page->content) {
            DB::table('pages')->where('id', $page->id)->update(['content' => $content, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
    }
};
