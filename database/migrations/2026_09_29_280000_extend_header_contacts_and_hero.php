<?php

use App\Models\Module;
use App\Models\ModuleTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('contact_email')->nullable()->after('footer_note');
            $table->string('contact_phone', 30)->nullable()->after('contact_email');
            $table->string('contact_address')->nullable()->after('contact_phone');
            $table->string('header_button_text', 40)->nullable()->after('header_logo');
            $table->string('header_button_url')->nullable()->after('header_button_text');
        });

        DB::table('site_settings')->update(['header_button_text' => 'Зв’язатися', 'header_button_url' => '/#contact']);

        $this->extendHeroTemplate();
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['contact_email', 'contact_phone', 'contact_address', 'header_button_text', 'header_button_url']);
        });
    }

    /** Hero отримує пункти-списком, другу кнопку та зображення праворуч. */
    private function extendHeroTemplate(): void
    {
        $template = ModuleTemplate::byCode('hero');

        if (! $template) {
            return;
        }

        $config = $template->template->toArray();
        $config['fields'] += [
            'image' => ['label' => 'Зображення праворуч', 'input' => 'browse'],
            'button2_url' => ['label' => 'Адреса другої кнопки', 'input' => 'text'],
        ];
        $config['rules'] += [
            'image' => ['nullable', 'string', 'max:2048'],
            'button2_url' => ['nullable', 'string', 'max:255', 'regex:~^(/|#|https?://|mailto:|tel:)~i'],
        ];
        $config['lang_fields'] += [
            'points' => ['label' => 'Пункти (кожен з нового рядка)', 'input' => 'textarea'],
            'button2_text' => ['label' => 'Текст другої кнопки', 'input' => 'text'],
        ];
        $config['lang_rules'] += [
            'points' => 'nullable|string|max:600',
            'button2_text' => 'nullable|string|max:80',
        ];

        $template->update(['template' => $config]);

        $hero = Module::where('name', 'Головна – Hero')->first();

        if ($hero) {
            $setting = $hero->setting->toArray();
            $setting['button2_url'] = '#features';
            $setting['image'] = '';
            $setting['uk']['points'] = "Старт за київським часом\nКілька профілів AdsPower одночасно\nОплата у вашому браузері";
            $setting['uk']['button2_text'] = 'Можливості';
            $hero->update(['setting' => $setting]);
        }
    }
};
