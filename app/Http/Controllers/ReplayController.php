<?php

namespace App\Http\Controllers;

use App\Jobs\SendReplay;
use App\Models\Replay;
use App\Models\WebhookEvent;
use App\Rules\SafeTargetUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReplayController extends Controller
{
    public function store(Request $request, WebhookEvent $event): RedirectResponse
    {
        $request->merge(['target_url' => $request->input('target_url') ?: $event->source->target_url]);

        $data = $request->validate([
            'target_url' => ['required', 'string', 'max:2000', new SafeTargetUrl],
        ]);

        $replay = Replay::create([
            'webhook_event_id' => $event->id,
            'target_url' => $data['target_url'],
        ]);

        SendReplay::dispatch($replay->id, $replay->organization_id);

        return redirect()->route('events.show', $event)->with('status', 'Rejeu mis en file.');
    }
}
