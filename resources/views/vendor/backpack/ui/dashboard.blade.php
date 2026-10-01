@extends(backpack_view('blank'))

@php
    $cards = [];
    if ($activeLicenseCount !== null) {
        $cards[] = ['type' => 'progress', 'class' => 'card', 'accentColor' => 'primary', 'statusBorder' => 'top',
            'value' => $activeLicenseCount, 'description' => 'Активних ліцензій'];
        $cards[] = ['type' => 'progress', 'class' => 'card', 'accentColor' => 'warning', 'statusBorder' => 'top',
            'value' => $expiringSoon->total(), 'description' => 'Закінчуються за 7 днів'];
    }
    if ($onlineDeviceCount !== null) {
        $cards[] = ['type' => 'progress', 'class' => 'card', 'accentColor' => 'success', 'statusBorder' => 'top',
            'value' => $onlineDeviceCount, 'description' => 'Пристроїв онлайн за 24 год'];
    }
    if ($receivedThisMonthCents !== null) {
        $cards[] = ['type' => 'progress', 'class' => 'card', 'accentColor' => 'info', 'statusBorder' => 'top',
            'value' => '$'.number_format($receivedThisMonthCents / 100, 2), 'description' => 'Отримано оплат цього місяця'];
    }
    if ($cards) {
        $widgets['before_content'][] = ['type' => 'div', 'class' => 'row mb-3', 'content' => $cards];
    }
@endphp

@section('content')
    @if ($expiringSoon !== null)
    <div class="card">
        <div class="card-header"><h3 class="card-title">Закінчуються найближчим часом</h3></div>
        @if ($expiringSoon->isEmpty())
            <div class="card-body text-muted">Немає ліцензій, що закінчуються протягом 7 днів.</div>
        @else
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Ключ</th><th>Клієнт</th><th>Контакт</th><th>Акаунтів</th><th>Діє до</th></tr></thead>
                    <tbody>
                        @foreach ($expiringSoon as $license)
                            <tr>
                                <td><a href="{{ backpack_url('license/'.$license->id.'/show') }}"><code>{{ $license->key }}</code></a></td>
                                <td>{{ $license->customer ?: '–' }}</td>
                                <td>{!! $license->contactLink() !!}</td>
                                <td>{{ $license->max_accounts }}</td>
                                <td>{{ $license->expires_at->format('d.m.Y H:i') }} <span class="text-muted">({{ $license->expires_at->diffForHumans() }})</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $expiringSoon->links() }}</div>
        @endif
    </div>
    @else
        <div class="card"><div class="card-body">Оберіть доступний розділ у меню.</div></div>
    @endif
@endsection
