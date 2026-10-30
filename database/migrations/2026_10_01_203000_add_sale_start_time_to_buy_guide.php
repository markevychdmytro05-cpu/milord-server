<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $page = DB::table('pages')->where('slug', 'iak-kupyty-monetu-v-internet-mahazyni-nbu-pokrokova-instruktsiia')->first();

        if (! $page || str_contains($page->content, '10:00')) {
            return;
        }

        $content = str_replace(
            'зникають зі складу за кілька хвилин після старту.</p>',
            'зникають зі складу за кілька хвилин після старту. Продаж щоразу починається о 10:00 за київським часом.</p>',
            $page->content,
        );
        $content = str_replace(
            'наприклад «В продажу 29 вересня».</li>',
            'наприклад «В продажу 29 вересня». Сам продаж стартує о 10:00 за київським часом.</li>',
            $content,
        );
        $content = str_replace(
            '<strong>У момент старту натисніть кнопку купівлі.</strong> До цього її на сторінці немає.',
            '<strong>О 10:00 натисніть кнопку купівлі.</strong> До цього її на сторінці немає.',
            $content,
        );

        DB::table('pages')->where('id', $page->id)->update(['content' => $content]);
    }

    public function down(): void
    {
    }
};
