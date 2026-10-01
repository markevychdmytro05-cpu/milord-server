<?php

namespace App\Console\Commands;

use App\Models\Coin;
use App\Services\CommonsCoinImageService;
use App\Services\NbuCoinNewsService;
use App\Support\CoinImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class ImportCoinImages extends Command
{
    protected $signature = 'coins:import-images {--dry-run : Лише показати, яка новина підходить до кожної монети} {--no-commons : Не шукати фото на Wikimedia Commons} {--recent=900 : Скільки останніх записів sitemap НБУ переглядати}';

    protected $description = 'Підтягує фото й короткий опис монет із новин НБУ на bank.gov.ua (лише для монет без фото)';

    private const EXTENSIONS = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    public function handle(NbuCoinNewsService $news, CommonsCoinImageService $commons): int
    {
        $coins = Coin::query()->where(fn ($query) => $query->whereNull('image')->orWhere('image', ''))->orderBy('id')->get();

        if ($coins->isEmpty()) {
            $this->info('Усі монети вже мають фото.');

            return self::SUCCESS;
        }

        $urls = $news->candidateUrls((int) $this->option('recent'));
        $this->info('Новин НБУ для перегляду: '.count($urls).' (перший запуск довший, далі береться з кешу)');

        $bar = $this->output->createProgressBar(count($urls));
        $articles = $news->articles($urls, fn (int $step) => $bar->advance($step));
        $bar->finish();
        $this->newLine(2);

        // Каталог обігових монет іде першим: у ньому є чисті зображення й точні характеристики.
        $catalog = $news->circulationCatalog();
        $this->info('Монет у каталозі обігових монет НБУ: '.count($catalog));
        $articles = [...$catalog, ...$articles];

        $imported = $skipped = 0;

        foreach ($coins as $coin) {
            $article = $news->match($coin, $articles);

            if (! $article) {
                $this->line("– {$coin->shortTitle()}: новини не знайдено");
                $skipped++;

                continue;
            }

            $this->line("✓ {$coin->shortTitle()} ← {$article['title']}");

            if ($this->option('dry-run')) {
                continue;
            }

            if ($path = $this->download($coin, (string) $article['image'])) {
                $data = ['image' => $path, 'image_source' => $article['url'], 'image_credit' => 'Національний банк України'];

                if ($coin->isThin()) {
                    $data['content'] = $this->describe($article, $news->lead($article));
                }

                $coin->update($data);
                $imported++;
            } else {
                $this->warn('  фото не вдалося завантажити');
                $skipped++;
            }
        }

        if (! $this->option('no-commons')) {
            $this->line('');
            $this->info('Wikimedia Commons для монет без фото:');

            foreach (Coin::query()->where(fn ($query) => $query->whereNull('image')->orWhere('image', ''))->orderBy('id')->get() as $coin) {
                if (! $coin->hasBeenReleased()) {
                    continue;
                }

                $file = $commons->find($coin);

                if (! $file) {
                    continue;
                }

                $this->line("✓ {$coin->shortTitle()} ← {$file['title']} [{$file['credit']}]");

                if ($this->option('dry-run')) {
                    continue;
                }

                if ($path = $this->download($coin, $file['url'])) {
                    $coin->update(['image' => $path, 'image_source' => $file['page'], 'image_credit' => $file['credit']]);
                    $imported++;
                } else {
                    $this->warn('  фото не вдалося завантажити');
                }
            }
        }

        $this->info("Фото додано: {$imported}, без фото: ".Coin::query()->where(fn ($query) => $query->whereNull('image')->orWhere('image', ''))->count().'.');

        return self::SUCCESS;
    }

    /** Зберігає фото в public/uploads/coins і повертає шлях від кореня сайту. */
    private function download(Coin $coin, string $imageUrl): ?string
    {
        $response = Http::withUserAgent('NumisCoinCatalog/1.0 (https://t.me/NumisNBU)')->timeout(60)->retry(2, 2000, throw: false)->get($imageUrl);
        $extension = self::EXTENSIONS[strtolower(explode(';', (string) $response->header('Content-Type'))[0])] ?? null;

        if (! $response->ok() || ! $extension || strlen($response->body()) > 8 * 1024 * 1024) {
            return null;
        }

        File::ensureDirectoryExists(public_path('uploads/coins'));

        $optimized = CoinImage::optimize($response->body());
        $extension = $optimized === null ? $extension : 'jpg';
        File::put(public_path("uploads/coins/{$coin->slug}.{$extension}"), $optimized ?? $response->body());

        return "/uploads/coins/{$coin->slug}.{$extension}";
    }

    /**
     * @param  array{url: string, title: string, description: string, image: ?string, text: string, facts?: array<string, string>}  $article
     */
    private function describe(array $article, string $lead): string
    {
        $monitor = route('services.show', 'rezym-sposterezennia', false);
        $calendar = route('pages.show', 'kalendar-vypusku-monet-nbu-2026', false);
        $source = '<a href="'.e($article['url']).'" rel="nofollow noopener" target="_blank">Джерело: bank.gov.ua</a>';

        $wanted = ['Дата введення в обіг', 'Діаметр, мм', 'Товщина монети, мм', 'Вага, г', 'Гурт', 'Художник', 'Скульптор'];
        $facts = array_intersect_key($article['facts'] ?? [], array_flip($wanted));

        $intro = $facts === []
            ? '<p>За повідомленням Національного банку України: «'.e($lead).'» '.$source.'.</p>'
            : '<p>Дані каталогу Національного банку України ('.$source.'):</p><ul>'.implode('', array_map(
                fn (string $key, string $value): string => '<li>'.e($key).': '.e($value).'</li>',
                array_keys($facts),
                $facts,
            )).'</ul>';

        return $intro
            .'<h2>Як не пропустити появу монети в магазині НБУ</h2>'
            .'<p>Точну дату й час початку продажу, а також ціну НБУ оголошує окремо. Щоб не пропустити появу кнопки «Купити», скористайтеся <a href="'.$monitor.'">режимом спостереження</a> в Numis. Усі випуски року – у <a href="'.$calendar.'">календарі монет НБУ 2026</a>.</p>';
    }
}
