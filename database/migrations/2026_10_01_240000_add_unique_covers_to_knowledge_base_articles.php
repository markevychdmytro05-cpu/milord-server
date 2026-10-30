<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const COVERS = [
        'adspower-shcho-tse-i-iak-stvoryty-pershyi-profil' => 'adspower-pershyi-profil',
        'proksi-v-adspower-iak-nalashtuvaty-i-pereviryty' => 'proksi-adspower',
        'adspower-local-api-adresa-kliuch-perevirka' => 'adspower-local-api',
        'adspower-ne-bachyt-proksi-abo-ne-zapuskaietsia-chek-lyst' => 'adspower-chek-lyst',
        'skilky-profiliv-adspower-potribno-dlia-kupivli-monet-nbu' => 'skilky-profiliv',
        'iak-kupyty-monetu-v-internet-mahazyni-nbu-pokrokova-instruktsiia' => 'yak-kupyty-moneta',
        'bot-dlia-kupivli-monet-nbu-shcho-vmiye-numis-a-shcho-ni' => 'bot-numis',
        'moneta-ne-dodayetsia-v-koshyk-prychyny-y-shcho-robyty' => 'ne-dodaietsia-v-koshyk',
        'avtokupivlia-monet-nbu-iak-tse-pratsiuye-i-komu-pidkhodyt' => 'avtokupivlia',
        'kupivlia-monet-nbu-vruchnu-chy-avtomatychno-shcho-obraty' => 'vruchnu-chy-avto',
    ];

    public function up(): void
    {
        foreach (self::COVERS as $slug => $file) {
            DB::table('pages')->where('type', 'page')->where('slug', $slug)->update(['og_image' => "/images/kb/{$file}.jpg"]);
        }
    }

    public function down(): void
    {
    }
};
