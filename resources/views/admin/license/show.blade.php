@extends(backpack_view('blank'))

@php
    /** @var \App\Models\License $entry */
    $breadcrumbs = [
        trans('backpack::crud.admin') => backpack_url('dashboard'),
        $crud->entity_name_plural => url($crud->route),
        trans('backpack::crud.preview') => false,
    ];

    [$statusText, $statusClass] = match ($entry->invalidReason()) {
        'license_revoked' => ['Відкликана', 'bg-danger'],
        'license_expired' => ['Прострочена', 'bg-warning'],
        default => ['Активна', 'bg-success'],
    };

    $details = [
        'Пакет' => e($entry->package?->name ?? 'без пакета'),
        'Клієнт' => e($entry->customer ?: '–'),
        'Контакт' => $entry->contactLink(),
        'Акаунтів' => $entry->max_accounts,
        'Пристрої' => $activations->count().' з '.$entry->max_devices,
        'Ціна' => $entry->price !== null ? '$'.$entry->price : '–',
        'Діє до' => $entry->expires_at
            ? $entry->expires_at->format('d.m.Y H:i').' <span class="text-muted">('.$entry->expires_at->diffForHumans().')</span>'
            : 'безстроково',
        'Видав' => e($entry->creator?->name ?? '–'),
        'Створено' => $entry->created_at->format('d.m.Y H:i'),
        'Змінено' => $entry->updated_at->format('d.m.Y H:i'),
    ];
@endphp

@section('header')
    <div class="container-fluid my-3">
        <p class="mb-1 d-print-none">
            <small><a href="{{ url($crud->route) }}"><i class="la la-angle-double-left"></i> Назад до всіх ліцензій</a></small>
        </p>
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <h1 class="mb-0 d-flex align-items-center flex-wrap">
                <code class="mr-3 me-3 text-body">{{ $entry->key }}</code>
                <span class="badge {{ $statusClass }} text-white" style="font-size: 0.9rem">{{ $statusText }}</span>
            </h1>
            <div class="d-print-none">
                @include('crud::inc.button_stack', ['stack' => 'line'])
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><strong>Ліцензія</strong></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        @foreach ($details as $label => $value)
                            <dt class="col-5 text-muted font-weight-normal fw-normal">{{ $label }}</dt>
                            <dd class="col-7">{!! $value !!}</dd>
                        @endforeach
                    </dl>
                    @if ($entry->note)
                        <hr>
                        <div class="text-muted small mb-1">Нотатка</div>
                        <div style="white-space: pre-line">{{ $entry->note }}</div>
                    @endif
                </div>
            </div>
        </div>

        @if (backpack_user()->can('devices_view'))
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><strong>Пристрої</strong> <span class="text-muted">{{ $activations->count() }} з {{ $entry->max_devices }}</span></div>
                @if ($activations->isEmpty())
                    <div class="card-body text-muted">Ключ ще не активовано на жодному пристрої.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="pl-3 ps-3">Пристрій</th>
                                    <th>Акаунтів</th>
                                    <th>Версія</th>
                                    <th>IP</th>
                                    <th>Остання перевірка</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($activations as $activation)
                                    <tr>
                                        <td class="pl-3 ps-3">
                                            <div>{{ $activation->device_name ?: 'Без назви' }}</div>
                                            @if (! in_array($activation->id, $allowedActivationIds, true))
                                                <div class="text-warning small">Поза лімітом поточного пакета</div>
                                            @endif
                                            <small class="text-muted text-nowrap" title="{{ $activation->device_id }}">{{ \Illuminate\Support\Str::limit($activation->device_id, 24) }}</small>
                                        </td>
                                        <td class="text-nowrap">{{ $activation->accounts_used ?? '–' }} / {{ $entry->max_accounts }}</td>
                                        <td>{{ $activation->app_version ?: '–' }}</td>
                                        <td class="text-nowrap">{{ $activation->ip ?: '–' }}</td>
                                        <td class="text-nowrap">
                                            {{ $activation->last_seen_at?->format('d.m.Y H:i') ?? '–' }}
                                            @if ($activation->last_seen_at)
                                                <div><small class="text-muted">{{ $activation->last_seen_at->diffForHumans() }}</small></div>
                                            @endif
                                        </td>
                                        <td class="text-right text-end pr-3 pe-3">
                                            @if (backpack_user()->can('devices_unbind'))
                                                <form method="POST" class="d-inline d-print-none"
                                                      action="{{ url($crud->route.'/'.$entry->getKey().'/devices/'.$activation->getKey().'/unbind') }}"
                                                      onsubmit="return confirm('Відв\'язати цей пристрій?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-link text-warning p-0" title="Відв'язати">
                                                        <i class="la la-unlink"></i> Відв'язати
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>
    @if (backpack_user()->can('payments_view'))
        @include('admin.license.billing')
    @elseif (backpack_user()->can('payments_create'))
        @include('admin.license.billing')
    @endif
    @if (backpack_user()->can('license_history_view'))
        @include('admin.license.history')
    @endif
@endsection
