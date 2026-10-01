@extends(backpack_view('blank'))

@section('header')
    <section class="header-operation container-fluid animated fadeIn d-flex mb-2 align-items-baseline d-print-none">
        <h1 class="text-capitalize mb-0">Редагування модуля</h1>
        <p class="ms-2 ml-2 mb-0">{{ $module->module_template->name }}</p>
        <p class="ms-2 ml-2 mb-0"><a href="{{ backpack_url('module') }}" class="font-sm"><i class="la la-angle-double-left"></i> Усі модулі</a></p>
    </section>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8 bold-labels">
            @include('admin.module.form', ['formAction' => url($crud->route.'/'.$module->getKey()), 'formMethod' => 'PUT'])
        </div>
    </div>
@endsection
