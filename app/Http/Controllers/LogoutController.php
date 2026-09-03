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
        $portal = $request->session()->get('login_portal');
        auth('web')->logout();
        auth('blogger')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route(match ($portal) {
            'blogger' => 'blogger.login',
            'sales_manager' => 'sales-manager.login',
            default => 'admin.login',
        });
    }
}
