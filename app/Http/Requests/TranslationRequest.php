<?php

namespace App\Http\Requests;

use App\Support\SiteLocale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_user()?->can($this->route('id') ? 'translations_update' : 'translations_create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'group' => ['required', 'string', 'max:100', 'regex:/^(\*|[A-Za-z0-9_-]+)$/'],
            'key' => [
                'required', 'string', 'max:255',
                Rule::unique('language_lines', 'key')->where('group', $this->input('group'))->ignore($this->route('id')),
            ],
            'text' => ['required', 'array'],
            'text.'.SiteLocale::current() => ['required', 'string'],
            'text.*' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'group' => 'група',
            'key' => 'ключ',
            'text' => 'переклад',
            'text.'.SiteLocale::current() => 'переклад ('.SiteLocale::current().')',
        ];
    }

    /** Порожні переклади не зберігаються: без них діє значення з файлу або мови за замовчуванням. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'group' => trim((string) $this->input('group')),
            'key' => trim((string) $this->input('key')),
            'text' => array_filter((array) $this->input('text'), fn ($value): bool => is_string($value) && trim($value) !== ''),
        ]);
    }
}
