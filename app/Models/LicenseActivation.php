<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseActivation extends Model
{
    use CrudTrait;

    protected $fillable = ['license_id', 'device_id', 'device_public_key', 'device_name', 'app_version', 'ip', 'last_seen_at'];

    protected static function booted(): void
    {
        static::created(function (LicenseActivation $activation): void {
            LicenseAudit::record($activation->license, 'device_activated', metadata: [
                'device_id' => $activation->device_id,
                'device_name' => $activation->device_name,
            ]);
        });

        static::deleting(function (LicenseActivation $activation): void {
            LicenseAudit::record($activation->license, 'device_unbound', metadata: [
                'device_id' => $activation->device_id,
                'device_name' => $activation->device_name,
            ]);
        });
    }

    protected function casts(): array
    {
        return ['last_seen_at' => 'datetime', 'accounts_used' => 'integer'];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}
