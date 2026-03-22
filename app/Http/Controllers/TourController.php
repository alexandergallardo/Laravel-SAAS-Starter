<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TourController extends Controller
{
    /**
     * Mark the product tour as completed for the authenticated user.
     */
    public function complete(Request $request): RedirectResponse
    {
        $request->user()->update(['tour_completed_at' => now()]);

        return back();
    }
}
