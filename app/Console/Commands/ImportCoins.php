<?php

namespace App\Console\Commands;

use App\Models\Coin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class ImportCoins extends Command
{
    protected $signature = 'coins:import {file : Шлях до JSON-файлу з монетами} {--publish : Одразу опублікувати нові монети}';

    protected $description = 'Імпортує монети з JSON (наприклад, з плану випуску НБУ): нові додає, наявні оновлює лише технічні поля';

    public function handle(): int
    {
        $file = (string) $this->argument('file');
        $rows = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

        if (! is_array($rows)) {
            $this->error('Файл не знайдено або він не є коректним JSON-масивом.');

            return self::FAILURE;
        }

        $created = $updated = $skipped = 0;

        foreach ($rows as $index => $row) {
            $validator = Validator::make(is_array($row) ? $row : [], [
                'title' => ['required', 'string', 'max:255'],
                'denomination' => ['nullable', 'string', 'max:50'],
                'series' => ['nullable', 'string', 'max:255'],
                'metal' => ['nullable', 'string', 'max:100'],
                'release_year' => ['nullable', 'integer', 'between:1990,2100'],
                'release_month' => ['nullable', 'integer', 'between:1,12'],
                'mintage' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
                'excerpt' => ['nullable', 'string', 'max:1000'],
            ]);

            if ($validator->fails()) {
                $this->warn('Рядок '.($index + 1).' пропущено: '.$validator->errors()->first());
                $skipped++;

                continue;
            }

            $data = $validator->validated();
            $coin = Coin::firstWhere(['title' => $data['title'], 'release_year' => $data['release_year'] ?? null]);

            if ($coin) {
                $coin->update(collect($data)->only(['denomination', 'series', 'metal', 'mintage', 'release_month'])->all());
                $updated++;

                continue;
            }

            Coin::create($data + ['is_published' => (bool) $this->option('publish')]);
            $created++;
        }

        $this->info("Створено: {$created}, оновлено: {$updated}, пропущено: {$skipped}.");

        return $skipped > 0 ? self::FAILURE : self::SUCCESS;
    }
}
