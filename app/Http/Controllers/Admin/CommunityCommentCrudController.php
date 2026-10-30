<?php

namespace App\Http\Controllers\Admin;

use App\Models\CommunityComment;
use App\Support\AdminPermissions;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class CommunityCommentCrudController extends CrudController
{
    use DeleteOperation;
    use ListOperation;
    use UpdateOperation;

    public function setup()
    {
        CRUD::setModel(CommunityComment::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/community-comment');
        CRUD::setEntityNameStrings('коментар', 'коментарі спільноти');
        AdminPermissions::crud(['list' => 'community_view', 'update' => 'community_manage', 'delete' => 'community_manage']);
    }

    protected function setupListOperation()
    {
        CRUD::with('topic');
        CRUD::addClause('orderByRaw', "case status when 'pending' then 0 else 1 end");
        CRUD::orderBy('id', 'desc');
        CRUD::removeButton('create');

        CRUD::column('created_at')->type('datetime')->label('Створено')->priority(3);
        CRUD::column('topic')->label('Тема')->type('closure')->function(fn (CommunityComment $comment) => $comment->topic?->title)->priority(2);
        CRUD::column('author_name')->label('Автор')->priority(3);
        CRUD::column('body')->label('Коментар')->limit(120)->priority(1);
        CRUD::column('is_published')->type('toggle')->label('Опубліковано')->toggle_route($this->crud->route)->priority(1);

    }

    protected function setupUpdateOperation()
    {
        CRUD::setValidation(['body' => ['required', 'string', 'max:2000'], 'status' => ['required', 'in:pending,published,hidden']]);

        CRUD::field('body')->type('textarea')->label('Коментар')->attributes(['rows' => 5]);
        CRUD::field('status')->type('select_from_array')->label('Статус')->options([
            CommunityComment::STATUS_PENDING => 'На модерації',
            CommunityComment::STATUS_PUBLISHED => 'Опубліковано',
            CommunityComment::STATUS_HIDDEN => 'Приховано',
        ]);
    }

    public function togglePublished(int $id)
    {
        CRUD::hasAccessOrFail('update');

        $comment = CommunityComment::findOrFail($id);
        $comment->update(['status' => $comment->is_published ? CommunityComment::STATUS_HIDDEN : CommunityComment::STATUS_PUBLISHED]);

        return response()->json(['value' => $comment->is_published]);
    }
}
