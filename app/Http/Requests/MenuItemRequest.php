<?php

namespace App\Http\Requests;

use App\Models\MenuItem;
use App\Rules\SafeUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_user()?->can('site_manage') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'menu' => ['required', Rule::in([MenuItem::HEADER, MenuItem::FOOTER])],
            'page_id' => ['nullable', 'integer', Rule::exists('pages', 'id')],
            'url' => ['nullable', 'string', 'max:255', new SafeUrl],
            'title' => ['nullable', 'string', 'max:120', 'required_without:page_id'],
            'open_in_new_tab' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['page_id' => 'сторінка', 'url' => 'адреса', 'title' => 'назва'];
    }
}
