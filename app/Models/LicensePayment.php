<?php

namespace App\Models;

use Database\Factories\LicensePaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicensePayment extends Model
{
    /** @use HasFactory<LicensePaymentFactory> */
    use HasFactory;

    public const OPERATION_PAYMENT = 'payment';

    public const OPERATION_RENEWAL = 'renewal';

    protected $fillable = [
        'operation', 'amount_cents', 'currency', 'paid_at', 'duration_days',
        'period_starts_at', 'period_ends_at', 'expires_at_before', 'reference', 'note',
        'idempotency_key', 'request_hash', 'recorded_by',
    ];

    protected $hidden = ['idempotency_key', 'request_hash'];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'duration_days' => 'integer',
            'paid_at' => 'datetime',
            'period_starts_at' => 'datetime',
            'period_ends_at' => 'datetime',
            'expires_at_before' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function amountLabel(): string
    {
        return number_format($this->amount_cents / 100, 2, '.', ' ').' '.$this->currency;
    }
}
