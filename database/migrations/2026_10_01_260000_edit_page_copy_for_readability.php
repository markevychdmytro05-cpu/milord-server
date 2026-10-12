<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Редагування текстів сторінок: «Про нас» без корпоративної води, коротші кінцівки статей,
 * без жирних лідерів у списках, одне тире замість двох видів, без дубля «або… або» у контактах.
 */
return new class extends Migration
{
    /** Статті, де лишається рядок про календар випусків (інструкції з купівлі). */
    private const KEEP_CALENDAR_LINE = ['iak-kupyty-monetu-v-internet-mahazyni-nbu-pokrokova-instruktsiia', 'avtokupivlia-monet-nbu-iak-tse-pratsiuye-i-komu-pidkhodyt', 'avtomatychna-kupivlia-monet-nbu'];

    public function up(): void
    {
        $this->rewriteAbout();

        foreach (DB::table('pages')->get() as $page) {
            $content = (string) $page->content;
            $keepCalendar = in_array($page->slug, self::KEEP_CALENDAR_LINE, true);

            if (! $keepCalendar) {
                $content = preg_replace('~<p>Що зараз виходить у магазині НБУ:.*?</p>~su', '', $content) ?? $content;
                $content = preg_replace('~\s*Що виходить найближчим часом:.*?\.</p>~su', '.</p>', $content) ?? $content;
                $content = preg_replace('~(\.)\.</p>~u', '$1</p>', $content) ?? $content;
            }

            $content = $this->limitRelatedLinks($content);

            if (in_array($page->slug, ['iak-vstyhnuty-kupyty-monetu-nbu-pershym', 'avtokupivlia-monet-nbu-iak-tse-pratsiuye-i-komu-pidkhodyt'], true)) {
                $content = preg_replace('~<li><strong>[^<]{2,40}?\.</strong>\s*~u', '<li>', $content) ?? $content;
            }

            if ($page->slug === 'iak-vstyhnuty-kupyty-monetu-nbu-pershym') {
                $content = str_replace(
                    ['<li>Старти оголошують за Києвом. Розбіжність годинника навіть на секунду може коштувати монети.</li>', '<li>Профіль відкритий, вхід виконано.</li>', '<li>Постійне оновлення сторінки призводить до помилки 429.</li>'],
                    ['<li>Старти оголошують за київським часом, тож розбіжність годинника навіть на секунду може коштувати монети.</li>', '<li>Сторінка має бути підготовлена: профіль відкритий, вхід в акаунт виконано.</li>', '<li>Сторінку не можна оновлювати без кінця: це призводить до помилки 429.</li>'],
                    $content,
                );
            }

            $content = preg_replace('~(Telegram</a>) або на пошту (<a href="mailto:[^"]+">[^<]+</a>) або ~u', '$1, на пошту $2 або ', $content) ?? $content;
            $content = preg_replace('~Telegram</a>, на пошту (<a href="mailto:[^"]+">[^<]+</a>) або залиште~u', 'Telegram</a>, на пошту $1 або залиште', $content) ?? $content;

            DB::table('pages')->where('id', $page->id)->update([
                'content' => str_replace(' – ', ' — ', $content),
                'title' => str_replace(' – ', ' — ', (string) $page->title),
                'excerpt' => $page->excerpt === null ? null : str_replace(' – ', ' — ', $page->excerpt),
                'meta_title' => $page->meta_title === null ? null : str_replace(' – ', ' — ', $page->meta_title),
                'meta_description' => $page->meta_description === null ? null : str_replace(' – ', ' — ', $page->meta_description),
            ]);
        }
    }

    /** У блоці «Читайте також» лишається не більше трьох посилань. */
    private function limitRelatedLinks(string $content): string
    {
        return preg_replace_callback('~(Читайте також(?:</h2>\s*<p>|: ))((?:<a [^>]+>[^<]*</a>(?: · )?){4,})~u', function (array $match): string {
            preg_match_all('~<a [^>]+>[^<]*</a>~u', $match[2], $links);

            return $match[1].implode(' · ', array_slice($links[0], 0, 3));
        }, $content) ?? $content;
    }

    private function rewriteAbout(): void
    {
        $content = '<p>Numis зробили для тих, хто не хоче сидіти біля екрана, коли в інтернет-магазині НБУ з’являється кнопка «Купити». Програма працює на вашому комп’ютері, стежить за сторінкою монети й у момент появи кнопки додає монету в кошик. Доставку й оплату ви оформлюєте самі у своєму браузері.</p>'
            .'<h2>Як це працює</h2><ol>'
            .'<li>Ви активуєте ліцензійний ключ, і він прив’язується до вашого комп’ютера.</li>'
            .'<li>Підключаєте AdsPower через Local API й додаєте профілі. Покрокова інструкція: <a href="/baza-znan/iak-pidkliuchyty-adspower-do-numis-pokrokova-instruktsiia">підключення AdsPower</a>.</li>'
            .'<li>Створюєте завдання: посилання на монету, час старту за Києвом і профілі.</li>'
            .'<li>Програма заздалегідь готує профіль і сторінку, а коли кнопка з’являється, натискає її один раз. Хід кожного завдання видно в <a href="/services/zurnal-zavdan">журналі</a>.</li>'
            .'</ol>'
            .'<h2>Кому підійде</h2>'
            .'<p>Колекціонерам і перепродавцям, які купують обмежені випуски з кількох акаунтів і не хочуть залежати від швидкості реакції чи кількості відкритих вікон. Порівняння з ручною купівлею: <a href="/baza-znan/kupivlia-monet-nbu-vruchnu-chy-avtomatychno-shcho-obraty">вручну чи автоматично</a>.</p>'
            .'<h2>Що виходить у магазині НБУ</h2>'
            .'<p>Ми ведемо довідник випусків: <a href="/monety-nbu">монети НБУ</a> з датами й тиражами та <a href="/kalendar-vypusku-monet-nbu-2026">календар випуску 2026</a> за офіційним планом Національного банку.</p>'
            .'<h2>Зв’язок</h2>'
            .'<p>Напишіть нам у <a href="https://t.me/NumisNBU" rel="noopener" target="_blank">Telegram</a>, на пошту <a href="mailto:egorkolenkov43@gmail.com">egorkolenkov43@gmail.com</a> або залиште заявку у формі внизу сторінки.</p>';

        DB::table('pages')->where('type', 'page')->where('slug', 'pro-nas')->update([
            'content' => $content,
            'excerpt' => 'Що таке Numis, як він працює, чого не робить і як з нами зв’язатися.',
        ]);
    }

    public function down(): void
    {
        //
    }
};
