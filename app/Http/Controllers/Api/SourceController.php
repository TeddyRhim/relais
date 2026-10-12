<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Source;
use Illuminate\Http\JsonResponse;

class SourceController extends Controller
{
    public function index(): JsonResponse
    {
        // Jamais le jeton d'URL ni le secret : l'API ne sert qu'à lire les événements.
        $sources = Source::orderBy('id')->get()->map(fn (Source $source) => [
            'id' => $source->id,
            'name' => $source->name,
            'require_signature' => $source->require_signature,
            'created_at' => $source->created_at,
        ]);

        return response()->json(['data' => $sources]);
    }
}
