<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\SiteSettingRequest;
use App\Models\Module;
use App\Models\SiteSetting;
use App\Support\AdminPermissions;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Шапка й підвал публічного сайту. Єдиний запис, тільки редагування.
 *
 * @property-read CrudPanel $crud
 */
class SiteSettingCrudController extends CrudController
{
    use UpdateOperation { update as traitUpdate; }

    public function setup()
    {
        CRUD::setModel(SiteSetting::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/site-setting');
        CRUD::setEntityNameStrings('налаштування сайту', 'налаштування сайту');
        AdminPermissions::crud(['update' => 'site_manage']);
    }

    protected function setupUpdateOperation()
    {
        CRUD::setOperationSetting('contentClass', 'col-md-10 mx-auto');
        CRUD::setValidation(SiteSettingRequest::class);

        CRUD::field('header_logo')->label('Логотип (текст)')->tab('Шапка');
        CRUD::field('logo_image')->type('browse')->label('Логотип (зображення)')->tab('Шапка')->mime_types(['image']);
        CRUD::field('header_button_text')->label('Кнопка в шапці: текст')->tab('Шапка')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('header_button_url')->label('Кнопка в шапці: адреса')->tab('Шапка')->wrapper(['class' => 'form-group col-md-8']);

        CRUD::field('show_topbar')->type('switch')->label('Показувати верхню смугу з контактами')->tab('Шапка');
        CRUD::field('footer_text')->label('Текст підвалу')->tab('Підвал');

        CRUD::field('footer_heading')->label('Заголовок блоку контактів у футері')->tab('Контакти');
        CRUD::field('contact_email')->label('Email')->tab('Контакти')->wrapper(['class' => 'form-group col-md-8']);
        CRUD::field('contact_email_label')->label('Підпис')->tab('Контакти')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('contact_email2')->label('Другий email')->tab('Контакти')->wrapper(['class' => 'form-group col-md-8']);
        CRUD::field('contact_email2_label')->label('Підпис')->tab('Контакти')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('contact_phone')->label('Телефон')->tab('Контакти');
        CRUD::field('contact_address')->label('Адреса')->tab('Контакти');
        CRUD::field('instagram')->label('Instagram (посилання)')->tab('Контакти');
        CRUD::field('facebook')->label('Facebook (посилання)')->tab('Контакти');
        CRUD::field('telegram')->label('Telegram (@нік або посилання)')->tab('Контакти');
        CRUD::field('whatsapp')->label('WhatsApp (номер телефону)')->tab('Контакти');
        CRUD::field('viber')->label('Viber (номер телефону)')->tab('Контакти');

        CRUD::field('footer_modules')->type('select_and_order')->label('Модулі підвалу')->tab('Підвал')
            ->options(Module::getModulesList());
    }

    public function update()
    {
        $this->crud->getRequest()->merge(['id' => $this->crud->getRequest()->route('id')]);

        return $this->traitUpdate();
    }
}
