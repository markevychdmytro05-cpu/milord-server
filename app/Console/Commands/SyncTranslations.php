<?php

namespace App\Console\Commands;

use App\Services\TranslationSyncService;
use Illuminate\Console\Command;

class SyncTranslations extends Command
{
    protected $signature = 'translations:sync';

    protected $description = 'Додає в базу ключі перекладів із файлів lang/ (наявні правки з адмінки не змінюються)';

    public function handle(TranslationSyncService $service): int
    {
        $result = $service->importFromFiles();
        $this->info("Створено рядків: {$result['created']}, доповнено мовами: {$result['updated']}.");

        return self::SUCCESS;
    }
}
