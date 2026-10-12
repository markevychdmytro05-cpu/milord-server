<?php

namespace App\Http\Controllers\Admin;

use App\Models\Release;
use App\Support\AdminPermissions;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Facades\Storage;

/** @property-read CrudPanel $crud */
class ReleaseCrudController extends CrudController
{
    use DeleteOperation { destroy as traitDestroy; }
    use ListOperation;
    use UpdateOperation { update as traitUpdate; }

    public function setup()
    {
        CRUD::setModel(Release::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/release');
        CRUD::setEntityNameStrings('реліз', 'релізи');
        AdminPermissions::crud([
            'list' => 'releases_view', 'update' => 'releases_update', 'delete' => 'releases_delete',
        ]);
    }

    protected function setupListOperation()
    {
        CRUD::orderBy('id', 'desc');

        CRUD::column('version')->label('Версія');
        CRUD::column('platform')->type('closure')->label('Система')->function(fn (Release $release) => $release->label());
        CRUD::column('size')->type('closure')->label('Розмір')->function(fn (Release $release) => $release->sizeLabel());
        CRUD::column('downloads_count')->type('number')->label('Завантажень');
        CRUD::column('published_at')->type('datetime')->label('Опубліковано');
        CRUD::column('is_active')->type('toggle')->label('Доступний')->toggle_route($this->crud->route);
    }

    protected function setupUpdateOperation()
    {
        CRUD::setValidation([
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],
        ]);

        CRUD::field('notes')->type('textarea')->label('Що нового')->hint('Показується на сайті й у вікні оновлення програми.');
        CRUD::field('is_active')->type('switch')->label('Доступний для завантаження й оновлення');
    }

    public function update()
    {
        $release = Release::findOrFail($this->crud->getRequest()->route('id'));
        $this->crud->getRequest()->merge(['id' => $release->id]);

        $response = $this->traitUpdate();

        $release->refresh();

        if ($release->is_active && ! $release->published_at) {
            $release->update(['published_at' => now()]);
        }

        return $response;
    }

    public function toggleActive(int $id)
    {
        CRUD::hasAccessOrFail('update');

        $release = Release::findOrFail($id);
        $release->update(['is_active' => ! $release->is_active, 'published_at' => $release->published_at ?? now()]);

        return response()->json(['value' => $release->is_active]);
    }

    public function destroy($id)
    {
        CRUD::hasAccessOrFail('delete');

        $release = Release::findOrFail($id);
        Storage::disk(Release::DISK)->delete($release->file_path);

        return $this->traitDestroy($id);
    }
}
