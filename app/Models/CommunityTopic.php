<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Database\Factories\CommunityTopicFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * Тема спільноти: новина, питання, відгук, ідея, помилка, обговорення або FAQ.
 */
class CommunityTopic extends Model
{
    /** @use HasFactory<CommunityTopicFactory> */
    use CrudTrait, HasFactory, HasSlug;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_HIDDEN = 'hidden';

    public const STATUSES = [self::STATUS_PENDING, self::STATUS_PUBLISHED, self::STATUS_HIDDEN];

    /** Типи, які можуть створювати користувачі з ключем. */
    public const USER_TYPES = ['question', 'review', 'idea', 'bug', 'discussion'];

    /** Усі типи; новини й FAQ додає лише команда. */
    public const TYPES = ['news', 'question', 'review', 'idea', 'bug', 'discussion', 'faq'];

    protected $fillable = [
        'type', 'title', 'slug', 'body', 'author_name', 'license_id', 'is_staff', 'is_pinned',
        'is_members_only', 'status', 'rating', 'views', 'comments_count', 'likes_count', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_staff' => 'boolean',
            'is_pinned' => 'boolean',
            'is_members_only' => 'boolean',
            'rating' => 'integer',
            'views' => 'integer',
            'comments_count' => 'integer',
            'likes_count' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /** Адреси службових маршрутів спільноти, які тема зайняти не може. */
    private const RESERVED_SLUGS = ['nova', 'kliuch', 'vykhid'];

    protected static function booted(): void
    {
        static::creating(function (CommunityTopic $topic): void {
            if (in_array($topic->slug, self::RESERVED_SLUGS, true)) {
                $topic->slug .= '-tema';
            }
        });

        static::saving(function (CommunityTopic $topic): void {
            if ($topic->status === self::STATUS_PUBLISHED && $topic->published_at === null) {
                $topic->published_at = now();
            }
        });
    }

    /** Slug з української транслітерації заголовка. */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->usingLanguage('uk')
            ->slugsShouldBeNoLongerThan(120)
            ->skipGenerateWhen(fn () => filled($this->slug));
    }

    /** @param  Builder<CommunityTopic>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::STATUS_PUBLISHED);
    }

    /** Для перемикача в адмінці: опубліковано чи ні (на модерації й приховане = ні). */
    public function getIsPublishedAttribute(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CommunityComment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CommunityLike::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(CommunityReport::class);
    }

    public function publishedComments(): HasMany
    {
        return $this->comments()->where('status', CommunityComment::STATUS_PUBLISHED)->orderBy('id');
    }

    public function refreshCommentsCount(): void
    {
        $this->forceFill(['comments_count' => $this->publishedComments()->count()])->saveQuietly();
    }

    public function url(): string
    {
        return route('community.show', $this->slug);
    }
}
