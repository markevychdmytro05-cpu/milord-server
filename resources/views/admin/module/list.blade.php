@extends(backpack_view('blank'))

@section('header')
    <section class="header-operation container-fluid animated fadeIn d-flex mb-2 align-items-baseline d-print-none">
        <h1 class="text-capitalize mb-0">Модулі</h1>
        <form method="get" class="ms-auto ml-auto">
            <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Пошук…">
        </form>
    </section>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12 module-list">
            @forelse ($templates as $template)
                <div class="card">
                    <div class="card-body">
                        <div class="card-title d-flex justify-content-between align-items-center">
                            <span>{{ $template->name }}</span>
                            @if (backpack_user()->can('modules_create'))
                                <a href="{{ backpack_url('module/create/'.$template->id) }}" class="btn btn-primary">
                                    <i class="la la-plus"></i> Додати модуль
                                </a>
                            @endif
                        </div>

                        @foreach ($template->modules as $module)
                            <div class="module-item d-flex justify-content-between align-items-center">
                                <span class="title">{{ $module->name }}</span>
                                <span class="buttons">
                                    <a href="{{ backpack_url('module/'.$module->id.'/preview') }}" class="btn btn-sm btn-link" target="_blank"><i class="la la-eye"></i> Прев'ю</a>
                                    @if (backpack_user()->can('modules_update'))
                                        <a href="{{ backpack_url('module/'.$module->id.'/edit') }}" class="btn btn-sm btn-link"><i class="la la-edit"></i> Редагувати</a>
                                    @endif
                                    @if (backpack_user()->can('modules_create'))
                                        <form method="post" action="{{ backpack_url('module/'.$module->id.'/copy') }}" class="d-inline">
                                            {!! csrf_field() !!}
                                            <button type="submit" class="btn btn-sm btn-link"><i class="la la-copy"></i> Копіювати</button>
                                        </form>
                                    @endif
                                    @if (backpack_user()->can('modules_delete'))
                                        <form method="post" action="{{ backpack_url('module/'.$module->id) }}" class="d-inline" onsubmit="return confirm('Видалити модуль?')">
                                            {!! csrf_field() !!}
                                            {!! method_field('DELETE') !!}
                                            <button type="submit" class="btn btn-sm btn-link text-danger"><i class="la la-trash"></i> Видалити</button>
                                        </form>
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-muted">Модулів не знайдено.</p>
            @endforelse
        </div>
    </div>
@endsection

@push('after_styles')
    <style>
        .module-item { width: 100%; padding: 10px; background-color: #f1f4f8; margin-top: 12px; }
    </style>
@endpush
