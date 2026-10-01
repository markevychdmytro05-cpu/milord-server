<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Database\Factories\LicenseAuditFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicenseAudit extends Model
{
    /** @use HasFactory<LicenseAuditFactory> */
    use CrudTrait, HasFactory;

    public const EVENTS = [
        'created' => 'Ліцензію створено',
        'updated' => 'Ліцензію змінено',
        'deleted' => 'Ліцензію видалено',
        'device_activated' => 'Пристрій активовано',
        'device_unbound' => 'Пристрій відв’язано',
        'payment_recorded' => 'Оплату записано',
        'renewed' => 'Ліцензію продовжено',
        'payment_voided' => 'Запис оплати скасовано',
    ];

    protected $fillable = ['license_id', 'license_number', 'actor_id', 'actor_name', 'event', 'changes', 'metadata'];

    protected function casts(): array
    {
        return ['changes' => 'array', 'metadata' => 'array'];
    }

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     * @param  array<string, mixed>  $metadata
     */
    public static function record(License $license, string $event, array $changes = [], array $metadata = []): self
    {
        $actor = auth('backpack')->user();

        return self::create([
            'license_id' => $license->id,
            'license_number' => $license->id,
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name ?? 'Система / API',
            'event' => $event,
            'changes' => $changes,
            'metadata' => $metadata,
        ]);
    }

    public function eventLabel(): string
    {
        return self::EVENTS[$this->event] ?? $this->event;
    }
}
