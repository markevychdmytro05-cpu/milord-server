<?php

namespace App\Services;

use App\Models\ModuleTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ModuleService
{
    public const DISK = 'public';

    /**
     * Зберігає завантажені файли полів image/upload і повертає шляхи до них.
     * Поля без нового файлу з $input прибираються, щоб при оновленні лишилось попереднє значення.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function saveUploads(array $input, ModuleTemplate $template): array
    {
        foreach ($template->uploadFields() as $field) {
            $file = $input[$field] ?? null;

            if ($file instanceof UploadedFile && $file->isValid()) {
                $input[$field] = $file->storePublicly('modules/'.$template->code, self::DISK);
            } else {
                unset($input[$field]);
            }
        }

        return $input;
    }

    /** Публічна адреса збереженого файлу; повний URL повертається як є. */
    public function url(?string $path): string
    {
        if (! $path) {
            return '';
        }

        return str_starts_with($path, 'http') || str_starts_with($path, '/') ? $path : Storage::disk(self::DISK)->url($path);
    }
}
