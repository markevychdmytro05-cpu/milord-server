<?php

namespace App\Http\Controllers\Admin;

use App\Models\License;
use App\Models\LicenseActivation;
use App\Support\AdminPermissions;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Facades\DB;

/** @property-read CrudPanel $crud */
class LicenseActivationCrudController extends CrudController
{
    use DeleteOperation { destroy as traitDestroy; }
    use ListOperation;

    public function setup()
    {
        CRUD::setModel(LicenseActivation::class);
        CRUD::with('license');
        CRUD::setRoute(config('backpack.base.route_prefix').'/license-activation');
        CRUD::setEntityNameStrings('активацію', 'активації');
        AdminPermissions::crud(['list' => 'devices_view', 'delete' => 'devices_unbind']);
    }

    protected function setupListOperation()
    {
        CRUD::orderBy('last_seen_at', 'desc');

        CRUD::column('license_id')->type('select')->label('Ключ')
            ->entity('license')->attribute('key')->model(License::class)
            ->wrapper(['href' => fn ($crud, $column, $entry) => backpack_url('license/'.$entry->license_id.'/show')]);
        CRUD::column('device_name')->label('Пристрій');
        CRUD::column('device_id')->label('Device ID')->limit(24);
        CRUD::column('accounts_used')->type('closure')->label('Акаунтів')
            ->function(fn ($a) => ($a->accounts_used ?? '–').' / '.$a->license->max_accounts);
        CRUD::column('app_version')->label('Версія');
        CRUD::column('ip')->label('IP');
        CRUD::column('last_seen_at')->type('datetime')->label('Остання перевірка');
        CRUD::column('created_at')->type('datetime')->label('Активовано');
    }

    public function destroy(int|string $id): string
    {
        CRUD::hasAccessOrFail('delete');

        return DB::transaction(function () use ($id): string {
            $activation = LicenseActivation::findOrFail($id);
            License::whereKey($activation->license_id)->lockForUpdate()->firstOrFail();

            return $this->traitDestroy($id);
        }, attempts: 3);
    }
}
