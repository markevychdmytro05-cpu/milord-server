<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * У статті про швидкий розпродаж частка інтернет-магазину була застарілою (60%): з 15 жовтня 2025 року це 75% тиражу.
 */
return new class extends Migration
{
    private const OLD = '<li><strong>Значна частка йде в онлайн.</strong> За <a href="https://kosht.media/nova-moneta-batkivske-shchastia-de-kupyty/">повідомленнями медіа</a>, через інтернет-магазин НБУ реалізовують 60% тиражу, тому на старті продажу туди приходить багато покупців одночасно.</li>';

    private const NEW = '<li><strong>Основна частка йде в онлайн.</strong> За даними НБУ, з 15 жовтня 2025 року через інтернет-магазин реалізується 75% тиражу нумізматичної продукції (докладніше: <a href="/blog/de-kupyty-pamiatni-monety-nbu-internet-mahazyn-chy-bank">де купити пам’ятні монети НБУ</a>), тому на старті продажу туди приходить багато покупців одночасно.</li>';

    public function up(): void
    {
        $page = Page::where('type', Page::TYPE_PAGE)->where('slug', 'chomu-monety-nbu-rozkuplyuyut-za-khvylyny')->first();

        if ($page === null || ! str_contains((string) $page->content, self::OLD)) {
            return;
        }

        $page->forceFill(['content' => str_replace(self::OLD, self::NEW, $page->content)])->save();
    }

    public function down(): void
    {
        //
    }
};
