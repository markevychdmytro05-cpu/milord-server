<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\LicenseRequest;
use App\Models\License;
use App\Models\Package;
use App\Support\AdminPermissions;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Prologue\Alerts\Facades\Alert;

/**
 * @property-read CrudPanel $crud
 */
class LicenseCrudController extends CrudController
{
    use CreateOperation { store as traitStore; }
    use DeleteOperation { destroy as traitDestroy; }
    use ListOperation;
    use ShowOperation { show as traitShow; }
    use UpdateOperation { update as traitUpdate; }

    private const STATUSES = [
        License::STATUS_ACTIVE => 'Активна',
        License::STATUS_REVOKED => 'Відкликана',
    ];

    public function setup()
    {
        CRUD::setModel(License::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/license');
        CRUD::setEntityNameStrings('ліцензію', 'ліцензії');

        AdminPermissions::crud([
            'list' => 'licenses_view', 'show' => 'licenses_view',
            'create' => 'licenses_create', 'update' => 'licenses_update', 'delete' => 'licenses_delete',
        ]);
    }

    protected function setupListOperation()
    {
        CRUD::orderBy('id', 'desc');
        if (backpack_user()->can('devices_view')) {
            CRUD::addClause('withCount', 'activations');
        }

        CRUD::column('key')->label('Ключ')->priority(1)->searchLogic(function ($query, $column, $term) {
            $query->orWhere('key', 'like', '%'.$term.'%');
        });
        CRUD::column('customer')->label('Клієнт')->priority(2);
        $this->statusColumn();
        CRUD::column('status')->priority(1);
        CRUD::column('expires_at')->type('datetime')->label('Діє до')->default('безстроково')->priority(1);
        CRUD::column('package_id')->type('select')->label('Пакет')->priority(3)
            ->entity('package')->attribute('name')->model(Package::class);
        CRUD::column('max_accounts')->type('number')->label('Акаунтів')->priority(2);
        if (backpack_user()->can('devices_view')) {
            CRUD::column('devices')->label('Пристрої')->type('closure')->priority(3)
                ->function(fn (License $l) => ($l->activations_count ?? $l->activations()->count()).' / '.$l->max_devices);
        }
        CRUD::column('contact')->label('Контакт')->priority(4)->type('closure')->escaped(false)
            ->function(fn (License $l) => $l->contactLink())
            ->searchLogic(fn ($query, $column, $term) => $query->orWhere('contact', 'like', '%'.$term.'%'));
        CRUD::column('price')->type('number')->label('Ціна')->prefix('$')->priority(4);
        CRUD::column('created_at')->type('datetime')->label('Створено')->priority(5);

        CRUD::addButtonFromView('line', 'reset_devices', 'reset_devices', 'beginning');
    }

    protected function setupShowOperation()
    {
        CRUD::setShowView('admin.license.show');
        CRUD::setOperationSetting('deleteButtonRedirect', fn () => backpack_url('license'));
        CRUD::addButtonFromView('line', 'reset_devices', 'reset_devices', 'beginning');
    }

    public function show(int|string $id): View
    {
        $view = $this->traitShow($id);
        $entry = $view->getData()['entry'];
        $entry->loadMissing(['package', 'creator']);
        $activations = backpack_user()->can('devices_view') ? $entry->activations()->orderBy('id')->get() : collect();

        return $view->with([
            'activations' => $activations,
            'allowedActivationIds' => $activations->take($entry->max_devices)->pluck('id')->all(),
            'payments' => backpack_user()->can('payments_view') ? $entry->payments()->with('recorder')->orderByDesc('id')->paginate(10, ['*'], 'payments_page') : null,
            'audits' => backpack_user()->can('license_history_view') ? $entry->audits()
                ->whereNotIn('event', AdminPermissions::hiddenAuditEvents(backpack_user()))
                ->orderByDesc('id')->paginate(15, ['*'], 'audit_page') : null,
        ]);
    }

    public function store(): RedirectResponse
    {
        return DB::transaction(fn () => $this->traitStore(), attempts: 3);
    }

    public function update(): RedirectResponse
    {
        return DB::transaction(function (): RedirectResponse {
            $id = (int) $this->crud->getRequest()->route('id');
            License::whereKey($id)->lockForUpdate()->firstOrFail();
            $this->crud->getRequest()->merge(['id' => $id]);

            return $this->traitUpdate();
        }, attempts: 3);
    }

    public function destroy(int|string $id): string|JsonResponse
    {
        CRUD::hasAccessOrFail('delete');

        return DB::transaction(function () use ($id): string|JsonResponse {
            $license = License::whereKey($id)->lockForUpdate()->firstOrFail();

            if ($license->payments()->exists()) {
                return response()->json(['message' => 'Ліцензія має історію оплат. Відкличте її замість видалення.'], 409);
            }

            return $this->traitDestroy($id);
        }, attempts: 3);
    }

    public function resetDevices(int $id): RedirectResponse
    {
        Gate::forUser(backpack_user())->authorize('devices_unbind');

        $count = DB::transaction(function () use ($id): int {
            $license = License::whereKey($id)->lockForUpdate()->firstOrFail();
            $activations = $license->activations()->get();
            $activations->each->delete();

            return $activations->count();
        }, attempts: 3);

        Alert::success("Відв'язано пристроїв: {$count}")->flash();

        return redirect()->back();
    }

    public function unbindDevice(int $id, int $activationId): RedirectResponse
    {
        Gate::forUser(backpack_user())->authorize('devices_unbind');

        DB::transaction(function () use ($id, $activationId): void {
            License::whereKey($id)->lockForUpdate()->firstOrFail()
                ->activations()->whereKey($activationId)->firstOrFail()->delete();
        }, attempts: 3);

        Alert::success('Пристрій відв\'язано')->flash();

        return redirect()->back();
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'package_id' => ['required', Rule::exists('packages', 'id')->where('is_active', true)],
            'customer' => ['required', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string'],
        ], [
            'package_id.required' => 'Оберіть пакет.',
            'customer.required' => 'Вкажіть клієнта.',
        ]);

        CRUD::field('package_id')->type('select')->label('Пакет')
            ->entity('package')->attribute('label')->model(Package::class)
            ->options(fn ($query) => $query->available()->get());
        $this->customerFields();
        CRUD::field('price')->type('number')->label('Ціна зі знижкою, $')->prefix('$')
            ->hint('Порожньо – ціна пакета.');
        CRUD::field('note')->type('textarea')->label('Нотатка');
    }

