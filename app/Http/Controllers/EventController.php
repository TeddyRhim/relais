<?php

namespace App\Http\Controllers;

use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $events = WebhookEvent::with('source')
            ->when($request->integer('source'), fn ($query, $id) => $query->where('source_id', $id))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('events.index', ['events' => $events]);
    }

    public function show(WebhookEvent $event): View
    {
        return view('events.show', [
            'event' => $event->load('source'),
            'replays' => $event->replays()->latest('id')->get(),
        ]);
    }
}
