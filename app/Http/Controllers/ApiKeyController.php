<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiKeyController extends Controller
{
    private const MAX_KEYS = 10;

    public function index(Request $request): View
    {
        return view('api-keys.index', ['keys' => $request->user()->tokens()->latest()->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);

        if ($request->user()->tokens()->count() >= self::MAX_KEYS) {
            return back()->withErrors(['name' => 'Nombre maximal de clés atteint ('.self::MAX_KEYS.') : révoquez-en une.']);
        }

        $token = $request->user()->createToken($data['name'], ['events:read'], now()->addDays(90));

        // La clé en clair n'existe qu'ici : seul son hash est conservé.
        return back()->with('status', 'Clé créée.')->with('api_key', $token->plainTextToken);
    }

    public function destroy(Request $request, int $key): RedirectResponse
    {
        // Seulement les clés de l'utilisateur connecté : l'identifiant d'un autre donne 404.
        $request->user()->tokens()->findOrFail($key)->delete();

        return back()->with('status', 'Clé révoquée.');
    }
}
