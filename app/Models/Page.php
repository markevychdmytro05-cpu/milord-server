<?php

namespace App\Models;

use App\Enums\PageRobotsType;
use App\Models\Concerns\HasOrderedModules;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Page extends Model
{
    use CrudTrait, HasFactory, HasOrderedModules, HasSlug;

    public const TYPE_PAGE = 'page';

    public const TYPE_SERVICE = 'service';

    public const TYPES = [
        self::TYPE_PAGE => 'Сторінка',
        self::TYPE_SERVICE => 'Послуга',
    ];

    public const ROOT_SLUG = '/';

    public const BLOG_SLUG = 'blog';

    public const RESERVED_SLUGS = ['admin', 'api', 'services', 'monety-nbu', 'baza-znan', 'sitemap', 'up', 'build', 'storage', 'login', 'contact'];

    public const LEGAL_SLUGS = ['polityka-konfidentsiynosti', 'publichna-oferta'];

    protected $fillable = [
        'type', 'title', 'slug', 'excerpt', 'show_title', 'category', 'published_at', 'content', 'meta_title', 'meta_description', 'og_image',
        'robots', 'is_published', 'page_modules',
    ];

    protected function casts(): array
    {
        return [
            'show_title' => 'boolean',
            'published_at' => 'datetime',
            'robots' => PageRobotsType::class,
            'is_published' => 'boolean',
        ];
    }

    protected function modulePivotTable(): string
    {
        return 'page_module';
    }

    protected function modulePivotForeignKey(): string
    {
        return 'page_id';
    }

    public function getPageModulesAttribute(): array
    {
        return $this->orderedModuleIds();
    }

    public function setPageModulesAttribute(?array $value): void
    {
        $this->queueModules($value);
    }

    public function ogImageUrl(): ?string
    {
        if (! $this->og_image) {
            return null;
        }

        return str_starts_with($this->og_image, '/') ? url($this->og_image) : $this->og_image;
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->usingLanguage('uk')
            ->slugsShouldBeNoLongerThan(120)
            ->extraScope(fn (Builder $query) => $query->where('type', $this->type ?? self::TYPE_PAGE))
            ->skipGenerateWhen(fn () => filled($this->slug));
    }

    public function scopeIndexable(Builder $query): void
    {
        $query->whereIn('robots', [PageRobotsType::IndexFollow->value, PageRobotsType::IndexNofollow->value]);
    }

    public function robotsDirective(): string
    {
        $robots = $this->robots ?? PageRobotsType::IndexFollow;

        return $robots->isIndexable() ? $robots->value.', max-image-preview:large, max-snippet:-1' : $robots->value;
    }

    public const CATEGORY_COLORS = 6;

    public static function categoryColor(?string $category): int
    {
        if ($category === null || $category === '') {
            return 0;
        }

        $categories = request()->attributes->get('page_category_colors');

        if ($categories === null) {
            $categories = static::published()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category')->all();
            request()->attributes->set('page_category_colors', $categories);
        }
        $position = array_search($category, $categories, true);

        return ($position === false ? crc32($category) : $position) % self::CATEGORY_COLORS;
    }

    public function relatedArticles(int $limit = 4): Collection
    {
        return static::published()->whereNotNull('category')->where('id', '!=', $this->id)
            ->orderByRaw('CASE WHEN category = ? THEN 0 ELSE 1 END', [$this->category])
            ->orderByRaw('COALESCE(published_at, created_at) DESC')
            ->limit($limit)->get();
    }

    public function articleDate(): Carbon
    {
        return $this->published_at ?? $this->created_at;
    }

    public function isHome(): bool
    {
        return $this->slug === self::ROOT_SLUG;
    }

    public function isArticle(): bool
    {
        return $this->type === self::TYPE_PAGE && filled($this->category) && ! $this->isHome();
    }

    public function url(): string
    {
        if ($this->isHome()) {
            return route('home');
        }

        if ($this->type === self::TYPE_SERVICE) {
            return route('services.show', $this->slug);
        }

        return $this->isArticle() ? route('articles.show', $this->slug) : route('pages.show', $this->slug);
    }

    private const TITLE_LIMIT = 60;

    public function seoTitle(): string
    {
        $title = $this->meta_title ?: $this->title;
        $brand = SiteSetting::current()->header_logo;

        if ($this->isHome() || $brand === '' || mb_stripos($title, $brand) !== false) {
            return $title;
        }

        $branded = $title.' | '.$brand;

        return mb_strlen($branded) <= self::TITLE_LIMIT ? $branded : $title;
    }

    public function seoDescription(): ?string
    {
        return $this->meta_description ?: ($this->excerpt ?: null);
    }
}
