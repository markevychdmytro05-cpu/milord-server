<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

/**
 * Повідомляє пошуковики з підтримкою IndexNow (Bing, Yandex та інші) про нові чи змінені адреси,
 * щоб вони заходили на сторінки одразу, а не через кілька днів. Ключ задається в .env: INDEXNOW_KEY.
 */
class SubmitToIndexNow implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 300];

    /** @param  list<string>  $urls */
    public function __construct(public array $urls) {}

    public function handle(): void
    {
        $key = config('services.indexnow.key');

        if (! $key || $this->urls === []) {
            return;
        }

        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        // IndexNow приймає до 10 000 адрес за запит.
        foreach (array_chunk($this->urls, 10000) as $chunk) {
            Http::timeout(15)
                ->post('https://api.indexnow.org/IndexNow', [
                    'host' => $host,
                    'key' => $key,
                    'keyLocation' => rtrim((string) config('app.url'), '/').'/'.$key.'.txt',
                    'urlList' => $chunk,
                ])
                ->throw();
        }
    }
}
