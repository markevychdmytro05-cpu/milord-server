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
    /** @use HasFactory<CoinFactory> */
    use CrudTrait, HasFactory, HasSlug;

    protected $fillable = [
        'title', 'slug', 'denomination', 'series', 'metal', 'release_year', 'release_month', 'mintage', 'price', 'sale_starts_at', 'nbu_url',
        'image', 'image_source', 'image_credit', 'excerpt', 'content', 'meta_title', 'meta_description', 'og_image', 'robots', 'is_published',
    ];

    /** Точна дата продажу визначає рік і місяць випуску, щоб сортування й блок «Найближчі випуски» не розходились. */
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
        ];
    }

    /** @param  Builder<Coin>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Найближчі випуски: продаж ще попереду, а без точної дати – місяць випуску поточний або пізніший.
     *
     * @param  Builder<Coin>  $query
     */
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

    /**
     * Хронологічний порядок за датою виходу; монети без дати завжди в кінці.
     *
     * @param  Builder<Coin>  $query
     */
    public function scopeChronological(Builder $query, string $direction = 'asc'): void
    {
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $query->orderByRaw('release_year IS NULL')->orderBy('release_year', $direction)
            ->orderByRaw('release_month IS NULL')->orderBy('release_month', $direction)
            ->orderBy('sale_starts_at', $direction)->orderBy('id', $direction);
    }

    /**
     * Індексовані монети: дозволені за robots і з власним описом (сторінка лише з фактами плану – тонка, її не індексуємо).
     *
     * @param  Builder<Coin>  $query
     */
    public function scopeIndexable(Builder $query): void
    {
        $query->whereIn('robots', [PageRobotsType::IndexFollow->value, PageRobotsType::IndexNofollow->value])
            ->whereNotNull('content')->where('content', '!=', '');
    }

    /** Тонка сторінка: немає власного опису, лише факти з плану випуску. */
    public function isThin(): bool
    {
        return trim(strip_tags((string) $this->content)) === '';
    }

    /** Slug з української транслітерації назви; заповнений вручну slug не чіпаємо. */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->usingLanguage('uk')
            ->slugsShouldBeNoLongerThan(120)
            ->skipGenerateWhen(fn () => filled($this->slug));
    }

    /** Назва без службового префікса: «Паляниця», а не «Монета НБУ «Паляниця»». */
    public function shortTitle(): string
    {
        $title = preg_replace('/^('.preg_quote(__('coins.prefix_investment'), '/').'|'.preg_quote(__('coins.prefix'), '/').')\s*/u', '', $this->title);

        if (preg_match('/^«(.+)»(\s*\(.+\))?$/u', $title, $matches)) {
            return $matches[1].($matches[2] ?? '');
        }

        return preg_replace('/^[«\s]+|[»\s]+$/u', '', $title);
    }

    /**
     * Назви місяців поточною мовою: номер => назва.
     *
     * @return array<int, string>
     */
    public static function months(): array
    {
        return (array) trans('coins.months');
    }

    public function url(): string
    {
        return route('coins.show', $this->slug);
    }

    /** Дата продажу, а за її відсутності місяць введення в обіг за планом НБУ («Вересень 2026»). */
    public function releaseLabel(): ?string
    {
        if ($this->sale_starts_at) {
            return __('site.date_with_year', ['date' => $this->sale_starts_at->locale(app()->getLocale())->translatedFormat('j F Y')]);
        }

        return $this->release_month ? __('coins.months.'.$this->release_month).' '.$this->release_year : null;
    }

    /** Монета вже вийшла: продаж розпочато або місяць випуску за планом уже настав. */
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

    /** Продаж ще не почався: дата старту в майбутньому. */
    public function isUpcoming(): bool
    {
        return $this->sale_starts_at !== null && $this->sale_starts_at->isFuture();
    }

    /** Абсолютна адреса зображення монети. */
    public function imageUrl(): ?string
    {
        if (! $this->image) {
            return null;
        }

        return str_starts_with($this->image, '/') ? url($this->image) : $this->image;
    }

    /** Зображення для соцмереж: своє, а за його відсутності зображення монети. */
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

    /** Заголовок для видачі; до нього додається бренд, якщо вміщується в ліміт. */
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

    /** Заголовок із назви монети, якщо SEO-заголовок не заповнено. */
    private function generatedTitle(): string
    {
        $title = $this->title.': '.__('coins.seo.title_suffix');

        return mb_strlen($title) <= 60 ? $title : $this->title;
    }

    /** Унікальний опис для видачі з фактів монети, якщо SEO-опис не заповнено. */
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
