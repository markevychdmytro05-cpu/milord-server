<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Блок контактів у футері: заголовок, email-и з підписами, телефон, соцмережі й абзац про проєкт.
 * Верхня смуга шапки тепер вмикається окремо. Значення зразкові: замініть їх на справжні в адмінці.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('show_topbar')->default(false)->after('header_button_url');
            $table->string('footer_heading')->nullable()->after('footer_note');
            $table->text('footer_description')->nullable()->after('footer_heading');
            $table->string('contact_email_label', 40)->nullable()->after('contact_email');
            $table->string('contact_email2')->nullable()->after('contact_email_label');
            $table->string('contact_email2_label', 40)->nullable()->after('contact_email2');
            $table->string('instagram')->nullable()->after('viber');
            $table->string('facebook')->nullable()->after('instagram');
        });

        // Верхня смуга лишається ввімкненою, якщо вона вже показувалась.
        DB::table('site_settings')->update([
            'show_topbar' => DB::raw('CASE WHEN contact_email IS NOT NULL OR contact_phone IS NOT NULL OR contact_address IS NOT NULL THEN 1 ELSE 0 END'),
            'footer_heading' => 'Надсилайте свої запитання та пропозиції',
            'footer_description' => '<b>Numis</b> — програма для автоматичної купівлі монет НБУ через профілі AdsPower. Оформлення й оплата залишаються у вашому браузері, а ключ і профілі зберігаються локально в зашифрованому сховищі.',
            'contact_email_label' => 'Запитання',
            'contact_email2_label' => 'Співпраця',
        ]);

        DB::table('site_settings')->whereNull('contact_email')->update(['contact_email' => 'help@numis.example']);
        DB::table('site_settings')->whereNull('contact_email2')->update(['contact_email2' => 'partners@numis.example']);
        DB::table('site_settings')->whereNull('contact_phone')->update(['contact_phone' => '+380 44 000 00 00']);
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['show_topbar', 'footer_heading', 'footer_description', 'contact_email_label', 'contact_email2', 'contact_email2_label', 'instagram', 'facebook']);
        });
    }
};
