<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'source' => ['sometimes', 'integer'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $page = WebhookEvent::query()
            ->when($request->integer('source'), fn ($query, $id) => $query->where('source_id', $id))
            ->latest('id')
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();

        return response()->json([
            'data' => $page->getCollection()->map(fn (WebhookEvent $event) => $this->summary($event))->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ],
            'links' => ['next' => $page->nextPageUrl(), 'prev' => $page->previousPageUrl()],
        ]);
    }

    public function show(WebhookEvent $event): JsonResponse
    {
        return response()->json(['data' => $this->summary($event) + [
            'headers' => $event->headers,
            'body' => $event->body,
        ]]);
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(WebhookEvent $event): array
    {
        return [
            'id' => $event->id,
            'source_id' => $event->source_id,
            'method' => $event->method,
            'content_type' => $event->content_type,
            'signature_status' => $event->signature_status,
            'size' => strlen($event->body),
            'created_at' => $event->created_at,
        ];
    }
}
