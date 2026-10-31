<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ArticleRequest extends PageRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'category' => ['required', 'string', Rule::exists('categories', 'name')],
        ]);
    }

    public function attributes(): array
    {
        return parent::attributes() + ['category' => 'категорія'];
    }
}
