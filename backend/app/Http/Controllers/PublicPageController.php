<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Notification;
use App\Models\Training;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicPageController extends Controller
{
    /**
     * Public landing page.
     */
    public function home(): View
    {
        return view('public.home');
    }

    /**
     * Static "About" page.
     */
    public function about(): View
    {
        return view('public.about');
    }

    /**
     * Public training announcements. Only trainings that have actually
     * been announced/run are shown - never 'draft' (not yet finalized)
     * or 'cancelled'. No participant, document, or evaluation data is
     * loaded here, only training-level public fields.
     */
    public function trainings(): View
    {
        $upcoming = Training::whereIn('status', ['scheduled', 'ongoing'])
            ->with('projectLeader')
            ->orderBy('start_date')
            ->get();

        $completed = Training::where('status', 'completed')
            ->with('projectLeader')
            ->orderByDesc('start_date')
            ->get();

        return view('public.trainings', [
            'upcoming' => $upcoming,
            'completed' => $completed,
        ]);
    }

    /**
     * Show the contact form.
     */
    public function contact(): View
    {
        return view('public.contact');
    }

    /**
     * Save a contact message and notify all Extension Coordinators.
     */
    public function submitContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        ContactMessage::create($validated);

        User::where('role', 'extension_coordinator')->pluck('id')->each(
            fn (int $userId) => Notification::notify(
                $userId,
                'New Contact Message',
                "{$validated['name']} sent a message: \"{$validated['subject']}\"",
                'contact_message'
            )
        );

        return redirect()
            ->route('public.contact')
            ->with('status', "Your message has been sent. We'll get back to you soon.");
    }
}
