<?php

namespace App\Http\Controllers;

use App\Billing\Usage;
use App\Models\Replay;
use App\Models\Source;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const CHART_DAYS = 14;

    public function __invoke(Request $request, Usage $usage): View
    {
        $organization = $request->user()->organization;

        return view('dashboard', [
            'organization' => $organization,
            'sourcesCount' => Source::count(),
            'repliesCount' => Replay::count(),
            'used' => $usage->used($organization),
            'limit' => $usage->limit($organization),
            'percent' => $usage->percent($organization),
            'days' => $this->eventsPerDay(),
            'recent' => WebhookEvent::with('source')->latest('id')->limit(6)->get(),
        ]);
    }

    /**
     * Événements reçus par jour sur les derniers jours, jours vides compris.
     *
     * @return list<array{label: string, count: int}>
     */
    private function eventsPerDay(): array
    {
        $start = now()->subDays(self::CHART_DAYS - 1)->startOfDay();

        // Regroupement en PHP : identique sur SQLite et PostgreSQL, volume borné par le quota mensuel.
        $counts = WebhookEvent::query()
            ->where('created_at', '>=', $start)
            ->pluck('created_at')
            ->countBy(fn ($date) => $date->format('Y-m-d'));

        return collect(range(0, self::CHART_DAYS - 1))->map(function (int $i) use ($start, $counts) {
            $day = $start->copy()->addDays($i);

            return ['label' => $day->format('d/m'), 'count' => (int) ($counts[$day->format('Y-m-d')] ?? 0)];
        })->all();
    }
}
