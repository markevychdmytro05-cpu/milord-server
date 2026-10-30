<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PageRobotsType;
use App\Http\Requests\CoinRequest;
use App\Models\Coin;
use App\Support\AdminPermissions;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class CoinCrudController extends CrudController
{
    use CreateOperation;
    use DeleteOperation;
    use ListOperation;
    use UpdateOperation { update as traitUpdate; }

    public function setup()
    {
        CRUD::setModel(Coin::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/coin');
        CRUD::setEntityNameStrings('монету', 'монети');
        AdminPermissions::crud([
            'list' => 'coins_view', 'create' => 'coins_create',
            'update' => 'coins_update', 'delete' => 'coins_delete',
        ]);
    }

    protected function setupListOperation()
    {
        CRUD::orderBy('id', 'desc');

        CRUD::column('image')->label('Фото')->type('closure')->escaped(false)->orderable(false)->searchable(false)->priority(1)
            ->function(fn (Coin $coin) => $coin->image
                ? '<img src="'.e($coin->image).'" alt="" style="height:48px;width:48px;object-fit:contain;border-radius:8px;background:#f3f4fb">'
                : '<span class="text-muted">—</span>');
        CRUD::column('title')->label('Назва')->priority(1);
        CRUD::column('denomination')->label('Номінал')->priority(3);
        CRUD::column('sale_starts_at')->type('datetime')->label('Початок продажу')->priority(4);
        CRUD::column('slug')->label('Адреса')->type('closure')->function(fn (Coin $coin) => parse_url($coin->url(), PHP_URL_PATH))->priority(5);
        CRUD::column('is_published')->type('toggle')->label('Активний')->toggle_route($this->crud->route)->priority(1);
        CRUD::column('show_on_home')->type('toggle')->label('Показувати на головній')->toggle_route($this->crud->route)->toggle_action('toggle-home')->priority(2);
        CRUD::column('updated_at')->type('datetime')->label('Змінено')->priority(6);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(CoinRequest::class);
        CRUD::setOperationSetting('contentClass', 'col-md-12');

        CRUD::field('title')->label('Назва монети (H1)')->tab('Основне');
        CRUD::field('slug')->type('slug_input')->label('Адреса (slug)')->tab('Основне')->source('title');
        CRUD::field('denomination')->label('Номінал')->tab('Основне')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('metal')->label('Метал')->tab('Основне')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('release_year')->type('number')->label('Рік випуску')->tab('Основне')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('release_month')->type('select_from_array')->label('Місяць випуску (за планом НБУ)')->tab('Основне')
            ->options(Coin::months())->allows_null(true)->hint('Використовується, поки не відома точна дата продажу.');
        CRUD::field('series')->label('Серія')->tab('Основне');
        CRUD::field('mintage')->type('number')->label('Тираж, шт.')->tab('Основне')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('price')->type('number')->label('Ціна, грн')->attributes(['step' => '0.01'])->tab('Основне')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('sale_starts_at')->type('datetime')->label('Початок продажу (за Києвом)')->tab('Основне')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('nbu_url')->type('url')->label('Посилання на сторінку монети в магазині НБУ')->tab('Основне');
        CRUD::field('image_preview')->type('image_preview')->label('Попередній перегляд')->source('image')->tab('Основне');
        CRUD::field('image')->type('browse')->label('Зображення монети')->tab('Основне')->mime_types(['image']);
        CRUD::field('excerpt')->type('textarea')->label('Короткий опис')->tab('Основне');
        CRUD::field('content')->type('summernote')->label('Опис')->tab('Основне')->options(['height' => 400]);
        CRUD::field('is_published')->type('switch')->label('Активний')->tab('Основне');
        CRUD::field('show_on_home')->type('switch')->label('Показувати на головній')->tab('Основне')->default(true);

        CRUD::field('meta_title')->label('SEO-заголовок (title)')->tab('SEO');
        CRUD::field('meta_description')->type('textarea')->label('SEO-опис (description)')->tab('SEO');
        CRUD::field('og_image')->type('browse')->label('Зображення для соцмереж')->tab('SEO')->mime_types(['image'])
            ->hint('Якщо порожньо, береться зображення монети.');
        CRUD::field('robots')->type('select_from_array')->label('Індексація (robots)')->tab('SEO')
            ->options(PageRobotsType::asSelectArray())->allows_null(false)->default(PageRobotsType::IndexFollow->value);
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

    public function togglePublished(int $id)
    {
        return $this->toggleField($id, 'is_published');
    }

    public function toggleShowOnHome(int $id)
    {
        return $this->toggleField($id, 'show_on_home');
    }

    private function toggleField(int $id, string $field)
    {
        CRUD::hasAccessOrFail('update');

        $coin = Coin::findOrFail($id);
        $coin->update([$field => ! $coin->{$field}]);

        return response()->json(['value' => $coin->{$field}]);
    }
}
