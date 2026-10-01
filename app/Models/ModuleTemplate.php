<?php

namespace App\Models;

use App\Support\SiteLocale;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Шаблон (тип) модуля. Опис у JSON-колонці template:
 *  - fields / rules            – загальні поля й правила валідації;
 *  - lang_fields / lang_rules  – поля, що зберігаються окремо для кожної мови (setting[uk][title]).
 *
 * Код шаблону визначає клас модуля (App\Modules\{Code}Module) і blade-шаблон.
 */
class ModuleTemplate extends Model
{
    use CrudTrait;

    protected $fillable = ['name', 'code', 'template'];

    protected function casts(): array
    {
        return ['template' => AsArrayObject::class];
    }

    /** @return HasMany<Module, $this> */
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }

    public static function byCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * @param  array<string, mixed>  $setting
     */
    public function makeModule(string $name, array $setting): Module
    {
        $config = ['name' => $name, 'module_template_id' => $this->id, 'setting' => []];

        foreach ($this->template['fields'] ?? [] as $attr => $attrConfig) {
            $config['setting'][$attr] = $setting[$attr] ?? '';
        }

        foreach (SiteLocale::all() as $locale) {
            foreach ($this->template['lang_fields'] ?? [] as $attr => $attrConfig) {
                $config['setting'][$locale][$attr] = $setting[$locale][$attr] ?? '';
            }
        }

        return Module::create($config);
    }

    public function hasLangFields(): bool
    {
        return ! empty($this->template['lang_fields']);
    }

    /**
     * Поля форми Backpack для цього шаблону.
     *
     * @return list<array<string, mixed>>
     */
    public function fields(): array
    {
        $template = $this->template;
        $result = [];

        foreach (SiteLocale::all() as $locale) {
            foreach ($template['lang_fields'] ?? [] as $field => $config) {
                $result[] = $this->buildField([
                    'field' => $locale.'.'.$field,
                    'name' => $locale.'['.$field.']',
                    'label' => ($config['label'] ?? $field).': '.$locale,
                    'tab' => $locale,
                    'wrapper' => $config['wrapper'] ?? null,
                ], $config);
            }
        }

        foreach ($template['fields'] ?? [] as $field => $config) {
            $result[] = $this->buildField([
                'field' => $field,
                'name' => $field,
                'label' => $config['label'] ?? $field,
                'tab' => 'Main',
            ], $config);
        }

        if (! array_key_exists('name', $template['fields'] ?? [])) {
            $result[] = ['type' => 'text', 'field' => 'name', 'name' => 'name', 'label' => 'Module name', 'tab' => 'Main'];
        }

        return $result;
    }

    /**
     * Ключі налаштувань, які приймаються з форми (для мовних полів – «uk.title»).
     *
     * @return list<string>
     */
    public function column_names(): array
    {
        $template = $this->template;
        $result = array_keys($template['fields'] ?? []);

        if (! in_array('name', $result, true)) {
            $result[] = 'name';
        }

        foreach (SiteLocale::all() as $locale) {
            foreach (array_keys($template['lang_fields'] ?? []) as $attr) {
                $result[] = $locale.'.'.$attr;
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $template = $this->template;
        $result = $template['rules'] ?? [];

        foreach (SiteLocale::all() as $locale) {
            foreach ($template['lang_rules'] ?? [] as $column => $rule) {
                $result[$locale.'.'.$column] = $rule;
            }
        }

        $result['name'] ??= 'nullable|string|max:255';

        return $result;
    }

    /**
     * Назви полів, у яких очікується завантаження файлу.
     *
     * @return list<string>
     */
    public function uploadFields(): array
    {
        return collect($this->template['fields'] ?? [])
            ->filter(fn (array $config): bool => in_array($config['input'] ?? null, ['image', 'upload'], true))
            ->keys()->all();
    }

    /**
     * @param  array<string, mixed>  $field
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function buildField(array $field, array $config): array
    {
        $field = array_filter($field, fn ($value): bool => $value !== null);

        return $field + match ($config['input'] ?? 'text') {
            'checkbox' => ['type' => 'checkbox'],
            'radio' => ['type' => 'radio', 'options' => $config['options'] ?? []],
            'select' => ['type' => 'select_from_array', 'allows_null' => false, 'options' => $this->selectOptions($config)],
            'textarea' => ['type' => 'textarea'],
            'ckeditor', 'ckeditor-title' => ['type' => 'summernote', 'options' => ['height' => 450]],
            'image', 'upload' => ['type' => 'upload', 'disk' => 'public', 'withFiles' => false],
            'browse' => ['type' => 'browse', 'mime_types' => ['image']],
            'repeatable' => ['type' => 'simple_repeatable', 'subfields' => $config['fields'] ?? []],
            default => ['type' => 'text'],
        };
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<int|string, string>
     */
    private function selectOptions(array $config): array
    {
        $provider = $config['provider'] ?? [];

        if (is_array($provider)) {
            return $provider;
        }

        return class_exists($provider) ? $provider::query()->pluck($config['column'] ?? 'title', 'id')->all() : [];
    }
}
