<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * Скріншоти програми в статтях бази знань, де їх ще не було: кабінет, автокупівля, Local API, кількість профілів.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->insertAfter('kabinet-nbu-v-numis-zamovlennia-bazane-y-koshyk', '~<h2>Що показує кабінет</h2>\s*<ul>.*?</ul>~su',
            $this->figure('cabinet', 1200, 833, 'Розділ «Кабінет» у Numis', 'Кабінет: «Бажане», кошик і замовлення з усіх профілів в одному вікні.'));

        $this->insertAfter('avtokupivlia-monet-nbu-iak-tse-pratsiuye-i-komu-pidkhodyt', '~<h2>Як працює автокупівля в Numis</h2>\s*<ol>.*?</ol>~su',
            $this->figure('tasks-live', 1200, 1093, 'Заплановані завдання автокупівлі в Numis', 'Завдання в Numis: монета, час старту за Києвом, відлік до старту й журнал.'));

        $this->insertAfter('adspower-local-api-adresa-kliuch-perevirka', '~<h2>Адреса за замовчуванням</h2>\s*<p>.*?</p>~su',
            $this->figure('connection', 488, 246, 'Розділ «Підключення до AdsPower» у Numis', 'Адреса Local API та ключ вводяться в «Налаштуваннях» Numis.'));

        $this->insertAfter('skilky-profiliv-adspower-potribno-dlia-kupivli-monet-nbu', '~<h2>Як порахувати</h2>\s*<ul>.*?</ul>~su',
            $this->figure('profiles', 488, 730, 'Збережені профілі в налаштуваннях Numis', 'Кожен профіль AdsPower додається окремо за ID і назвою.'));
    }

    public function down(): void
    {
        //
    }

    private function insertAfter(string $slug, string $pattern, string $html): void
    {
        $page = Page::where('slug', $slug)->first();

        if (! $page || str_contains($page->content, $html)) {
            return;
        }

        $page->update(['content' => preg_replace($pattern, '$0'."\n".addcslashes($html, '\\$'), $page->content, 1)]);
    }

    private function figure(string $name, int $width, int $height, string $alt, string $caption): string
    {
        return '<figure><img src="/images/app/numis-'.$name.'.png" alt="'.e($alt).'" width="'.$width.'" height="'.$height.'" loading="lazy"><figcaption>'.e($caption).'</figcaption></figure>';
    }
};
