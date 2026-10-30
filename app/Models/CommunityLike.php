<?php

namespace App\Models;

use Database\Factories\CommunityLikeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityLike extends Model
{
    use HasFactory;

    protected $fillable = ['community_topic_id', 'license_id'];

    protected static function booted(): void
    {
        $refresh = fn (CommunityLike $like) => $like->topic?->forceFill(['likes_count' => $like->topic->likes()->count()])->saveQuietly();

        static::saved($refresh);
        static::deleted($refresh);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(CommunityTopic::class, 'community_topic_id');
    }
}
