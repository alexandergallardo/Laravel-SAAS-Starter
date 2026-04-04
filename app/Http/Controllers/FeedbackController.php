<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeedbackRequest;
use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;

class FeedbackController extends Controller
{
    public function store(StoreFeedbackRequest $request): RedirectResponse
    {
        $user = $request->user();

        Feedback::create([
            'user_id' => $user->id,
            'workspace_id' => $user->current_workspace_id,
            'type' => $request->validated('type'),
            'message' => $request->validated('message'),
            'page_url' => $request->header('Referer'),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Feedback submitted. Thank you!');
    }
}
