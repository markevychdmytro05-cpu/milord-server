<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PageRobotsType;
use App\Http\Requests\PageRequest;
use App\Models\Category;
use App\Models\Module;
use App\Models\Page;
use App\Support\AdminPermissions;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/** @property-read CrudPanel $crud */
class PageCrudController extends CrudController
{
    use CreateOperation;
    use DeleteOperation { destroy as traitDestroy; }
    use ListOperation;
    use UpdateOperation { update as traitUpdate; }

    public function setup()
    {
        CRUD::setModel(Page::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/page');
        CRUD::setEntityNameStrings('сторінку', 'сторінки');
        AdminPermissions::crud([
            'list' => 'pages_view', 'create' => 'pages_create',
            'update' => 'pages_update', 'delete' => 'pages_delete',
        ]);
    }

    protected function setupListOperation()
    {
        CRUD::orderBy('id');

        CRUD::column('title')->label('Заголовок');
        CRUD::column('slug')->label('Адреса')->type('closure')->function(fn (Page $page) => parse_url($page->url(), PHP_URL_PATH) ?: '/');
        CRUD::column('is_published')->type('toggle')->label('Опублікована')->toggle_route($this->crud->route);
        CRUD::column('updated_at')->type('datetime')->label('Змінено');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(PageRequest::class);
        CRUD::setOperationSetting('contentClass', 'col-md-12');

        CRUD::field('title')->label('Заголовок (H1)')->tab('Основне');
        CRUD::field('slug')->type('slug_input')->label('Адреса (slug)')->tab('Основне')->source('title');
        CRUD::field('excerpt')->type('textarea')->label('Короткий опис')->tab('Основне');
        CRUD::field('show_title')->type('switch')->label('Показувати заголовок і короткий опис на сторінці')->tab('Основне')->default(true);
        CRUD::field('category')->type('select_from_array')->label('Категорія (для бази знань)')->tab('Основне')
            ->options(Category::asSelectArray())->allows_null(true)->hint('Категорії створюються в меню «Вміст сайту → Категорії».')->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('published_at')->type('datetime')->label('Дата статті')->tab('Основне')->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('content')->type('summernote')->label('Зміст')->tab('Основне')->options(['height' => 450]);
        CRUD::field('is_published')->type('switch')->label('Опублікована')->tab('Основне');

        CRUD::field('meta_title')->label('SEO-заголовок (title)')->tab('SEO');
        CRUD::field('meta_description')->type('textarea')->label('SEO-опис (description)')->tab('SEO');
        CRUD::field('og_image')->type('browse')->label('Зображення для соцмереж')->tab('SEO')->mime_types(['image']);
        CRUD::field('robots')->type('select_from_array')->label('Індексація (robots)')->tab('SEO')
            ->options(PageRobotsType::asSelectArray())->allows_null(false)->default(PageRobotsType::IndexFollow->value)
            ->hint('index — показувати в пошуку, noindex — сховати; follow — переходити за посиланнями, nofollow — ні.');

        CRUD::field('page_modules')->type('select_and_order')->label('Модулі')->tab('Модулі')
            ->options(Module::getModulesList());
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function destroy($id)
    {
        CRUD::hasAccessOrFail('delete');

        if (Page::findOrFail($id)->isHome()) {
            return response()->json(['message' => 'Головну сторінку не можна видалити.'], 409);
        }

        return $this->traitDestroy($id);
    }

    public function update()
    {
        $this->crud->getRequest()->merge(['id' => $this->crud->getRequest()->route('id')]);

        return $this->traitUpdate();
    }

    public function togglePublished(int $id)
    {
        CRUD::hasAccessOrFail('update');

        $page = Page::findOrFail($id);
        $page->update(['is_published' => ! $page->is_published]);

        return response()->json(['value' => $page->is_published]);
    }
}
