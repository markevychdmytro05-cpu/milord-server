<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Пункт меню шапки чи підвалу: посилання на сторінку або на довільну адресу; може мати вкладені пункти.
 *
 * @property Collection<int, MenuItem> $tree_children
 */
class MenuItem extends Model
{
    use CrudTrait;

    public const HEADER = 'header';

    public const FOOTER = 'footer';

    protected $fillable = [
        'menu', 'parent_id', 'lft', 'rgt', 'depth', 'title', 'url', 'page_id', 'open_in_new_tab', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'open_in_new_tab' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Page, $this> */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /** @return BelongsTo<MenuItem, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<MenuItem, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('lft');
    }

    /** Підпис пункту: власний або назва сторінки. */
    public function getLabelAttribute(): string
    {
        return $this->title ?: ($this->page?->title ?? $this->url ?? '–');
    }

    /** Пункт-заголовок: без сторінки й адреси (наприклад, назва колонки підвалу). */
    public function isHeading(): bool
    {
        return ! $this->page_id && ! $this->url;
    }

    /** Адреса пункту; null, якщо сторінка неопублікована чи видалена або пункт є заголовком. */
    public function href(): ?string
    {
        if ($this->page_id) {
            return $this->page?->is_published ? $this->page->url() : null;
        }

        return $this->url;
    }

    /**
     * Чи веде пункт на поточну сторінку: збіг шляху або вкладена адреса (/monety-nbu/x для /monety-nbu).
     * Якорі на головну (/#pricing) не рахуються: їх підсвічує прокрутка на самій сторінці.
     */
    public function isCurrent(string $path): bool
    {
        $href = $this->href();

        if ($href === null || str_contains($href, '#')) {
            return false;
        }

        $target = '/'.trim((string) parse_url($href, PHP_URL_PATH), '/');
        $path = '/'.trim($path, '/');

        return $target !== '/' && ($path === $target || str_starts_with($path, $target.'/'));
    }

    /** Чи є серед вкладених пунктів той, що веде на поточну сторінку. */
    public function hasCurrentChild(string $path): bool
    {
        return $this->tree_children->contains(fn (self $child): bool => $child->isCurrent($path));
    }

    /**
     * Дерево активних пунктів меню з доступними адресами; вкладені лежать у $item->tree_children.
     *
     * @return Collection<int, MenuItem>
     */
    public static function tree(string $menu): Collection
    {
        $items = static::query()->with('page')->where('menu', $menu)->where('is_active', true)
            ->orderBy('lft')->orderBy('id')->get()->groupBy('parent_id');

        $build = function (?int $parentId) use (&$build, $items): Collection {
            return ($items[$parentId] ?? collect())
                ->filter(fn (self $item): bool => $item->href() !== null || $item->isHeading())
                ->each(fn (self $item) => $item->setRelation('tree_children', $build($item->id)))
                ->values();
        };

        return $build(null);
    }

    /**
     * Пункти дерева одним списком у порядку обходу (для плоского меню підвалу).
     *
     * @param  Collection<int, MenuItem>  $tree
     * @return Collection<int, MenuItem>
     */
    public static function flatten(Collection $tree): Collection
    {
        return $tree->flatMap(fn (self $item): Collection => collect([$item])->concat(static::flatten($item->getRelation('tree_children'))));
    }
}
