<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationRole;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvitationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email:rfc', 'max:255']]);
        $email = Str::lower($data['email']);

        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => 'Cette adresse a déjà un compte.']);
        }

        if (Invitation::whereNull('accepted_at')->where('expires_at', '>', now())->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => 'Une invitation est déjà en attente pour cette adresse.']);
        }

        $token = Str::random(40);

        $invitation = Invitation::create([
            'email' => $email,
            'role' => OrganizationRole::Member,
            'token_hash' => Invitation::hashToken($token),
            'expires_at' => now()->addDays(7),
        ]);

        $link = route('register', ['invitation' => $token]);

        Mail::to($email)->send(new InvitationMail($invitation, $request->user()->organization, $link));

        // Le lien n'est affichable qu'ici : seul le hash est conservé.
        return back()->with('status', 'Invitation envoyée.')->with('invitation_link', $link);
    }

    public function destroy(Invitation $invitation): RedirectResponse
    {
        $invitation->delete();

        return back()->with('status', 'Invitation annulée.');
    }
}
