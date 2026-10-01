<h1>Нова заявка з сайту</h1>
<p><strong>Ім’я:</strong> {{ $lead->name }}</p>
<p><strong>Контакт:</strong> {{ $lead->contact }}</p>
@if ($lead->message)
    <p><strong>Повідомлення:</strong><br>{{ $lead->message }}</p>
@endif
@if ($lead->source_url)
    <p><strong>Сторінка:</strong> {{ $lead->source_url }}</p>
@endif
<p>Заявка №{{ $lead->id }} доступна в адмінпанелі.</p>
