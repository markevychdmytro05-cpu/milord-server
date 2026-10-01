<?php

namespace App\Http\Requests;

use App\Enums\PageRobotsType;
use App\Models\Page;
use App\Support\LocalUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_user()?->can($this->route('id') ? 'pages_update' : 'pages_create') ?? false;
    }

    /** Порожній slug генерує модель (spatie/laravel-sluggable) з заголовка. */
    protected function prepareForValidation(): void
    {
        $slug = trim((string) $this->input('slug'));

        $this->merge([
            'og_image' => LocalUrl::path($this->input('og_image')),
            // Тип не редагується у формі: нові записи – звичайні сторінки, наявні зберігають свій тип.
            'type' => $this->route('id') ? (Page::find($this->route('id'))?->type ?? Page::TYPE_PAGE) : Page::TYPE_PAGE,
            'slug' => $slug === Page::ROOT_SLUG ? $slug : ($slug === '' ? null : Str::slug($slug, '-', 'uk')),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $isPage = $this->input('type') === Page::TYPE_PAGE;

        return [
            'type' => ['required', Rule::in(array_keys(Page::TYPES))],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:120', 'regex:/^(\/|[a-z0-9-]+)$/',
                Rule::unique('pages', 'slug')->where('type', $this->input('type'))->ignore($this->route('id')),
                ...($isPage ? [Rule::notIn(Page::RESERVED_SLUGS)] : []),
            ],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'string', 'max:2048', 'regex:~^(https?://|/)~i'],
            'show_title' => ['boolean'],
            'category' => ['nullable', 'string', Rule::exists('categories', 'name')],
            'published_at' => ['nullable', 'date'],
            'robots' => ['sometimes', Rule::enum(PageRobotsType::class)],
            'is_published' => ['boolean'],
            'page_modules' => ['array'],
            'page_modules.*' => ['integer', Rule::exists('modules', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'заголовок',
            'slug' => 'адреса (slug)',
            'excerpt' => 'короткий опис',
            'content' => 'зміст',
            'meta_title' => 'SEO-заголовок',
            'meta_description' => 'SEO-опис',
            'og_image' => 'зображення для соцмереж',
        ];
    }
}
