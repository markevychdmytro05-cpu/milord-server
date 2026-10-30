<?php

namespace App\Services;

use App\Models\LanguageLine;
use App\Support\SiteLocale;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class TranslationSyncService
{
    public function importFromFiles(): array
    {
        $created = $updated = 0;

        foreach ($this->fileLines() as $group => $locales) {
            foreach ($locales as $locale => $lines) {
                foreach ($lines as $key => $value) {
                    $line = LanguageLine::where('group', $group)->where('key', $key)->first();

                    if (! $line) {
                        LanguageLine::create(['group' => $group, 'key' => $key, 'text' => [$locale => $value]]);
                        $created++;
                    } elseif (! array_key_exists($locale, $line->text ?? [])) {
                        $line->setTranslation($locale, $value)->save();
                        $updated++;
                    }
                }
            }
        }

        return ['created' => $created, 'updated' => $updated];
    }

    public function exportToFiles(): int
    {
        $files = [];

        foreach (LanguageLine::query()->orderBy('group')->orderBy('key')->get() as $line) {
            if ($line->group === '*') {
                continue;
            }

            foreach ($line->text ?? [] as $locale => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $path = lang_path("{$locale}/{$line->group}.php");
                $files[$path] ??= File::exists($path) ? (array) require $path : [];
                Arr::set($files[$path], $line->key, $value);
            }
        }

        foreach ($files as $path => $lines) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, "<?php\n\nreturn ".$this->export($lines).";\n");
        }

        return count($files);
    }

    public function fileLines(): array
    {
        $result = [];

        foreach (SiteLocale::all() as $locale) {
            foreach (File::glob(lang_path("{$locale}/*.php")) ?: [] as $file) {
                $lines = array_filter(Arr::dot((array) require $file), fn ($value): bool => is_string($value) && $value !== '');

                if ($lines !== []) {
                    $result[basename($file, '.php')][$locale] = $lines;
                }
            }
        }

        return $result;
    }

    private function export(array $value, int $level = 0): string
    {
        $indent = str_repeat('    ', $level + 1);
        $lines = [];
        $isList = array_is_list($value);

        foreach ($value as $key => $item) {
            $prefix = $isList ? '' : (is_int($key) ? $key : $this->quote((string) $key)).' => ';
            $lines[] = $indent.$prefix.(is_array($item) ? $this->export($item, $level + 1) : $this->quote((string) $item)).',';
        }

        return $lines === [] ? '[]' : "[\n".implode("\n", $lines)."\n".str_repeat('    ', $level).']';
    }

    private function quote(string $text): string
    {
        return "'".str_replace(['\\', "'"], ['\\\\', "\\'"], $text)."'";
    }
}
