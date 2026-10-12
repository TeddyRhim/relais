<?php

namespace App\Http\Controllers;

use App\Billing\BillingService;
use App\Billing\Plan;
use App\Billing\Usage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function show(Request $request, Usage $usage): View
    {
        $organization = $request->user()->organization;

        return view('billing.show', [
            'organization' => $organization,
            'plans' => Plan::cases(),
            'used' => $usage->used($organization),
            'limit' => $usage->limit($organization),
            'percent' => $usage->percent($organization),
        ]);
    }

    public function update(Request $request, BillingService $billing): RedirectResponse
    {
        $data = $request->validate(['plan' => ['required', Rule::enum(Plan::class)]]);

        $redirect = $billing->subscribe($request->user()->organization, Plan::from($data['plan']));

        return $redirect
            ? redirect()->away($redirect)
            : redirect()->route('billing.show')->with('status', 'Plan mis à jour.');
    }
}
