<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use Illuminate\Contracts\View\View;

class TicketController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Registration $registration): View
    {
        return view('tickets.show', compact('registration'));
    }
}
