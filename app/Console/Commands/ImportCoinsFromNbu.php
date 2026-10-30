<?php

namespace App\Console\Commands;

use App\Models\Coin;
use App\Services\NbuCoinNewsService;
use Illuminate\Console\Command;

class ImportCoinsFromNbu extends Command
{
    protected $signature = 'coins:import-from-nbu {--publish : Одразу опублікувати нові монети}';

    protected $description = 'Створює/оновлює монети з каталогу обігових монет НБУ (bank.gov.ua)';

    public function handle(NbuCoinNewsService $news): int
    {
        $catalog = $news->circulationCatalog();

        if ($catalog === []) {
            $this->error('Не вдалося отримати каталог монет із bank.gov.ua.');

            return self::FAILURE;
        }

        $this->info('Монет у каталозі: '.count($catalog));

        $created = $updated = 0;

        foreach ($catalog as $entry) {
            $facts = $entry['facts'];
            $title = $entry['title'];

            $denomination = null;
            if (preg_match('~(\d+)\s*гривен~u', $title, $m)) {
                $denomination = $m[1].' грн';
            }

            $saleStartsAt = null;
            if (! empty($facts['Дата введення в обіг']) && preg_match('~^(\d{2})\.(\d{2})\.(\d{4})$~', trim($facts['Дата введення в обіг']), $d)) {
                $saleStartsAt = "{$d[3]}-{$d[2]}-{$d[1]}";
            }

            $mintage = null;
            if (! empty($facts['Тираж, шт.'])) {
                $mintage = (int) preg_replace('~\D~u', '', $facts['Тираж, шт.']);
            }

            $data = array_filter([
                'denomination' => $denomination,
                'metal' => $facts['Метал'] ?? null,
                'mintage' => $mintage,
                'sale_starts_at' => $saleStartsAt,
                'image' => null,
                'image_source' => $entry['url'] ?? null,
                'image_credit' => 'Національний банк України',
            ], fn ($value) => $value !== null);

            $coin = Coin::firstWhere('title', $title);

            if ($coin) {
                $coin->update($data);
                $updated++;

                continue;
            }

            Coin::create($data + [
                'title' => $title,
                'is_published' => (bool) $this->option('publish'),
                'show_on_home' => false,
            ]);
            $created++;
        }

        $this->info("Створено: {$created}, оновлено: {$updated}.");

        return self::SUCCESS;
    }
}
