<?php

namespace App\Models\Concerns;

use App\Models\Module;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Впорядкований список модулів моделі (звʼязок через таблицю з sort_order, як layout_module в iptel-ua).
 * Модель задає таблицю й ключ та віртуальний атрибут-список id (get/set…Attribute), що викликає orderedModuleIds/queueModules.
 */
trait HasOrderedModules
{
    /** @var list<int|string>|null */
    private ?array $pendingModules = null;

    abstract protected function modulePivotTable(): string;

    abstract protected function modulePivotForeignKey(): string;

    public static function bootHasOrderedModules(): void
    {
        static::saved(function (Model $model): void {
            $model->flushPendingModules();
        });
    }

    /** @return BelongsToMany<Module, $this> */
    public function modules_list(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, $this->modulePivotTable(), $this->modulePivotForeignKey(), 'module_id')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    /** @return list<int> */
    public function orderedModuleIds(): array
    {
        return $this->exists
            ? $this->modules_list()->pluck('modules.id')->map(fn ($id): int => (int) $id)->all()
            : [];
    }

    /** @param  list<int|string>|null  $ids */
    protected function queueModules(?array $ids): void
    {
        $this->pendingModules = $ids ?? [];
    }

    /** HTML модулів у порядку сортування. */
    public function drawModules(): string
    {
        return $this->modules_list->map(fn (Module $module): string => $module->draw())->implode("\n");
    }

    private function flushPendingModules(): void
    {
        if ($this->pendingModules === null) {
            return;
        }

        $this->modules_list()->sync(
            collect($this->pendingModules)->unique()->values()
                ->mapWithKeys(fn ($moduleId, int $position): array => [$moduleId => ['sort_order' => $position]])
                ->all()
        );

        $this->pendingModules = null;
        $this->unsetRelation('modules_list');
    }
}