    protected function setupUpdateOperation()
    {
        CRUD::setValidation(LicenseRequest::class);

        $entry = $this->crud->getCurrentEntry();

        CRUD::field('key_display')->type('custom_html')->value(
            '<label class="form-label">Ключ</label><div><code class="fs-3">'.e($entry->key).'</code></div>'
        );
        CRUD::field('package_id')->type('select')->label('Пакет')
            ->entity('package')->attribute('label')->model(Package::class)
            ->options(fn ($query) => $query->where('is_active', true)->orWhere('id', $entry->package_id)->orderBy('price')->get());
        $this->customerFields();
        CRUD::field('price')->type('number')->label('Ціна, $')->prefix('$')
            ->hint('Порожньо – ціна пакета.');
        CRUD::field('status')->type('select_from_array')->label('Статус')
            ->options(self::STATUSES)->allows_null(false);
        CRUD::field('expires_at')->type('datetime')->label('Діє до')
            ->hint('Порожньо – безстроково.');
        CRUD::field('note')->type('textarea')->label('Нотатка');
    }

    private function customerFields(): void
    {
        CRUD::field('customer')->label('Клієнт');
        CRUD::field('contact')->label('Контакт');
    }

    private function statusColumn(): void
    {
        CRUD::column('status')->label('Статус')->type('closure')->escaped(false)
            ->function(function (License $l) {
                [$text, $class] = match ($l->invalidReason()) {
                    'license_revoked' => ['Відкликана', 'bg-danger'],
                    'license_expired' => ['Прострочена', 'bg-warning'],
                    default => ['Активна', 'bg-success'],
                };

                return '<span class="badge '.$class.' text-white">'.$text.'</span>';
            });
    }
}
