<?php

use Illuminate\Support\Facades\Route;

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::crud('license', 'LicenseCrudController');
    Route::post('license/{id}/payments', 'LicensePaymentController@store')->whereNumber('id')->name('license.payments.store');
    Route::post('license/{id}/payments/{paymentId}/void', 'LicensePaymentController@void')->whereNumber(['id', 'paymentId'])->name('license.payments.void');
    Route::crud('license-audit', 'LicenseAuditCrudController');
    Route::post('license/{id}/reset-devices', 'LicenseCrudController@resetDevices')->whereNumber('id');
    Route::post('package/{id}/toggle-home', 'PackageCrudController@toggleShowOnHome')->whereNumber('id');
    Route::post('license/{id}/devices/{activationId}/unbind', 'LicenseCrudController@unbindDevice')->whereNumber(['id', 'activationId']);
    Route::crud('license-activation', 'LicenseActivationCrudController');
    Route::crud('user', 'UserCrudController');
    Route::post('user/{id}/toggle', 'UserCrudController@toggleActive')->whereNumber('id');
    Route::crud('role', 'RoleCrudController');
    Route::crud('package', 'PackageCrudController');
    Route::crud('page', 'PageCrudController');
    Route::post('page/{id}/toggle', 'PageCrudController@togglePublished')->whereNumber('id');
    Route::crud('coin', 'CoinCrudController');
    Route::post('coin/{id}/toggle', 'CoinCrudController@togglePublished')->whereNumber('id');
    Route::crud('translation', 'TranslationCrudController');
    Route::post('translation/sync', 'TranslationCrudController@sync');
    Route::post('translation/export', 'TranslationCrudController@export');
    Route::crud('module', 'ModuleCrudController');
    Route::get('module', 'ModuleCrudController@index')->name('module.index');
    Route::get('module/create/{template}', 'ModuleCrudController@create')->whereNumber('template')->name('module.create');
    Route::post('module/{template}', 'ModuleCrudController@store')->whereNumber('template')->name('module.store');
    Route::get('module/{module}/edit', 'ModuleCrudController@edit')->whereNumber('module')->name('module.edit');
    Route::put('module/{module}', 'ModuleCrudController@update')->whereNumber('module')->name('module.update');
    Route::get('module/{module}/preview', 'ModuleCrudController@preview')->whereNumber('module')->name('module.preview');
    Route::post('module/{module}/copy', 'ModuleCrudController@copy')->whereNumber('module')->name('module.copy');
    Route::crud('category', 'CategoryCrudController');
    Route::crud('lead', 'LeadCrudController');
    Route::post('lead/{id}/toggle', 'LeadCrudController@toggleProcessed')->whereNumber('id');
    Route::crud('site-setting', 'SiteSettingCrudController');
    Route::crud('header-menu', 'HeaderMenuCrudController');
    Route::crud('footer-menu', 'FooterMenuCrudController');
    Route::post('header-menu/{id}/toggle', 'HeaderMenuCrudController@toggleActive')->whereNumber('id');
    Route::post('footer-menu/{id}/toggle', 'FooterMenuCrudController@toggleActive')->whereNumber('id');
    Route::post('package/{id}/toggle', 'PackageCrudController@toggleActive')->whereNumber('id');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
