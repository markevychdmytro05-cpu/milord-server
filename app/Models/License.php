<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class License extends Model
{
    use CrudTrait, HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REVOKED = 'revoked';

    public const CONTACT_APP_SCHEMES = ['tg', 'viber', 'whatsapp', 'skype', 'signal', 'tel', 'sms'];

    protected $fillable = [
        'package_id', 'customer', 'contact', 'max_accounts', 'max_devices',
        'price', 'status', 'expires_at', 'note',
    ];

    protected function casts(): array
    {
        return [
            'max_accounts' => 'integer',
            'max_devices' => 'integer',
            'price' => 'integer',
            'expires_at' => 'datetime',
            'expiry_reminded_for' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (License $license) {
            $license->key ??= static::generateKey();
            $license->created_by ??= auth('backpack')->id();
            $license->fillFromPackage();
        });

        static::updating(function (License $license) {
            if ($license->isDirty('package_id') && $package = Package::find($license->package_id)) {
                $license->max_accounts = $package->max_accounts;
                $license->max_devices = $package->max_devices;

                if (! $license->isDirty('price')) {
                    $license->price = $package->price;
                }

                $license->setRelation('package', $package);
            }
        });

        static::saving(function (License $license) {
            if ($license->price === null && $license->package_id) {
                $license->price = Package::find($license->package_id)?->price;
            }
        });

        static::created(function (License $license): void {
            $license->refresh();
            $changes = [];

            foreach ($license->getFillable() as $field) {
                $changes[$field] = ['old' => null, 'new' => $license->getAttributes()[$field] ?? null];
            }

            LicenseAudit::record($license, 'created', $changes);
        });

        static::updated(function (License $license): void {
            $changes = [];

            foreach (array_intersect_key($license->getChanges(), array_flip($license->getFillable())) as $field => $value) {
                $changes[$field] = ['old' => $license->getRawOriginal($field), 'new' => $value];
            }

            if ($changes !== []) {
                LicenseAudit::record($license, 'updated', $changes);
            }
        });

        static::deleting(function (License $license): void {
            LicenseAudit::record($license, 'deleted', metadata: ['customer' => $license->customer]);
        });
    }

    public static function generateKey(): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

        do {
            $chars = '';
            for ($i = 0; $i < 16; $i++) {
                $chars .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $key = implode('-', str_split($chars, 4));
        } while (static::where('key', $key)->exists());

        return $key;
    }

    public static function normalizeKey(string $key): string
    {
        $chars = preg_replace('/[^A-Z0-9]/', '', Str::upper($key));

        return implode('-', str_split($chars, 4));
    }

    public function activations(): HasMany
    {
        return $this->hasMany(LicenseActivation::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LicensePayment::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(LicenseAudit::class);
    }

    public function allowsActivation(LicenseActivation $activation): bool
    {
        return $this->activations()->where('id', '<=', $activation->id)->count() <= $this->max_devices;
    }

    public function fillFromPackage(): void
    {
        if (! $package = $this->package) {
            return;
        }

        $this->max_accounts ??= $package->max_accounts;
        $this->max_devices ??= $package->max_devices;
        $this->price ??= $package->price;

        if ($this->expires_at === null && $package->duration_days) {
            $this->expires_at = now()->addDays($package->duration_days);
        }
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contactUrl(): ?string
    {
        $contact = trim((string) $this->contact);

        if ($contact === '') {
            return null;
        }

        if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            return 'mailto:'.$contact;
        }

        if (str_starts_with($contact, '@')) {
            return preg_match('~^@([A-Za-z0-9_]{5,32})$~', $contact, $m) ? 'https://t.me/'.$m[1] : null;
        }

        $schemes = implode('|', self::CONTACT_APP_SCHEMES);

        if (preg_match('~^(?:'.$schemes.'):[^\s"\'<>]+$~i', $contact)) {
            return $contact;
        }

        if (preg_match('~^\+?[0-9][0-9\s()\-]{6,}$~', $contact)) {
            return 'tel:'.preg_replace('~[^0-9+]~', '', $contact);
        }

        $url = preg_match('~^https?://~i', $contact) ? $contact : 'https://'.$contact;
        $host = parse_url($url, PHP_URL_HOST);

        if (! filter_var($url, FILTER_VALIDATE_URL) || ! $host || ! preg_match('~\.[a-z]{2,}$~i', $host)) {
            return null;
        }

        return $url;
    }

    public function contactLink(): string
    {
        if (! $url = $this->contactUrl()) {
            return e($this->contact ?: '–');
        }

        $target = str_starts_with($url, 'http') ? ' target="_blank" rel="noopener"' : '';

        return '<a href="'.e($url).'"'.$target.'>'.e($this->contact).'</a>';
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function invalidReason(): ?string
    {
        return match (true) {
            $this->status === self::STATUS_REVOKED => 'license_revoked',
            $this->isExpired() => 'license_expired',
            default => null,
        };
    }
}
