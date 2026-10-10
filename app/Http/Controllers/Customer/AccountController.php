<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    /**
     * Show the signed-in customer's account and profile settings.
     */
    public function __invoke(Request $request): View
    {
        return view('account.index', ['user' => $request->user()]);
    }
}
