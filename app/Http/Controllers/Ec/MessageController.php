<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        if ($request->filled('read') && is_numeric($request->query('read'))) {
            ContactMessage::where('id', $request->query('read'))->update(['is_read' => true]);

            return redirect()->route('ec.messages');
        }

        if ($request->has('mark_all')) {
            ContactMessage::query()->update(['is_read' => true]);

            return redirect()->route('ec.messages');
        }

        if ($request->filled('delete') && is_numeric($request->query('delete'))) {
            ContactMessage::where('id', $request->query('delete'))->delete();

            return redirect()->route('ec.messages');
        }

        $messages = ContactMessage::orderByDesc('created_at')->get();

        return view('ec.messages', [
            'activePage' => 'messages',
            'messages'   => $messages,
            'unread'     => $messages->where('is_read', false),
        ]);
    }
}
