<?php

use App\Models\MenuItem;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (MenuItem::where('menu', MenuItem::HEADER)->where('url', '/zavantazhyty')->exists()) {
            return;
        }

        $position = (int) MenuItem::where('menu', MenuItem::HEADER)->max('rgt');

        MenuItem::create([
            'menu' => MenuItem::HEADER, 'is_active' => true, 'title' => 'Завантажити', 'url' => '/zavantazhyty',
            'depth' => 1, 'lft' => ++$position, 'rgt' => ++$position,
        ]);
    }

    public function down(): void
    {
        MenuItem::where('menu', MenuItem::HEADER)->where('url', '/zavantazhyty')->delete();
    }
};
