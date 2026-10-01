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

/**
 * @property list<int> $page_modules ідентифікатори модулів сторінки в порядку виводу
 */
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use CrudTrait, HasFactory, HasOrderedModules, HasSlug;

    public const TYPE_PAGE = 'page';

    public const TYPE_SERVICE = 'service';

    public const TYPES = [
        self::TYPE_PAGE => 'Сторінка',
        self::TYPE_SERVICE => 'Послуга',
    ];

    /** Slug головної сторінки: відкривається на «/». */
    public const ROOT_SLUG = '/';

    /** Slug-и звичайних сторінок, які збігаються з наявними адресами. */
    public const RESERVED_SLUGS = ['admin', 'api', 'services', 'monety-nbu', 'sitemap', 'up', 'build', 'storage', 'login', 'contact'];

    /** Slug-и юридичних сторінок (політика конфіденційності, оферта). */
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

    /** @return list<int> */
    public function getPageModulesAttribute(): array
    {
        return $this->orderedModuleIds();
    }

    /** @param  list<int|string>|null  $value */
    public function setPageModulesAttribute(?array $value): void
    {
        $this->queueModules($value);
    }

    /** Абсолютна адреса зображення для соцмереж (шлях від кореня доповнюється доменом). */
    public function ogImageUrl(): ?string
    {
        if (! $this->og_image) {
            return null;
        }

        return str_starts_with($this->og_image, '/') ? url($this->og_image) : $this->og_image;
    }

    /** @param  Builder<Page>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** Slug з української транслітерації заголовка; заповнений вручну slug не чіпаємо. */
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

    /** @param  Builder<Page>  $query */
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

    /**
     * Індекс кольору категорії (0-5), спільний для фільтрів, тегів і блоку «Читають також».
     * Кольори роздаються за позицією категорії в алфавітному списку, тож різні категорії не збігаються (доки їх не більше шести).
     */
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

    /**
     * Інші статті бази знань: спершу з тієї ж категорії, далі найновіші.
     *
     * @return Collection<int, Page>
     */
    public function relatedArticles(int $limit = 4): Collection
    {
        return static::published()->whereNotNull('category')->where('id', '!=', $this->id)
            ->orderByRaw('CASE WHEN category = ? THEN 0 ELSE 1 END', [$this->category])
            ->orderByRaw('COALESCE(published_at, created_at) DESC')
            ->limit($limit)->get();
    }

    /** Дата в картці статті: вказана вручну або дата створення. */
    public function articleDate(): Carbon
    {
        return $this->published_at ?? $this->created_at;
    }

    public function isHome(): bool
    {
        return $this->slug === self::ROOT_SLUG;
    }

    public function url(): string
    {
        if ($this->isHome()) {
            return route('home');
        }

        return $this->type === self::TYPE_SERVICE
            ? route('services.show', $this->slug)
            : route('pages.show', $this->slug);
    }

    /** Довжина заголовка, до якої пошуковики зазвичай не обрізають його у видачі. */
    private const TITLE_LIMIT = 60;

    /** Заголовок сторінки; до нього додається назва бренду, якщо її ще немає і вона вміщується в ліміт. */
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
