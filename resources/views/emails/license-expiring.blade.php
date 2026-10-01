<p>Вітаємо, {{ $license->customer }}!</p>
<p>Строк дії вашої ліцензії завершується {{ $license->expires_at->format('d.m.Y о H:i') }}.</p>
<p>Щоб продовжити доступ, зв’яжіться з нами через сайт: <a href="{{ route('home') }}">{{ route('home') }}</a>.</p>
