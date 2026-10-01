{{-- Спільна форма створення/редагування модуля: поля будуються зі схеми шаблону. --}}
@include('crud::inc.grouped_errors')

<form method="post" enctype="multipart/form-data" action="{{ $formAction }}" id="crudForm">
    {!! csrf_field() !!}
    @if (! empty($formMethod))
        {!! method_field($formMethod) !!}
    @endif

    @include('crud::form_content', ['fields' => $crud->fields(), 'action' => $formMethod ? 'edit' : 'create'])

    <div class="mt-3 d-print-none">
        <button type="submit" class="btn btn-success"><i class="la la-save"></i> Зберегти</button>
        <a href="{{ backpack_url('module') }}" class="btn btn-link">Скасувати</a>
    </div>
</form>
