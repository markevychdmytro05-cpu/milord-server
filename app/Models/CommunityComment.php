<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Database\Factories\CommunityCommentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityComment extends Model
{
    use CrudTrait, HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_HIDDEN = 'hidden';

    protected $fillable = ['community_topic_id', 'license_id', 'author_name', 'body', 'is_staff', 'status'];

    protected function casts(): array
    {
        return ['is_staff' => 'boolean'];
    }

    protected static function booted(): void
    {
        $refresh = fn (CommunityComment $comment) => $comment->topic?->refreshCommentsCount();

        static::saved($refresh);
        static::deleted($refresh);
    }

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
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
