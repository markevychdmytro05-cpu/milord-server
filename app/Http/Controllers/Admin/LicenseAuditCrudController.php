<?php

namespace App\Http\Controllers\Admin;

use App\Models\LicenseAudit;
use App\Support\AdminPermissions;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class LicenseAuditCrudController extends CrudController
{
    use ListOperation;
    use ShowOperation;

    public function setup(): void
    {
        abort_unless(backpack_user()?->can('audit_view'), 403);

        CRUD::setModel(LicenseAudit::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/license-audit');
        CRUD::setEntityNameStrings('подію', 'журнал дій');
        CRUD::addClause('whereNotIn', 'event', AdminPermissions::hiddenAuditEvents(backpack_user()));
    }

    protected function setupListOperation(): void
    {
        CRUD::orderBy('id', 'desc');
        CRUD::column('created_at')->type('datetime')->label('Коли');
        CRUD::column('license_number')->type('number')->label('ID ліцензії');
        CRUD::column('actor_name')->label('Хто');
        CRUD::column('event')->type('select_from_array')->options(LicenseAudit::EVENTS)->label('Подія');
    }

    protected function setupShowOperation(): void
    {
        $this->setupListOperation();
        CRUD::column('changes')->type('closure')->label('Зміни')
            ->function(fn (LicenseAudit $audit): string => json_encode($audit->changes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        CRUD::column('metadata')->type('closure')->label('Деталі')
            ->function(fn (LicenseAudit $audit): string => json_encode($audit->metadata, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
