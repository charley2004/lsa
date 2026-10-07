<?php

namespace App\Http\Controllers;

use App\Mail\NewContactMessage;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function show()
    {
        return view('pages.contact');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'in:general,analysis,feedback,technical,partnership,other'],
            'message' => ['required', 'string', 'max:5000'],
            'consent' => ['accepted'],
        ]);

        $contactMessage = ContactMessage::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
        ]);

        // The message is already saved and visible in /admin regardless of what
        // happens here — a mail/SMTP outage should never block the form or lose
        // the submission, just the notification about it.
        try {
            Mail::to(config('lsa.admin_email'))->send(new NewContactMessage($contactMessage));
        } catch (\Throwable $e) {
            Log::warning('Contact notification email failed to send', ['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('contact')
            ->with('status', "Thanks, {$validated['name']} — we've received your message and will reply within 24-48 hours.");
    }
}
