<?php

namespace App\Console\Commands;

use App\Jobs\SubmitToIndexNow;
use App\Models\Coin;
use App\Models\Page;
use Illuminate\Console\Command;

class SubmitIndexNow extends Command
{
    protected $signature = 'seo:indexnow {--changed : Лише сторінки, змінені за останню добу}';

    protected $description = 'Надіслати в IndexNow усі індексовані адреси сайту (після запуску) або лише змінені за добу (--changed)';

    public function handle(): int
    {
        if (! config('services.indexnow.key')) {
            $this->error('Не задано INDEXNOW_KEY у .env.');

            return self::FAILURE;
        }

        $since = $this->option('changed') ? now()->subHours(25) : null;

        $pages = Page::published()->indexable()->when($since, fn ($query) => $query->where('updated_at', '>=', $since))->get();
        $coins = Coin::published()->withImage()->indexable()->when($since, fn ($query) => $query->where('updated_at', '>=', $since))->get();

        $urls = $pages->map(fn (Page $page): string => $page->url())
            ->merge($coins->map(fn (Coin $coin): string => $coin->url()))
            ->when(! $since, fn ($urls) => $urls->push(route('coins.index')))
            ->unique()->values()->all();

        if ($urls === []) {
            $this->info('Змін за добу немає, надсилати нічого.');

            return self::SUCCESS;
        }

        SubmitToIndexNow::dispatchSync($urls);

        $this->info(count($urls).' адрес надіслано.');

        return self::SUCCESS;
    }
}
