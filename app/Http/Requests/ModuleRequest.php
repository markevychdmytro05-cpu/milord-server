<?php

namespace App\Http\Requests;

use App\Models\Module;
use App\Models\ModuleTemplate;
use App\Support\LocalUrl;
use Illuminate\Foundation\Http\FormRequest;

class ModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_user()?->can($this->route('module') ? 'modules_update' : 'modules_create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $template = $this->moduleTemplate();
        $data = [];

        foreach ($template->template['fields'] ?? [] as $field => $config) {
            if (($config['input'] ?? null) === 'checkbox') {
                $data[$field] = (int) $this->boolean($field);
            }

            if (($config['input'] ?? null) === 'browse') {
                $data[$field] = LocalUrl::path($this->input($field));
            }

            if (($config['input'] ?? null) === 'repeatable') {
                $data[$field] = array_values((array) $this->input($field, []));
            }
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        return $this->moduleTemplate()->rules();
    }

    public function moduleTemplate(): ModuleTemplate
    {
        $module = $this->route('module');

        return $module instanceof Module ? $module->module_template : $this->route('template');
    }
}
