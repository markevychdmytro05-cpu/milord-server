<?php

namespace App\Models;

use App\Interfaces\ModuleInterface;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Database\Factories\ModuleFactory;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Екземпляр модуля: налаштування (setting) за схемою його шаблону (ModuleTemplate).
 */
class Module extends Model
{
    /** @use HasFactory<ModuleFactory> */
    use CrudTrait, HasFactory;

    protected $fillable = ['name', 'setting', 'module_template_id'];

    protected function casts(): array
    {
        return ['setting' => AsArrayObject::class];
    }

    /** @return BelongsTo<ModuleTemplate, $this> */
    public function module_template(): BelongsTo
    {
        return $this->belongsTo(ModuleTemplate::class);
    }

    /**
     * @return array<int, string> id => назва
     */
    public static function getModulesList(): array
    {
        return static::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * Поля форми редагування зі значеннями цього модуля.
     *
     * @return list<array<string, mixed>>
     */
    public function fields(): array
    {
        return array_map(function (array $field): array {
            $path = $field['field'];

            $field['value'] = $path === 'name'
                ? ($this->setting['name'] ?? $this->name)
                : data_get($this->setting?->toArray() ?? [], $path);

            return $field;
        }, $this->module_template->fields());
    }

    /** Клас модуля за кодом шаблону: «text_section» → App\Modules\TextSectionModule. */
    public function moduleClass(): string
    {
        return 'App\Modules\\'.Str::ucfirst(Str::camel($this->module_template->code)).'Module';
    }

    /** HTML модуля; порожній рядок, якщо модуль вимкнений, без класу або клас нічого не повернув. */
    public function draw(): string
    {
        $className = $this->moduleClass();

        if (! class_exists($className) || ! ($this->setting['active'] ?? false)) {
            return '';
        }

        $module = new $className;
        assert($module instanceof ModuleInterface);

        return $module->draw([...$this->setting->toArray(), 'module_id' => $this->id]);
    }
}
