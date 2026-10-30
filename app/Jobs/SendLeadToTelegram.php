<?php

namespace App\Jobs;

use App\Models\Lead;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class SendLeadToTelegram implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 60];

    public function __construct(public Lead $lead) {}

    public function handle(): void
    {
        $token = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (! $token || ! $chatId) {
            return;
        }

        Http::timeout(10)
            ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $this->text(),
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ])
            ->throw();
    }

    private function text(): string
    {
        $lines = [
            '<b>Нова заявка з сайту</b>',
            '',
            '<b>Імʼя:</b> '.e($this->lead->name),
            '<b>Контакт:</b> '.e($this->lead->contact),
        ];

        if (filled($this->lead->message)) {
            $lines[] = '<b>Повідомлення:</b> '.e($this->lead->message);
        }

        if (filled($this->lead->source_url)) {
            $lines[] = '<b>Сторінка:</b> '.e($this->lead->source_url);
        }

        $lines[] = '<b>Час:</b> '.$this->lead->created_at->timezone('Europe/Kyiv')->format('d.m.Y H:i');

        return implode("\n", $lines);
    }
}
