<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OrganizationRole;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        $invitation = $request->query('invitation')
            ? Invitation::findPendingByToken((string) $request->query('invitation'))
            : null;

        return view('auth.register', [
            'invitation' => $invitation,
            'invitationToken' => $invitation ? $request->query('invitation') : null,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $invitation = null;

        if ($request->filled('invitation')) {
            $invitation = Invitation::findPendingByToken((string) $request->input('invitation'));

            if (! $invitation) {
                throw ValidationException::withMessages(['invitation' => 'Cette invitation est invalide ou expirée.']);
            }

            if (strcasecmp($invitation->email, (string) $request->input('email')) !== 0) {
                throw ValidationException::withMessages(['email' => 'Cette invitation est destinée à une autre adresse e-mail.']);
            }
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'organization_name' => [$invitation ? 'nullable' : 'required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($request, $invitation) {
            if ($invitation) {
                $organizationId = $invitation->organization_id;
                $role = $invitation->role;

                // Réservation atomique : deux inscriptions simultanées ne peuvent pas consommer la même invitation.
                $claimed = Invitation::withoutGlobalScopes()->whereKey($invitation->id)->whereNull('accepted_at')->update(['accepted_at' => now()]);

                if ($claimed !== 1) {
                    throw ValidationException::withMessages(['invitation' => 'Cette invitation est invalide ou expirée.']);
                }
            } else {
                $organizationId = Organization::create(['name' => $request->organization_name])->getKey();
                $role = OrganizationRole::Owner;
            }

            $user = new User([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
            $user->forceFill(['organization_id' => $organizationId, 'role' => $role])->save();

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
