<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Старий slug => новий, після транслітераційної перегенерації. */
    private const MAP = [
        'services/auto-buy' => 'services/avtomatychna-kupivlia-monet-nbu',
        'services/adspower-profiles' => 'services/profili-adspower',
        'services/watch-mode' => 'services/rezym-sposterezennia',
        'services/device-license' => 'services/litsenziia-na-prystriy',
        'services/task-journal' => 'services/zurnal-zavdan',
        'about' => 'pro-nas',
        'privacy' => 'polityka-konfidentsiynosti',
        'terms' => 'publichna-oferta',
        'nalashtuvannya-adspower' => 'iak-pidkliuchyty-adspower-do-numis-pokrokova-instruktsiia',
        'yak-kupyty-monetu-nbu-pershym' => 'iak-vstyhnuty-kupyty-monetu-nbu-pershym',
        'cherha-ta-429' => 'cherha-y-pomylka-429-na-sayti-nbu-shcho-robyty',
        'kabinet-nbu' => 'kabinet-nbu-v-numis-zamovlennia-bazane-y-koshyk',
        'progriv-profiliv' => 'prohriv-profiliv-adspower-u-numis-shcho-tse-i-iak-zapustyty',
        'probnyi-period' => 'probnyy-period-numis-3-dni-bezkoshtovno',
        'licenziya-ta-pristroyi' => 'litsenziia-numis-aktyvatsiia-prystriy-oflayn-dostup',
        'bezpeka-danykh' => 'bezpeka-danykh-u-numis-shcho-zberihayetsia-i-de',
        'help' => 'baza-znan-numis',
        'yak-kupyty-monetu-nbu' => 'iak-kupyty-monetu-v-internet-mahazyni-nbu-pokrokova-instruktsiia',
        'bot-dlya-kupivli-monet-nbu' => 'bot-dlia-kupivli-monet-nbu-shcho-vmiye-numis-a-shcho-ni',
        'ne-dodaje-v-koshyk' => 'moneta-ne-dodayetsia-v-koshyk-prychyny-y-shcho-robyty',
        'avtokupivlya-monet-nbu' => 'avtokupivlia-monet-nbu-iak-tse-pratsiuye-i-komu-pidkhodyt',
        'vruchnu-chy-avtomatychno-kupivlya-monet-nbu' => 'kupivlia-monet-nbu-vruchnu-chy-avtomatychno-shcho-obraty',
    ];

    public function up(): void
    {
        foreach (['pages' => ['content'], 'modules' => null, 'site_settings' => null, 'menu_items' => ['url']] as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $columns ??= array_filter(Schema::getColumns($table), fn ($c) => in_array($c['type_name'], ['text', 'varchar', 'json', 'longtext'], true));
            $columns = array_map(fn ($c) => is_array($c) ? $c['name'] : $c, is_array($columns) ? $columns : []);

            foreach (DB::table($table)->get() as $row) {
                $updates = [];
                foreach ($columns as $column) {
                    $value = $row->{$column} ?? null;
                    if (! is_string($value) || $value === '') {
                        continue;
                    }
                    $new = $this->rewrite($value);
                    if ($new !== $value) {
                        $updates[$column] = $new;
                    }
                }
                if ($updates) {
                    DB::table($table)->where('id', $row->id)->update($updates);
                }
            }
        }
    }

    private function rewrite(string $text): string
    {
        // Довгі slug-и першими, щоб «yak-kupyty-monetu-nbu» не зачепив «…-pershym».
        $map = self::MAP;
        uksort($map, fn ($a, $b) => strlen($b) <=> strlen($a));

        return preg_replace_callback(
            '~(?<=["\'(/])/?('.implode('|', array_map(fn ($k) => preg_quote($k, '~'), array_keys($map))).')(?=["\')#?\s<])~',
            fn ($m) => str_starts_with($m[0], '/') ? '/'.$map[$m[1]] : $map[$m[1]],
            $text,
        );
    }

    public function down(): void
    {
        //
    }
};
