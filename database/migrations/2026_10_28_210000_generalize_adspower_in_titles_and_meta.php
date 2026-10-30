<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const REPLACEMENTS = [
        '/' => [
            'meta_title' => ['програма Numis з AdsPower' => 'програма Numis'],
        ],
        'blog' => [
            'meta_title' => ['купівля монет НБУ, AdsPower, ліцензія' => 'купівля монет НБУ, автоматизація, ліцензія'],
            'meta_description' => ['налаштувати AdsPower, запустити Numis' => 'налаштувати антидетект-браузер, запустити Numis'],
            'excerpt' => ['від AdsPower до пробного періоду' => 'від підключення антидетект-браузера до пробного періоду'],
        ],
        'kabinet-nbu-v-numis-zamovlennia-bazane-y-koshyk' => [
            'meta_description' => ['з кількох профілів AdsPower в одному вікні' => 'з кількох профілів антидетект-браузера в одному вікні'],
            'excerpt' => ['з кількох профілів AdsPower в одному вікні' => 'з кількох профілів антидетект-браузера в одному вікні'],
        ],
        'avtokupivlia-monet-nbu-iak-tse-pratsiuye-i-komu-pidkhodyt' => [
            'meta_description' => ['з профілями AdsPower' => 'з профілями антидетект-браузера'],
            'excerpt' => ['з профілями AdsPower' => 'з профілями антидетект-браузера'],
        ],
        'bezpeka-danykh-u-numis-shcho-zberihayetsia-i-de' => [
            'meta_description' => ['ключ AdsPower' => 'ключ API антидетект-браузера'],
            'excerpt' => ['ключ AdsPower' => 'ключ API антидетект-браузера'],
        ],
    ];

    public function up(): void
    {
        foreach (self::REPLACEMENTS as $slug => $fields) {
            $page = DB::table('pages')->where('slug', $slug === '/' ? '/' : $slug)->first();

            if ($page === null && $slug === '/') {
                $page = DB::table('pages')->where('slug', 'home')->orWhere('slug', '')->first();
            }

            if ($page === null) {
                continue;
            }

            $changes = [];

            foreach ($fields as $field => $pairs) {
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
    }

    public function down(): void
    {
    }
};
