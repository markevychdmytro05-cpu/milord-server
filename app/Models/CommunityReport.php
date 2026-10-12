<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Database\Factories\CommunityReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Скарга власника ліцензії на тему спільноти.
 */
class CommunityReport extends Model
{
    /** @use HasFactory<CommunityReportFactory> */
    use CrudTrait, HasFactory;

    protected $fillable = ['community_topic_id', 'license_id', 'reason', 'is_processed'];

    protected function casts(): array
    {
        return ['is_processed' => 'boolean'];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(CommunityTopic::class, 'community_topic_id');
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}
