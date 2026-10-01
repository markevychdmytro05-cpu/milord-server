<?php

namespace App\Console\Commands;

use App\Services\TranslationSyncService;
use Illuminate\Console\Command;

class ExportTranslations extends Command
{
    protected $signature = 'translations:export';

    protected $description = 'Записує переклади з бази у файли lang/{мова}/{група}.php, щоб їх можна було закомітити';

    public function handle(TranslationSyncService $service): int
    {
        $this->info('Записано файлів: '.$service->exportToFiles());

        return self::SUCCESS;
    }
}
