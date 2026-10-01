<?php

namespace App\Http\Requests;

use App\Enums\PageRobotsType;
use App\Support\LocalUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CoinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_user()?->can($this->route('id') ? 'coins_update' : 'coins_create') ?? false;
    }

    /** Порожній slug генерує модель (spatie/laravel-sluggable) з назви. */
    protected function prepareForValidation(): void
    {
        $slug = trim((string) $this->input('slug'));

        $this->merge([
            'image' => LocalUrl::path($this->input('image')),
            'og_image' => LocalUrl::path($this->input('og_image')),
            'slug' => $slug === '' ? null : Str::slug($slug, '-', 'uk'),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/', Rule::unique('coins', 'slug')->ignore($this->route('id'))],
            'denomination' => ['nullable', 'string', 'max:50'],
            'series' => ['nullable', 'string', 'max:255'],
            'metal' => ['nullable', 'string', 'max:100'],
            'release_year' => ['nullable', 'integer', 'between:1990,2100'],
            'release_month' => ['nullable', 'integer', 'between:1,12'],
            'mintage' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'sale_starts_at' => ['nullable', 'date'],
            'nbu_url' => ['nullable', 'string', 'max:2048', 'url:http,https'],
            'image' => ['nullable', 'string', 'max:2048', 'regex:~^(https?://|/)~i'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'string', 'max:2048', 'regex:~^(https?://|/)~i'],
            'robots' => ['sometimes', Rule::enum(PageRobotsType::class)],
            'is_published' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'назва',
            'slug' => 'адреса (slug)',
            'denomination' => 'номінал',
            'series' => 'серія',
            'metal' => 'метал',
            'release_year' => 'рік випуску',
            'release_month' => 'місяць випуску',
            'mintage' => 'тираж',
            'price' => 'ціна',
            'sale_starts_at' => 'початок продажу',
            'nbu_url' => 'посилання на магазин НБУ',
            'image' => 'зображення монети',
            'excerpt' => 'короткий опис',
            'content' => 'опис',
            'meta_title' => 'SEO-заголовок',
            'meta_description' => 'SEO-опис',
            'og_image' => 'зображення для соцмереж',
        ];
    }
}
