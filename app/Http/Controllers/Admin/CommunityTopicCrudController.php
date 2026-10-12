<?php

namespace App\Http\Controllers\Admin;

use App\Models\CommunityTopic;
use App\Support\AdminPermissions;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Validation\Rule;

/**
 * Теми спільноти: модерація користувацьких тем і публікація новин та FAQ від команди.
 *
 * @property-read CrudPanel $crud
 */
class CommunityTopicCrudController extends CrudController
{
    use CreateOperation;
    use DeleteOperation;
    use ListOperation;
    use UpdateOperation;

    public function setup()
    {
        CRUD::setModel(CommunityTopic::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/community-topic');
        CRUD::setEntityNameStrings('тему', 'теми спільноти');
        AdminPermissions::crud([
            'list' => 'community_view', 'create' => 'community_manage',
            'update' => 'community_manage', 'delete' => 'community_manage',
        ]);
    }

    protected function setupListOperation()
    {
        CRUD::addClause('orderByRaw', "case status when 'pending' then 0 else 1 end");
        CRUD::orderBy('id', 'desc');

        CRUD::column('created_at')->type('datetime')->label('Створено')->priority(3);
        CRUD::column('type')->label('Тип')->type('closure')->function(fn (CommunityTopic $topic) => __('community.type_singular.'.$topic->type))->priority(2);
        CRUD::column('title')->label('Заголовок')->priority(1);
        CRUD::column('author_name')->label('Автор')->priority(3);
        CRUD::column('status')->label('Статус')->type('closure')->function(fn (CommunityTopic $topic) => $this->statusLabels()[$topic->status] ?? $topic->status)->priority(2);
        CRUD::column('is_published')->type('toggle')->label('Опубліковано')->toggle_route($this->crud->route)->priority(1);
        CRUD::column('comments_count')->label('Коментарі')->priority(4);
        CRUD::column('views')->label('Перегляди')->priority(5);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'type' => ['required', Rule::in(CommunityTopic::TYPES)],
            'title' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:150', 'regex:/^[A-Za-z0-9-]+$/', Rule::unique('community_topics', 'slug')->ignore(request()->route('id'))],
            'body' => ['required', 'string', 'max:20000'],
            'author_name' => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::in(CommunityTopic::STATUSES)],
            'rating' => ['nullable', 'integer', 'between:1,5'],
        ]);

        CRUD::field('type')->type('select_from_array')->label('Тип')->options(
            collect(CommunityTopic::TYPES)->mapWithKeys(fn (string $type) => [$type => __('community.type_singular.'.$type)])->all(),
        )->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('status')->type('select_from_array')->label('Статус')->options($this->statusLabels())
            ->default(CommunityTopic::STATUS_PUBLISHED)->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('rating')->type('number')->label('Оцінка відгуку (1–5)')->attributes(['min' => 1, 'max' => 5])->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('title')->label('Заголовок');
        CRUD::field('slug')->type('slug_input')->label('Адреса (slug)')->source('title');
        CRUD::field('body')->type('textarea')->label('Текст')->attributes(['rows' => 10]);
        CRUD::field('author_name')->label('Автор')->default('Команда Numis')->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('is_staff')->type('switch')->label('Від команди')->default(true)->wrapper(['class' => 'form-group col-md-2']);
        CRUD::field('is_pinned')->type('switch')->label('Закріпити')->wrapper(['class' => 'form-group col-md-2']);
        CRUD::field('is_members_only')->type('switch')->label('Лише за ключем')->default(true)->wrapper(['class' => 'form-group col-md-2']);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function togglePublished(int $id)
    {
        CRUD::hasAccessOrFail('update');

        $topic = CommunityTopic::findOrFail($id);
        $topic->update(['status' => $topic->is_published ? CommunityTopic::STATUS_HIDDEN : CommunityTopic::STATUS_PUBLISHED]);

        return response()->json(['value' => $topic->is_published]);
    }

    /** @return array<string, string> */
    private function statusLabels(): array
    {
        return [
            CommunityTopic::STATUS_PENDING => 'На модерації',
            CommunityTopic::STATUS_PUBLISHED => 'Опубліковано',
            CommunityTopic::STATUS_HIDDEN => 'Приховано',
        ];
    }
}
