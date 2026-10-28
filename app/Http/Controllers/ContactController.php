<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Jobs\SendLeadToTelegram;
use App\Mail\LeadReceivedMail;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(ContactRequest $request): JsonResponse|RedirectResponse
    {
        // Заповнена приманка — бот: вдаємо успіх, але заявку не зберігаємо.
        // Назва поля навмисно беззмістовна: «website» підставляли менеджери паролів, і живі заявки губилися.
        if (! $request->filled('nb_hp')) {
            $lead = Lead::create([
                ...$request->safe()->only(['name', 'contact', 'message']),
                'source_url' => $request->sourceUrl(),
                'ip' => $request->ip(),
            ]);

            if ($email = config('site.lead_notification_email')) {
                Mail::to($email)->queue((new LeadReceivedMail($lead))->afterCommit());
            }

            if (config('services.telegram.bot_token') && config('services.telegram.chat_id')) {
                SendLeadToTelegram::dispatch($lead)->afterCommit();
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => __('site.contact.success')]);
        }

        return redirect()->to($request->sourceUrl() ?? url('/'))->with('contact_sent', __('site.contact.success'));
    }
}
