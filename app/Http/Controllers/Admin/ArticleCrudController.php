<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ArticleRequest;
use App\Models\Page;
use App\Support\AdminPermissions;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class ArticleCrudController extends PageCrudController
{
    public function setup()
    {
        CRUD::setModel(Page::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/article');
        CRUD::setEntityNameStrings('статтю', 'статті блогу');
        AdminPermissions::crud([
            'list' => 'pages_view', 'create' => 'pages_create',
            'update' => 'pages_update', 'delete' => 'pages_delete',
        ]);

        CRUD::addClause('where', 'type', Page::TYPE_PAGE);
        CRUD::addClause('whereNotNull', 'category');
        CRUD::addClause('where', 'category', '!=', '');
    }

    protected function setupListOperation()
    {
        CRUD::orderBy('published_at', 'desc');
        CRUD::orderBy('id', 'desc');

        CRUD::column('title')->label('Заголовок');
        CRUD::column('category')->label('Категорія');
        CRUD::column('published_at')->type('datetime')->label('Дата статті');
        CRUD::column('slug')->label('Адреса')->type('closure')->function(fn (Page $page) => parse_url($page->url(), PHP_URL_PATH) ?: '/');
        CRUD::column('is_published')->type('toggle')->label('Опублікована')->toggle_route($this->crud->route);
        CRUD::column('updated_at')->type('datetime')->label('Змінено');
    }

    protected function setupCreateOperation()
    {
        parent::setupCreateOperation();

        CRUD::setValidation(ArticleRequest::class);
        CRUD::field('slug')->type('slug_input')->label('Адреса (slug)')->tab('Основне')->source('title')
            ->hint('Не змінюйте адресу в опублікованої статті: вона вже може бути в індексі Google, а автоматичного 301-редиректу зі старої адреси немає.');
        CRUD::field('category')->label('Категорія')->hint('Без категорії матеріал не стане статтею блогу. Категорії створюються в меню «Вміст сайту → Категорії».');
        CRUD::field('is_published')->type('switch')->label('Опублікована')->tab('Основне')->default(true);
    }
}
