<?php

use App\Models\MenuItem;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const LINKS = [
        '/monety-nbu' => 'Монети НБУ',
        '/zavantazhyty' => 'Завантажити',
    ];

    public function up(): void
    {
        foreach (self::LINKS as $url => $title) {
            if (MenuItem::where('menu', MenuItem::FOOTER)->where('url', $url)->exists()) {
                continue;
            }

            $position = (int) MenuItem::where('menu', MenuItem::FOOTER)->max('rgt');

            MenuItem::create([
                'menu' => MenuItem::FOOTER, 'is_active' => true, 'title' => $title, 'url' => $url,
                'depth' => 1, 'lft' => ++$position, 'rgt' => ++$position,
            ]);
        }
    }

    public function down(): void
    {
        MenuItem::where('menu', MenuItem::FOOTER)->whereIn('url', array_keys(self::LINKS))->delete();
    }
};
