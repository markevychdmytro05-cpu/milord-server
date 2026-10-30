<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommunityCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:2', 'max:2000'],
            'name' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'string', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return ['body' => __('community.form.comment'), 'name' => __('community.form.name')];
    }
}
