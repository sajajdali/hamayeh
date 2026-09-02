<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        auth('web')->logout();
        auth('blogger')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('panel.login');
    }
}
