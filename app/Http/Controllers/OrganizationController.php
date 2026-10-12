<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function show(Request $request): View
    {
        $organization = $request->user()->organization;

        return view('organization.show', [
            'organization' => $organization,
            'members' => $organization->users()->orderBy('name')->get(),
            'invitations' => Invitation::whereNull('accepted_at')->latest()->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);

        $request->user()->organization->update($data);

        return back()->with('status', 'Organisation mise à jour.');
    }
}
