<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\TranslationRequest;
use App\Models\LanguageLine;
use App\Services\TranslationSyncService;
use App\Support\AdminPermissions;
use App\Support\SiteLocale;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\RedirectResponse;

/** @property-read CrudPanel $crud */
class TranslationCrudController extends CrudController
{
    use CreateOperation;
    use DeleteOperation;
    use ListOperation;
    use UpdateOperation { update as traitUpdate; }

    public function setup()
    {
        CRUD::setModel(LanguageLine::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/translation');
        CRUD::setEntityNameStrings('переклад', 'переклади');
        AdminPermissions::crud([
            'list' => 'translations_view', 'create' => 'translations_create',
            'update' => 'translations_update', 'delete' => 'translations_delete',
        ]);
    }

    protected function setupListOperation()
    {
        CRUD::orderBy('group')->orderBy('key');

        CRUD::column('group')->label('Група');
        CRUD::column('key')->label('Ключ');

        foreach (SiteLocale::all() as $locale) {
            CRUD::column('text_'.$locale)->label(strtoupper($locale))->type('closure')
                ->function(fn (LanguageLine $line) => mb_strimwidth((string) ($line->text[$locale] ?? ''), 0, 90, '…'))
                ->searchLogic(fn ($query, $column, $term) => $query->orWhere('text', 'like', '%'.$term.'%'));
        }

        CRUD::column('updated_at')->type('datetime')->label('Змінено');

        if (backpack_user()?->can('translations_update')) {
            CRUD::addButtonFromView('top', 'translations_tools', 'translations_tools', 'end');
        }
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(TranslationRequest::class);

        CRUD::field('group')->label('Група (файл lang/{мова}/{група}.php)')->hint('Наприклад: site, coins, validation.')
            ->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('key')->label('Ключ')->hint('Вкладені ключі через крапку: card.alt.')
            ->wrapper(['class' => 'form-group col-md-8']);
        CRUD::field('text')->type('translation_texts')->label('Переклади')
            ->hint('Порожня мова не зберігається: діє значення з файлу lang/ або мови за замовчуванням. Змінні виду :name залишайте без змін.');
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function update()
    {
        $this->crud->getRequest()->merge(['id' => $this->crud->getRequest()->route('id')]);

        return $this->traitUpdate();
    }

    /** Додає в базу ключі з файлів lang/ (наявні правки не змінюються). */
    public function sync(TranslationSyncService $service): RedirectResponse
    {
        CRUD::hasAccessOrFail('update');

        $result = $service->importFromFiles();

        return back()->with('success', "Синхронізовано з файлами: нових рядків {$result['created']}, доповнено мовами {$result['updated']}.");
    }

    /** Записує переклади з бази у файли lang/, щоб їх можна було закомітити. */
    public function export(TranslationSyncService $service): RedirectResponse
    {
        CRUD::hasAccessOrFail('update');

        return back()->with('success', 'Записано файлів lang/: '.$service->exportToFiles().'.');
    }
}
