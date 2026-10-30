<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const NAME = 'Головна – Як це працює';

    private const OLD = 'Підключіть AdsPower, збережіть профілі й вкажіть посилання на монету та час старту.';

    private const NEW = 'Підключіть антидетект-браузер, збережіть профілі й вкажіть посилання на монету та час старту.';

    public function up(): void
    {
        $module = Module::where('name', self::NAME)->first();

        if ($module === null) {
            return;
        }

        $json = json_encode($module->setting->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (! str_contains($json, self::OLD)) {
            $this->command?->warn('Текст кроку «Додайте профілі» вже змінено в адмінці, тож його не чіпаємо.');

            return;
        }

        $module->update(['setting' => json_decode(str_replace(self::OLD, self::NEW, $json), true)]);
    }

    public function down(): void
    {
    }
};
