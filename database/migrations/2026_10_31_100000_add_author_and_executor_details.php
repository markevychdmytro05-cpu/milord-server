<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const AUTHOR = 'Vlad Volodyn';

    private const FOOTER_TEXT = '© Numis · Vlad Volodyn';

    public function up(): void
    {
        $about = DB::table('pages')->where('slug', 'pro-nas')->first();

        if ($about !== null && ! str_contains((string) $about->content, 'Хто стоїть за Numis')) {
            $block = '<h2>Хто стоїть за Numis</h2><p>Numis — незалежний проєкт. Контактна особа й автор матеріалів на сайті — '.self::AUTHOR.'. Питання про ліцензії й роботу програми приймаємо на пошту <a href="mailto:help@numis.in.ua">help@numis.in.ua</a> та в <a href="https://t.me/NumisNBU" rel="noopener" target="_blank">Telegram</a>.</p>';
            $content = (string) $about->content;
            $marker = '<h2>Зв’язок</h2>';

            $content = str_contains($content, $marker)
                ? str_replace($marker, $block.$marker, $content)
                : $content.$block;

            DB::table('pages')->where('id', $about->id)->update(['content' => $content, 'updated_at' => now()]);
        }

        $offer = DB::table('pages')->where('slug', 'publichna-oferta')->first();

        if ($offer !== null && ! str_contains((string) $offer->content, self::AUTHOR)) {
            $content = (string) $offer->content;
            $parties = '<p>Виконавець — проєкт Numis, контактна особа '.self::AUTHOR.' (<a href="mailto:help@numis.in.ua">help@numis.in.ua</a>). Замовник — особа, яка придбаває ліцензію на програму.</p>';

            $content = str_replace('<h2>1. Предмет</h2>', $parties.'<h2>1. Предмет</h2>', $content);
            $content = str_replace(
                '<p>З усіх питань пишіть через форму зв’язку на сайті.</p>',
                '<p>З усіх питань пишіть на <a href="mailto:help@numis.in.ua">help@numis.in.ua</a>, у <a href="https://t.me/NumisNBU" rel="noopener" target="_blank">Telegram</a> або через форму зв’язку на сайті.</p>',
                $content,
            );

            DB::table('pages')->where('id', $offer->id)->update(['content' => $content, 'updated_at' => now()]);
        }

        DB::table('site_settings')->whereNull('footer_text')->update(['footer_text' => self::FOOTER_TEXT]);
    }

    public function down(): void
    {
    }
};
