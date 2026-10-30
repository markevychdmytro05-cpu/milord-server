<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const NAME = 'Головна – FAQ';

    private const REPLACEMENTS = [
        'Як програма працює з AdsPower?' => 'Як програма працює з антидетект-браузером?',
        'Numis підключається до локального API AdsPower, перевіряє' => 'Numis підключається до локального API антидетект-браузера, перевіряє',
    ];

    public function up(): void
    {
        $module = Module::where('name', self::NAME)->first();

        if ($module === null) {
            return;
        }

        $json = json_encode($module->setting->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        foreach (self::REPLACEMENTS as $old => $new) {
            if (! str_contains($json, $old)) {
                $this->command?->warn("Фрагмент «{$old}» уже змінено в адмінці, тож його не чіпаємо.");

                continue;
            }

            $json = str_replace($old, $new, $json);
        }

        $module->update(['setting' => json_decode($json, true)]);
    }

    public function down(): void
    {
    }
};
