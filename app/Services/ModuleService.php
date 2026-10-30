<?php

namespace App\Services;

use App\Models\ModuleTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ModuleService
{
    public const DISK = 'public';

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

    public function url(?string $path): string
    {
        if (! $path) {
            return '';
        }

        return str_starts_with($path, 'http') || str_starts_with($path, '/') ? $path : Storage::disk(self::DISK)->url($path);
    }
}
