<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
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
        if (! $request->filled('website')) {
            $lead = Lead::create([
                ...$request->safe()->only(['name', 'contact', 'message']),
                'source_url' => $request->sourceUrl(),
                'ip' => $request->ip(),
            ]);

            if ($email = config('site.lead_notification_email')) {
                Mail::to($email)->queue((new LeadReceivedMail($lead))->afterCommit());
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => __('site.contact.success')]);
        }

        return redirect()->to($request->sourceUrl() ?? url('/'))->with('contact_sent', __('site.contact.success'));
    }
}
