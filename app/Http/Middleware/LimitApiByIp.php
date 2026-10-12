<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Limite les requêtes d'API par adresse IP avant l'authentification, donc aussi les essais de clés invalides.
 * Classe dédiée plutôt que `throttle:` : la liste de priorité de Laravel placerait ce dernier après l'authentification.
 */
class LimitApiByIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'api-guests:'.$request->ip();
        $max = config('relais.api_rate_per_minute') * 5;

        if (RateLimiter::tooManyAttempts($key, $max)) {
            abort(429, 'Trop de requêtes.');
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
