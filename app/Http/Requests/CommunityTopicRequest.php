<?php

namespace App\Http\Requests;

use App\Models\CommunityTopic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommunityTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(CommunityTopic::USER_TYPES)],
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'body' => ['required', 'string', 'min:20', 'max:5000'],
            'rating' => ['nullable', 'required_if:type,review', 'integer', 'between:1,5'],
            'name' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'string', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => __('community.form.type'),
            'title' => __('community.form.title'),
            'body' => __('community.form.body'),
            'rating' => __('community.form.rating'),
            'name' => __('community.form.name'),
        ];
    }
}
