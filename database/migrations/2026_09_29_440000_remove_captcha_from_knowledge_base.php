<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * База знань більше не згадує CAPTCHA: правки в статтях, метаданих і адресі статті про чергу та 429.
 */
return new class extends Migration
{
    private const OLD_SLUG = 'cherha-kapcha-429';

    private const NEW_SLUG = 'cherha-ta-429';

    public function up(): void
    {
        $this->rewriteQueueArticle();

        $this->edit('yak-kupyty-monetu-nbu-pershym', [
            'вхід виконано, CAPTCHA пройдена.' => 'вхід виконано.',
            'Відсутність CAPTCHA, черги чи' => 'Відсутність черги чи',
        ]);
        $this->edit('yak-kupyty-monetu-nbu', [
            '<li><strong>Черга й перевірка «я не робот».</strong> Магазин може ввімкнути їх під навантаженням. Див. <a href="/cherha-kapcha-429">що робити при черзі, CAPTCHA й помилці 429</a>.</li>' => '<li><strong>Черга.</strong> Магазин може ввімкнути її під навантаженням. Див. <a href="/cherha-kapcha-429">що робити при черзі й помилці 429</a>.</li>',
        ]);
        $this->edit('test-povedinky', ['Черга, CAPTCHA і 429' => 'Черга і 429']);
        $this->edit('bezpeka-danykh', ['<li>Дані для розв’язання CAPTCHA: програма не відправляє їх стороннім сервісам.</li>'."\n" => '']);
        $this->edit('bot-dlya-kupivli-monet-nbu', [
            '<li>Пройти перевірку «я не робот», якщо магазин її покаже.</li>'."\n" => '',
        ]);
        $this->removeBotLimitsSection();
        $this->removeCaptchaFromCartArticle();
        $this->softenLimitations();

        Page::where('slug', 'help')->update([
            'meta_description' => 'Як купити монету в інтернет-магазині НБУ, налаштувати AdsPower, запустити Numis, отримати пробний ключ і що робити при черзі чи помилці 429.',
        ]);

        Page::query()->each(function (Page $page): void {
            if ($page->content && str_contains($page->content, '/'.self::OLD_SLUG)) {
                $page->update(['content' => str_replace('/'.self::OLD_SLUG, '/'.self::NEW_SLUG, $page->content)]);
            }
        });
    }

    public function down(): void
    {
        //
    }

    /** @param  array<string, string>  $replacements */
    private function edit(string $slug, array $replacements): void
    {
        $page = Page::where('slug', $slug)->first();

        if ($page) {
            $page->update(['content' => strtr($page->content, $replacements)]);
        }
    }

    /** Без розділів про недоліки й обмеження: лишаються нейтральні факти. */
    private function softenLimitations(): void
    {
        $this->replacePattern('yak-kupyty-monetu-nbu-pershym', [
            '~<h2>Чого не гарантує ніхто</h2>\s*<ul>.*?</ul>\s*~su' => '',
            '~<p>Імітації проводились.*?</p>~su' => '<p>Заміри виконано на локальній імітації продажу та в режимі спостереження на реальному сайті.</p>',
        ]);
        $this->replacePattern('test-povedinky', [
            '~<h2>Обмеження</h2>\s*<p>.*?</p>~su' => '<h2>Коли тест зупиняється</h2><p>Коли ви створюєте завдання купівлі або наближається його підготовка, тести зупиняються самі.</p>',
        ]);
        $this->replacePattern('bot-dlya-kupivli-monet-nbu', [
            '~<h2>Чи безпечно для акаунта</h2>\s*<p>.*?</p>~su' => '<h2>Чи безпечно</h2><p>Програма не запитує ваш пароль від НБУ й не передає його нікуди: використовується сесія вашого профілю AdsPower.</p>',
        ]);

        Page::where('slug', 'test-povedinky')->update([
            'excerpt' => 'Що робить «Тест поведінки»: рухи курсора, паузи й прокрутка на сторінках товарів. Як запустити.',
            'meta_description' => 'Що робить «Тест поведінки»: рухи курсора, паузи й прокрутка на сторінках товарів. Як запустити.',
        ]);
        Page::where('slug', 'bot-dlya-kupivli-monet-nbu')->update([
            'excerpt' => 'Numis стежить за кнопкою купівлі та додає монету в кошик у момент старту. Що програма робить сама і що лишається вам.',
            'meta_description' => 'Numis стежить за кнопкою купівлі та додає монету в кошик у момент старту. Що програма робить сама і що лишається вам.',
        ]);
    }

    /** @param  array<string, string>  $patterns */
    private function replacePattern(string $slug, array $patterns): void
    {
        $page = Page::where('slug', $slug)->first();

        if ($page) {
            $page->update(['content' => preg_replace(array_keys($patterns), array_values($patterns), $page->content)]);
        }
    }

    private function removeBotLimitsSection(): void
    {
        $page = Page::where('slug', 'bot-dlya-kupivli-monet-nbu')->first();

        if ($page) {
            $page->update(['content' => preg_replace('~<h2>Чого Numis не робить</h2>\s*<ul>.*?</ul>\s*~su', '', $page->content)]);
        }
    }

    private function removeCaptchaFromCartArticle(): void
    {
        $page = Page::where('slug', 'ne-dodaje-v-koshyk')->first();

        if (! $page) {
            return;
        }

        $description = 'Кнопка не з’явилась, профіль закритий або ви в черзі: розбираємо причини, чому монета не потрапила в кошик, і що робити далі.';
        $page->update([
            'excerpt' => $description,
            'meta_description' => $description,
            'content' => preg_replace('~<h3>Магазин просить CAPTCHA</h3>\s*<p>.*?</p>\s*~su', '', $page->content),
        ]);
    }

    private function rewriteQueueArticle(): void
    {
        $page = Page::where('slug', self::OLD_SLUG)->first();

        if (! $page) {
            return;
        }

        $description = 'Що означають черга й помилка 429 під час продажу монет НБУ, як на них реагує Numis і що робити вам.';
        $page->update([
            'slug' => self::NEW_SLUG,
            'title' => 'Черга й помилка 429 на сайті НБУ: що робити',
            'meta_title' => 'Черга та помилка 429 на сайті НБУ: що робити',
            'excerpt' => $description,
            'meta_description' => $description,
            'og_image' => '/images/kb/'.self::NEW_SLUG.'.jpg',
            'content' => <<<'HTML'
<p>Під час гарячих продажів магазин може поставити вас у чергу або тимчасово обмежити запити. Це нормально: ось що робити в кожному випадку.</p>
<h2>Помилка 429: забагато запитів</h2>
<p>Магазин тимчасово блокує звернення, якщо їх було забагато.</p>
<ul>
<li>Numis робить спільну паузу для всіх профілів і враховує час, який назвав сервер.</li>
<li>Якщо сервер не назвав, пауза росте від 30 до 300 секунд.</li>
<li>Після паузи сторінка оновлюється сама, поки не закінчилось вікно завдання.</li>
<li>Причину обмеження (IP, акаунт чи щось інше) без даних сервера встановити неможливо.</li>
</ul>
<p><strong>Що робити вам:</strong> не оновлюйте сторінку вручну паралельно й не запускайте забагато профілів з однієї адреси.</p>
<h2>Черга магазину</h2>
<p>Якщо НБУ поставив вас у чергу, Numis не оновлює сторінку й спостерігає за чергою до підтвердження кошика або вашої зупинки. Зміни номера в черзі записуються в журнал. Після того як черга зникла, є до двох хвилин на підтвердження кошика. Повторно кнопка не натискається. Якщо підтвердження не надійшло, результат позначається як невідомий: <a href="/ne-dodaje-v-koshyk">перевірте кошик вручну</a>.</p>
<h2>Поради</h2>
<ul>
<li>Не закривайте кришку ноутбука й не вимикайте AdsPower.</li>
<li>Тримайте в профілі одну вкладку магазину.</li>
<li>Вимкніть інші скрипти купівлі в цьому профілі.</li>
</ul>
<h2>Читайте також</h2>
<p><a href="/yak-kupyty-monetu-nbu-pershym">Як встигнути купити монету першим</a> · <a href="/services/task-journal">Журнал завдань</a></p>
HTML,
        ]);
    }
};
