@php
    $fieldLabels = [
        'package_id' => 'ID пакета', 'customer' => 'Клієнт', 'contact' => 'Контакт',
        'max_accounts' => 'Ліміт акаунтів', 'max_devices' => 'Ліміт пристроїв',
        'price' => 'Ціна, USD', 'status' => 'Статус', 'expires_at' => 'Діє до', 'note' => 'Нотатка',
        'device_id' => 'ID пристрою', 'device_name' => 'Пристрій', 'payment_id' => 'ID оплати',
        'amount_cents' => 'Сума, центи', 'currency' => 'Валюта', 'duration_days' => 'Днів',
        'reference' => 'Квитанція', 'reason' => 'Причина',
    ];
@endphp
<div class="card mt-3">
    <div class="card-header"><strong>Історія ліцензії</strong></div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Коли</th><th>Хто</th><th>Подія</th><th>Зміни</th></tr></thead>
            <tbody>
                @forelse ($audits as $audit)
                    <tr>
                        <td class="text-nowrap">{{ $audit->created_at->format('d.m.Y H:i:s') }}</td>
                        <td>{{ $audit->actor_name }}</td>
                        <td>{{ $audit->eventLabel() }}</td>
                        <td>
                            @foreach ($audit->changes ?? [] as $field => $change)
                                <div><strong>{{ $fieldLabels[$field] ?? $field }}:</strong> {{ $change['old'] ?? '–' }} → {{ $change['new'] ?? '–' }}</div>
                            @endforeach
                            @foreach ($audit->metadata ?? [] as $field => $value)
                                <div><strong>{{ $fieldLabels[$field] ?? $field }}:</strong> {{ $value ?? '–' }}</div>
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">Історія з’явиться після нових дій із ліцензією.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $audits->withQueryString()->links() }}</div>
</div>
