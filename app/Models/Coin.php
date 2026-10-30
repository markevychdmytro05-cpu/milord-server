<?php

namespace App\Models;

use App\Enums\PageRobotsType;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Database\Factories\CoinFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Coin extends Model
{
    use CrudTrait, HasFactory, HasSlug;

    protected $fillable = [
        'title', 'slug', 'denomination', 'series', 'metal', 'release_year', 'release_month', 'mintage', 'price', 'sale_starts_at', 'nbu_url',
        'image', 'image_source', 'image_credit', 'excerpt', 'content', 'meta_title', 'meta_description', 'og_image', 'robots', 'is_published', 'show_on_home',
    ];

    protected static function booted(): void
    {
        static::saving(function (Coin $coin): void {
            if ($coin->sale_starts_at) {
                $coin->release_year = $coin->sale_starts_at->year;
                $coin->release_month = $coin->sale_starts_at->month;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'release_year' => 'integer',
            'release_month' => 'integer',
            'mintage' => 'integer',
            'price' => 'decimal:2',
            'sale_starts_at' => 'datetime',
            'robots' => PageRobotsType::class,
            'is_published' => 'boolean',
            'show_on_home' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    public function scopeWithImage(Builder $query): void
    {
        $query->whereNotNull('image')->where('image', '!=', '');
    }

    public function scopeUpcoming(Builder $query): void
    {
        $now = now();

        $query->where(function (Builder $query) use ($now) {
            $query->where('sale_starts_at', '>=', $now)
                ->orWhere(fn (Builder $query) => $query->whereNull('sale_starts_at')->where(function (Builder $query) use ($now) {
                    $query->where('release_year', '>', $now->year)
                        ->orWhere(fn (Builder $query) => $query->where('release_year', $now->year)->where('release_month', '>=', $now->month));
                }));
        })->orderBy('release_year')->orderBy('release_month')->orderBy('sale_starts_at')->orderBy('id');
    }

    public function scopeChronological(Builder $query, string $direction = 'asc'): void
    {
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $query->orderByRaw('release_year IS NULL')->orderBy('release_year', $direction)
            ->orderByRaw('release_month IS NULL')->orderBy('release_month', $direction)
            ->orderBy('sale_starts_at', $direction)->orderBy('id', $direction);
    }

    public function scopeIndexable(Builder $query): void
    {
        $query->whereIn('robots', [PageRobotsType::IndexFollow->value, PageRobotsType::IndexNofollow->value])
            ->whereNotNull('content')->where('content', '!=', '');
    }

    public function isThin(): bool
    {
        return trim(strip_tags((string) $this->content)) === '';
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->usingLanguage('uk')
            ->slugsShouldBeNoLongerThan(120)
            ->skipGenerateWhen(fn () => filled($this->slug));
    }

    public function shortTitle(): string
    {
        $title = preg_replace('/^('.preg_quote(__('coins.prefix_investment'), '/').'|'.preg_quote(__('coins.prefix'), '/').')\s*/u', '', $this->title);

        if (preg_match('/^«(.+)»(\s*\(.+\))?$/u', $title, $matches)) {
            return $matches[1].($matches[2] ?? '');
        }

        return preg_replace('/^«([^«»]*)»$/u', '$1', trim($title));
    }

    public static function months(): array
    {
        return (array) trans('coins.months');
    }

    public function url(): string
    {
        return route('coins.show', $this->slug);
    }

    public function releaseLabel(): ?string
    {
        if ($this->sale_starts_at) {
            return __('site.date_with_year', ['date' => $this->sale_starts_at->locale(app()->getLocale())->translatedFormat('j F Y')]);
        }

        return $this->release_month ? __('coins.months.'.$this->release_month).' '.$this->release_year : null;
    }

    public function hasBeenReleased(): bool
    {
        if ($this->sale_starts_at) {
            return $this->sale_starts_at->isPast();
        }

        if (! $this->release_year || ! $this->release_month) {
            return false;
        }

        return $this->release_year < now()->year || ($this->release_year === now()->year && $this->release_month <= now()->month);
    }

    public function isUpcoming(): bool
    {
        return $this->sale_starts_at !== null && $this->sale_starts_at->isFuture();
    }

    public function imageUrl(): ?string
    {
        if (! $this->image) {
            return null;
        }

        return str_starts_with($this->image, '/') ? url($this->image) : $this->image;
    }

    public function ogImageUrl(): ?string
    {
        $image = $this->og_image ?: $this->image;

        if (! $image) {
            return null;
        }

        return str_starts_with($image, '/') ? url($image) : $image;
    }

    public function robotsDirective(): string
    {
        $robots = $this->robots ?? PageRobotsType::IndexFollow;

        if ($this->isThin()) {
            return PageRobotsType::NoindexFollow->value;
        }

        return $robots->isIndexable() ? $robots->value.', max-image-preview:large, max-snippet:-1' : $robots->value;
    }

    public function seoTitle(): string
    {
        $title = $this->meta_title ?: $this->generatedTitle();
        $brand = SiteSetting::current()->header_logo;

        if ($brand === '' || mb_stripos($title, $brand) !== false) {
            return $title;
        }

        $branded = $title.' | '.$brand;

        return mb_strlen($branded) <= 60 ? $branded : $title;
    }

    public function seoDescription(): ?string
    {
        return $this->meta_description ?: $this->generatedDescription();
    }

    private function generatedTitle(): string
    {
        $title = $this->title.': '.__('coins.seo.title_suffix');

        return mb_strlen($title) <= 60 ? $title : $this->title;
    }

    private function generatedDescription(): string
    {
        $facts = array_filter([
            $this->denomination ? __('coins.seo.denomination', ['value' => $this->denomination]) : null,
            $this->metal,
            $this->mintage ? __('coins.seo.mintage', ['value' => number_format($this->mintage, 0, ',', ' ')]) : null,
            $this->releaseLabel() ? __('coins.seo.release', ['value' => mb_strtolower($this->releaseLabel())]) : null,
        ]);

        return mb_strimwidth($this->title.': '.implode(', ', $facts).'. '.__('coins.seo.tail'), 0, 160, '…');
    }
}
