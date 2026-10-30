<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const OLD = 'Акаунтом вважається кожен профіль AdsPower у налаштуваннях і активних завданнях.';

    private const NEW = 'Акаунтом вважається кожен профіль антидетект-браузера в налаштуваннях і активних завданнях.';

    public function up(): void
    {
        $module = Module::where('name', 'Головна – FAQ')->first();

        if ($module === null) {
            return;
        }

        $json = json_encode($module->setting->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (! str_contains($json, self::OLD)) {
            return;
        }

        $module->update(['setting' => json_decode(str_replace(self::OLD, self::NEW, $json), true)]);
    }

    public function down(): void
    {
    }
};
