<?php

namespace App\Console\Commands;

use App\Models\Release;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PublishRelease extends Command
{
    protected $signature = 'release:publish
        {file : Шлях до інсталятора, наприклад nbu-desktop-0.2.0-win-x64.exe}
        {--notes= : Що нового (показується на сайті й у програмі)}
        {--activate : Одразу зробити реліз доступним}';

    protected $description = 'Додає інсталятор програми до релізів (версію, платформу й архітектуру бере з імені файлу)';

    public function handle(): int
    {
        $path = (string) $this->argument('file');

        if (! is_file($path)) {
            $this->error('Файл не знайдено: '.$path);

            return self::FAILURE;
        }

        $name = basename($path);

        if (! preg_match('/^nbu-desktop-(?<version>\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?)-(?<platform>win|mac|linux)-(?<arch>x64|arm64)\.[A-Za-z0-9.]+$/', $name, $parts)) {
            $this->error('Ім\'я файлу має бути nbu-desktop-<версія>-<win|mac|linux>-<x64|arm64>.<розширення>.');

            return self::FAILURE;
        }

        if (Release::where(['version' => $parts['version'], 'platform' => $parts['platform'], 'arch' => $parts['arch']])->exists()) {
            $this->error('Такий реліз уже опубліковано. Видаліть його в адмінці або збільште версію.');

            return self::FAILURE;
        }

        $target = 'releases/'.$parts['version'].'/'.$name;
        $stream = fopen($path, 'rb');
        $stored = Storage::disk(Release::DISK)->put($target, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        if (! $stored) {
            $this->error('Не вдалося зберегти файл на диску.');

            return self::FAILURE;
        }

        $release = Release::create([
            'version' => $parts['version'],
            'platform' => $parts['platform'],
            'arch' => $parts['arch'],
            'file_path' => $target,
            'file_name' => $name,
            'size' => filesize($path),
            'sha256' => hash_file('sha256', $path),
            'notes' => $this->option('notes'),
            'is_active' => (bool) $this->option('activate'),
            'published_at' => $this->option('activate') ? now() : null,
        ]);

        $this->info(sprintf('Реліз %s (%s) додано, sha256 %s. %s', $release->version, $release->label(), $release->sha256,
            $release->is_active ? 'Доступний на сайті й у програмі.' : 'Увімкніть його в адмінці, коли будете готові.'));

        return self::SUCCESS;
    }
}
