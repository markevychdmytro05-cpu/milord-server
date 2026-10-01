<?php

namespace App\Http\Controllers\Admin;

use App\Models\Lead;
use App\Support\AdminPermissions;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Заявки з форми зв'язку.
 *
 * @property-read CrudPanel $crud
 */
class LeadCrudController extends CrudController
{
    use DeleteOperation;
    use ListOperation;
    use ShowOperation;

    public function setup()
    {
        CRUD::setModel(Lead::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/lead');
        CRUD::setEntityNameStrings('заявку', 'заявки');
        AdminPermissions::crud(['list' => 'leads_view', 'show' => 'leads_view', 'delete' => 'leads_delete']);

        // Перемикач «оброблено» – окрема дія (редагування заявок немає).
        if (backpack_user()?->can('leads_update')) {
            CRUD::allowAccess('update');
        }
    }

    protected function setupListOperation()
    {
        CRUD::orderBy('id', 'desc');
        CRUD::removeButton('update');

        CRUD::column('created_at')->type('datetime')->label('Отримано');
        CRUD::column('name')->label('Імʼя');
        CRUD::column('contact')->label('Контакт');
        CRUD::column('message')->label('Повідомлення')->limit(80);
        CRUD::column('is_processed')->type('toggle')->label('Оброблено')->toggle_route($this->crud->route);
    }

    protected function setupShowOperation()
    {
        CRUD::column('created_at')->type('datetime')->label('Отримано');
        CRUD::column('name')->label('Імʼя');
        CRUD::column('contact')->label('Контакт');
        CRUD::column('message')->label('Повідомлення');
        CRUD::column('source_url')->label('Сторінка');
        CRUD::column('ip')->label('IP');
        CRUD::column('is_processed')->type('boolean')->label('Оброблено');
    }

    public function toggleProcessed(int $id)
    {
        CRUD::hasAccessOrFail('update');

        $lead = Lead::findOrFail($id);
        $lead->update(['is_processed' => ! $lead->is_processed]);

        return response()->json(['value' => $lead->is_processed]);
    }
}
