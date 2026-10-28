<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Numis працює не лише з AdsPower, тож у загальних текстах сайту (послуги, статті, FAQ, головна, зміни) пишемо
 * «антидетект-браузер». Матеріали, присвячені саме AdsPower, лишаються як є: сторінка «Профілі AdsPower»,
 * статті про підключення й прогрів AdsPower та їхні FAQ-блоки.
 */
return new class extends Migration
{
    /** Сторінки, присвячені AdsPower. */
    private const ADSPOWER_PAGES = [
        'profili-adspower',
        'iak-pidkliuchyty-adspower-do-numis-pokrokova-instruktsiia',
        'prohriv-profiliv-adspower-u-numis-shcho-tse-i-iak-zapustyty',
    ];

    /** FAQ-модулі цих сторінок. */
    private const ADSPOWER_MODULES = [
        'SEO – FAQ: adspower-profiles',
        'SEO – FAQ: nalashtuvannya-adspower',
    ];

    /** @var array<string, string> регулярний вираз => заміна (порядок важливий) */
    private const RULES = [
        '/Кілька профілів AdsPower одночасно/u' => 'Кілька профілів одночасно',
        '/Кілька профілів AdsPower/u' => 'Кілька профілів',
        '/(профіл[а-яіїєґ’]*) AdsPower/iu' => '$1 антидетект-браузера',
        '/(Підключ)іть AdsPower/u' => '$1іть антидетект-браузер',
        '/([Пп]ідключення) AdsPower/u' => '$1 антидетект-браузера',
        '/([Кк]люч) AdsPower/u' => '$1 API антидетект-браузера',
        '/вимикайте AdsPower/u' => 'вимикайте антидетект-браузер',
        '/Numis, AdsPower і/u' => 'Numis, антидетект-браузер і',
        '/програму, AdsPower і/u' => 'програму, антидетект-браузер і',
        '/([Зз])’єднання з AdsPower/u' => '$1’єднання з антидетект-браузером',
        '/чи AdsPower/u' => 'чи антидетект-браузера',
        '/Чи потрібен AdsPower\?/u' => 'Чи потрібен антидетект-браузер?',
    ];

    public function up(): void
    {
        foreach (DB::table('pages')->whereNotIn('slug', self::ADSPOWER_PAGES)->get() as $page) {
            $changes = [];

            foreach (['title', 'excerpt', 'meta_title', 'meta_description', 'content'] as $field) {
                $value = (string) $page->{$field};
                $new = $this->generalize($value);

                if ($new !== $value) {
                    $changes[$field] = $new;
                }
            }

            if ($changes !== []) {
                DB::table('pages')->where('id', $page->id)->update($changes + ['updated_at' => now()]);
            }
        }

        foreach (Module::whereNotIn('name', self::ADSPOWER_MODULES)->get() as $module) {
            $setting = $this->walk($module->setting->toArray());

            if ($setting !== $module->setting->toArray()) {
                $module->update(['setting' => $setting]);
            }
        }
    }

    public function down(): void
    {
        //
    }

    private function generalize(string $text): string
    {
        foreach (self::RULES as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text) ?? $text;
        }

        return $text;
    }

    /** @param  mixed  $value */
    private function walk($value)
    {
        if (is_string($value)) {
            return $this->generalize($value);
        }

        if (is_array($value)) {
            return array_map(fn ($item) => $this->walk($item), $value);
        }

        return $value;
    }
};
