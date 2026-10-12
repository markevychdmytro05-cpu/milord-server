<?php

use App\Models\ModuleTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Читабельніший скріншот у hero; у шапці не дублюється пункт «Контакти», бо є кнопка «Спробувати безкоштовно». */
return new class extends Migration
{
    public function up(): void
    {
        $hero = ModuleTemplate::byCode('hero')?->modules()->where('name', 'Головна – Hero')->first();

        if ($hero) {
            $setting = $hero->setting->toArray();
            $setting['image'] = '/images/app/numis-tasks-live.png';
            $setting['uk']['image_alt'] = 'Журнал завдань Numis: відлік до старту за київським часом і профілі AdsPower';
            $hero->update(['setting' => $setting]);
        }

        DB::table('menu_items')->where('menu', 'header')->where('url', '/#contact')->update(['is_active' => false]);
    }

    public function down(): void
    {
        //
    }
};
