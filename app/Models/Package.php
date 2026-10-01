<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Database\Factories\PackageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    /** @use HasFactory<PackageFactory> */
    use CrudTrait, HasFactory;

    protected $fillable = ['name', 'max_accounts', 'max_devices', 'price', 'duration_days', 'is_active', 'show_on_home'];

    protected function casts(): array
    {
        return [
            'max_accounts' => 'integer',
            'max_devices' => 'integer',
            'price' => 'integer',
            'duration_days' => 'integer',
            'is_active' => 'boolean',
            'show_on_home' => 'boolean',
        ];
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    /** @param  Builder<Package>  $query */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('price');
    }

    /** @param  Builder<Package>  $query */
    public function scopeForHome(Builder $query): void
    {
        $query->available()->where('show_on_home', true);
    }

    public function isTrial(): bool
    {
        return $this->price === 0 && $this->duration_days > 0;
    }

    public function durationLabel(): string
    {
        return $this->duration_days ? __('site.package.days', ['count' => $this->duration_days]) : __('site.package.unlimited');
    }

    public function getLabelAttribute(): string
    {
        return "{$this->name} – {$this->max_accounts} акаунтів, {$this->durationLabel()}, \${$this->price}";
    }
}
