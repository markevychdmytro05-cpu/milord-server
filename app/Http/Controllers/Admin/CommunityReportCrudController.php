<?php

namespace App\Http\Controllers\Admin;

use App\Models\CommunityReport;
use App\Support\AdminPermissions;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class CommunityReportCrudController extends CrudController
{
    use DeleteOperation;
    use ListOperation;

    public function setup()
    {
        CRUD::setModel(CommunityReport::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/community-report');
        CRUD::setEntityNameStrings('скаргу', 'скарги спільноти');
        AdminPermissions::crud(['list' => 'community_view', 'delete' => 'community_manage']);

        if (backpack_user()?->can('community_manage')) {
            CRUD::allowAccess('update');
        }
    }

    protected function setupListOperation()
    {
        CRUD::with(['topic', 'license']);
        CRUD::orderBy('is_processed')->orderBy('id', 'desc');
        CRUD::removeButton('update');

        CRUD::column('created_at')->type('datetime')->label('Отримано')->priority(2);
        CRUD::column('topic')->label('Тема')->type('closure')->function(fn (CommunityReport $report) => $report->topic?->title)->priority(1);
        CRUD::column('reason')->label('Причина')->limit(120)->priority(1);
        CRUD::column('license')->label('Клієнт')->type('closure')->function(fn (CommunityReport $report) => $report->license?->customer)->priority(3);
        CRUD::column('is_processed')->type('toggle')->label('Оброблено')->toggle_route($this->crud->route)->priority(1);
    }

    public function toggleProcessed(int $id)
    {
        CRUD::hasAccessOrFail('update');

        $report = CommunityReport::findOrFail($id);
        $report->update(['is_processed' => ! $report->is_processed]);

        return response()->json(['value' => $report->is_processed]);
    }
}
