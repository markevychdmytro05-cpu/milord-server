<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Виправляє «у Telegram на пошту» і повтор речення на сторінці ліцензії. */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('pages')->where('content', 'like', '%Telegram</a> на пошту%')->get() as $page) {
            DB::table('pages')->where('id', $page->id)->update([
                'content' => str_replace('Telegram</a> на пошту', 'Telegram</a> або на пошту', $page->content),
            ]);
        }

        $page = DB::table('pages')->where('slug', 'litsenziia-na-prystriy')->first();

        if ($page) {
            $duplicate = '<li>Відповіді сервера захищені цифровим підписом, тому їх не можна підмінити.</li>';
            $replacement = '<li>Без зв’язку з сервером програма працює офлайн до 72 годин.</li>';
            DB::table('pages')->where('id', $page->id)->update(['content' => str_replace($duplicate, $replacement, $page->content)]);
        }
    }

    public function down(): void
    {
        //
    }
};
