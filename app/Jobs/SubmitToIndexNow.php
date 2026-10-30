<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class SubmitToIndexNow implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 300];

    public function __construct(public array $urls) {}

    public function handle(): void
    {
        $key = config('services.indexnow.key');

        if (! $key || $this->urls === []) {
            return;
        }

        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

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
