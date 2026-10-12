<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Database\Factories\ReleaseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class Release extends Model
{
    /** @use HasFactory<ReleaseFactory> */
    use CrudTrait, HasFactory;

    public const DISK = 'local';

    public const PLATFORMS = ['win' => 'Windows', 'mac' => 'macOS', 'linux' => 'Linux'];

    public const ARCHES = ['x64' => 'x64', 'arm64' => 'ARM64'];

    public const VERSION_PATTERN = '/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/';

    protected $fillable = [
        'version', 'platform', 'arch', 'file_path', 'file_name', 'size', 'sha256',
        'notes', 'is_active', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'is_active' => 'boolean',
            'published_at' => 'datetime',
            'downloads_count' => 'integer',
        ];
    }

    /** @param  Builder<Release>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public static function latestFor(string $platform, string $arch): ?self
    {
        return static::active()->where('platform', $platform)->where('arch', $arch)->get()
            ->sort(fn (self $a, self $b): int => version_compare($b->version, $a->version))->first();
    }

    /**
     * Найновіша активна збірка для кожної пари «платформа + архітектура».
     *
     * @return Collection<int, Release>
     */
    public static function latestPerTarget(): Collection
    {
        return static::active()->get()->groupBy(fn (self $release): string => $release->target())
            ->map(fn (Collection $group): self => $group->sort(fn (self $a, self $b): int => version_compare($b->version, $a->version))->first())
            ->sortBy(fn (self $release): string => array_search($release->platform, array_keys(self::PLATFORMS), true).$release->arch)
            ->values();
    }

    /**
     * Збірка, яку варто запропонувати клієнту: новіша за його версію й доступна для його платформи.
     */
    public static function offerFor(?string $platform, ?string $arch, ?string $currentVersion): ?self
    {
        if (! isset(self::PLATFORMS[$platform]) || ! isset(self::ARCHES[$arch]) || ! $currentVersion) {
            return null;
        }

        $latest = static::latestFor($platform, $arch);

        return $latest && version_compare($latest->version, $currentVersion, '>') ? $latest : null;
    }

    public function target(): string
    {
        return $this->platform.'-'.$this->arch;
    }

    public function label(): string
    {
        return self::PLATFORMS[$this->platform].' '.self::ARCHES[$this->arch];
    }

    public function sizeLabel(): string
    {
        return number_format($this->size / 1048576, 1, ',', ' ').' МБ';
    }

    public function temporaryUrl(int $minutes): string
    {
        return URL::temporarySignedRoute('downloads.file', now()->addMinutes($minutes), ['release' => $this->id]);
    }

    public function fileExists(): bool
    {
        return Storage::disk(self::DISK)->exists($this->file_path);
    }
}
