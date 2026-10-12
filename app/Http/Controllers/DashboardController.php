<?php

namespace App\Http\Controllers;

use App\Billing\Usage;
use App\Models\Replay;
use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
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
        ]);
    }
}
