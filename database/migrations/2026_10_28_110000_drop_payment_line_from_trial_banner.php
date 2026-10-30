<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const NAME = 'SEO – Заклик: пробний період';

    private const OLD = 'Залиште заявку, і ми видамо пробний ключ. Оформлення й оплата залишаються у вашому браузері.';

    private const NEW = 'Залиште заявку, і ми видамо пробний ключ.';

    public function up(): void
    {
        $module = Module::where('name', self::NAME)->first();

        if ($module === null) {
            return;
        }

        $setting = $module->setting->toArray();

        if (($setting['uk']['text'] ?? null) !== self::OLD) {
            $this->command?->warn('Текст банера пробного періоду вже змінено в адмінці, тож його не чіпаємо.');

            return;
        }

        $setting['uk']['text'] = self::NEW;
        $module->update(['setting' => $setting]);
    }

    public function down(): void
    {
    }
};
