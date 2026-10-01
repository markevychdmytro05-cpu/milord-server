<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * Обкладинки статей бази знань (згенеровані ілюстрації в public/images/kb).
 */
return new class extends Migration
{
    private const COVERS = [
        'nalashtuvannya-adspower', 'kabinet-nbu', 'test-povedinky', 'cherha-kapcha-429',
        'yak-kupyty-monetu-nbu-pershym', 'probnyi-period', 'licenziya-ta-pristroyi', 'bezpeka-danykh',
    ];

    public function up(): void
    {
        foreach (self::COVERS as $slug) {
            Page::where('slug', $slug)->whereNull('og_image')->update(['og_image' => '/images/kb/'.$slug.'.jpg']);
        }
    }

    public function down(): void
    {
        foreach (self::COVERS as $slug) {
            Page::where('slug', $slug)->where('og_image', '/images/kb/'.$slug.'.jpg')->update(['og_image' => null]);
        }
    }
};
