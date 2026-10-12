<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    /**
     * Retire un membre : il garde son compte et repart avec sa propre organisation vide.
     */
    public function destroy(Request $request, int $member): RedirectResponse
    {
        $organization = $request->user()->organization;

        // La recherche passe par l'organisation courante : un identifiant d'une autre organisation donne 404.
        $target = $organization->users()->findOrFail($member);

        abort_if($target->is($request->user()), 422, 'Vous ne pouvez pas vous retirer vous-même.');
        abort_if($target->isOwner(), 422, 'Un propriétaire ne peut pas être retiré.');

        DB::transaction(function () use ($target) {
            $own = Organization::create(['name' => 'Organisation de '.$target->name]);
            $target->forceFill(['organization_id' => $own->getKey(), 'role' => OrganizationRole::Owner])->save();
        });

        return back()->with('status', 'Membre retiré.');
    }
}
