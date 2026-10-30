<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD_URL = '/kalendar-vypusku-monet-nbu-2026';

    private const NEW_URL = '/baza-znan/plan-vypusku-monet-nbu-na-2026-rik-shcho-vyyde-do-kintsia-roku';

    private const DUPLICATE_BLOCK = '<h2>Як не пропустити появу монети в магазині НБУ</h2>';

    public function up(): void
    {
        foreach (DB::table('pages')->where('content', 'like', '%'.self::OLD_URL.'%')->get(['id', 'content']) as $page) {
            DB::table('pages')->where('id', $page->id)->update([
                'content' => str_replace('href="'.self::OLD_URL.'"', 'href="'.self::NEW_URL.'"', $page->content),
            ]);
        }

        foreach (DB::table('coins')->where('content', 'like', '%'.self::DUPLICATE_BLOCK.'%')->get(['id', 'content']) as $coin) {
            DB::table('coins')->where('id', $coin->id)->update([
                'content' => rtrim(strstr($coin->content, self::DUPLICATE_BLOCK, true)),
            ]);
        }
    }

    public function down(): void
    {
    }
};
