<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/** EC accounts are provisioned through controlled access, never public signup. */
class RegisteredEcController extends Controller
{
    public function create()
    {
        abort(404);
    }

    public function store(Request $request)
    {
        abort(404);
    }
}
