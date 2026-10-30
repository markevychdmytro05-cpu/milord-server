<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SLUG = 'pro-nas';

    private const FAQ_NAME = 'SEO – FAQ: about';

    private const PAGE_REPLACEMENTS = [
        'meta_description' => [
            'через профілі AdsPower.' => 'через профілі антидетект-браузера.',
        ],
        'content' => [
            'Підключаєте AdsPower через Local API й додаєте профілі. Покрокова інструкція: <a href="/blog/iak-pidkliuchyty-adspower-do-numis-pokrokova-instruktsiia">підключення AdsPower</a>.' => 'Підключаєте антидетект-браузер і додаєте профілі. Покрокова інструкція: <a href="/blog/iak-pidkliuchyty-adspower-do-numis-pokrokova-instruktsiia">підключення антидетект-браузера</a>.',
        ],
    ];

    public function up(): void
    {
        $page = DB::table('pages')->where('slug', self::SLUG)->first();

        if ($page !== null) {
            $changes = [];

            foreach (self::PAGE_REPLACEMENTS as $field => $pairs) {
                $value = (string) $page->{$field};

                foreach ($pairs as $old => $new) {
                    $value = str_replace($old, $new, $value);
                }

                if ($value !== (string) $page->{$field}) {
                    $changes[$field] = $value;
                }
            }

            if ($changes !== []) {
                DB::table('pages')->where('id', $page->id)->update($changes + ['updated_at' => now()]);
            }
        }

        $faq = Module::where('name', self::FAQ_NAME)->first();

        if ($faq !== null) {
            $json = json_encode($faq->setting->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $updated = str_replace('в кошик через профілі AdsPower.', 'в кошик через профілі антидетект-браузера.', $json);

            if ($updated !== $json) {
                $faq->update(['setting' => json_decode($updated, true)]);
            }
        }
    }

    public function down(): void
    {
    }
};
