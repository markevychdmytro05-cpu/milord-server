<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ModuleRequest;
use App\Models\Module;
use App\Models\ModuleTemplate;
use App\Services\ModuleService;
use App\Support\AdminPermissions;
use App\Support\SiteLocale;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Prologue\Alerts\Facades\Alert;

/**
 * Модулі сайту. Список згрупований за шаблонами; форма будується зі схеми шаблону.
 *
 * @property-read CrudPanel $crud
 */
class ModuleCrudController extends CrudController
{
    use DeleteOperation;

    public function __construct(private readonly ModuleService $moduleService)
    {
        parent::__construct();
    }

    public function setup()
    {
        CRUD::setModel(Module::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/module');
        CRUD::setEntityNameStrings('модуль', 'модулі');
        AdminPermissions::crud(['delete' => 'modules_delete']);
    }

    public function index(Request $request): View
    {
        $this->authorizeAbility('modules_view');

        $search = trim((string) $request->input('search'));

        $templates = ModuleTemplate::with(['modules' => fn ($query) => $query
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name'),
        ])
            ->when($search !== '', fn ($query) => $query->whereHas('modules', fn ($query) => $query->where('name', 'like', '%'.$search.'%')))
            ->orderBy('name')
            ->get();

        return view('admin.module.list', ['crud' => $this->crud, 'templates' => $templates, 'search' => $search]);
    }

    public function create(ModuleTemplate $template): View
    {
        $this->authorizeAbility('modules_create');
        $this->addFields($template->fields());

        return view('admin.module.create', ['crud' => $this->crud, 'template' => $template]);
    }

    public function store(ModuleRequest $request, ModuleTemplate $template): RedirectResponse
    {
        $input = $this->moduleService->saveUploads($request->only($template->column_names()), $template);

        $module = $template->makeModule($input['name'] ?? $template->name, $input);

        Alert::success(trans('backpack::crud.insert_success'))->flash();

        return redirect(backpack_url('module/'.$module->getKey().'/edit'));
    }

    public function edit(Module $module): View
    {
        $this->authorizeAbility('modules_update');
        $this->addFields($module->fields());

        return view('admin.module.edit', ['crud' => $this->crud, 'module' => $module]);
    }

    public function update(ModuleRequest $request, Module $module): RedirectResponse
    {
        $template = $module->module_template;
        $input = $this->moduleService->saveUploads($request->only($template->column_names()), $template);

        $module->setting = $this->mergeSetting($module->setting->toArray(), Arr::except($input, ['name']));
        $module->name = $input['name'] ?? $module->name;
        $module->save();

        Alert::success(trans('backpack::crud.update_success'))->flash();

        return redirect(backpack_url('module/'.$module->getKey().'/edit'));
    }

    public function preview(Module $module): View
    {
        $this->authorizeAbility('modules_view');

        return view('admin.module.preview', ['module' => $module]);
    }

    public function copy(Module $module): RedirectResponse
    {
        $this->authorizeAbility('modules_create');

        $copy = $module->replicate();
        $copy->name = $module->name.' (копія)';
        $copy->save();

        return redirect(backpack_url('module'));
    }

    /**
     * Загальні поля замінюються цілком (щоб видалені елементи списків зникали), мовні – по ключах.
     *
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function mergeSetting(array $current, array $input): array
    {
        $merged = array_replace($current, Arr::except($input, SiteLocale::all()));

        foreach (SiteLocale::all() as $locale) {
            if (isset($input[$locale])) {
                $merged[$locale] = array_replace($current[$locale] ?? [], $input[$locale]);
            }
        }

        return $merged;
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     */
    private function addFields(array $fields): void
    {
        foreach ($fields as $field) {
            CRUD::addField($field);
        }
    }

    private function authorizeAbility(string $ability): void
    {
        abort_unless(backpack_user()?->can($ability), 403);
    }
}
