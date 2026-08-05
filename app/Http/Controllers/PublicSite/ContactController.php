<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ContactController extends Controller
{
    public function index()
    {
        return view('public.contact');
    }

    /** Saves the message and notifies every active EC, matching contact.php. */
    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'name'    => 'required|string|max:150',
            'email'   => 'required|email|max:150',
            'subject' => 'required|string|max:200',
            'message' => 'required|string',
        ], [
            'required' => 'Please fill in all fields.',
            'email.email' => 'Please enter a valid email address.',
        ])->validate();

        ContactMessage::create($data);

        $notifMsg = "New contact message from {$data['name']}: ".Str::limit($data['subject'], 60, '…');
        $notifLink = route('ec.messages');

        User::where('role', 'extension_coordinator')->where('is_active', true)->get()
            ->each(fn ($ec) => Notification::create([
                'user_id' => $ec->id,
                'role'    => 'all',
                'message' => $notifMsg,
                'link'    => $notifLink,
            ]));

        return redirect(route('contact').'#contact-form')->with('success', "Your message has been sent. We'll get back to you soon.");
    }
}
