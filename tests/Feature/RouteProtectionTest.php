<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;

/**
 * Filet de sécurité : une route ajoutée plus tard sans protection fait échouer ces tests,
 * sauf si elle figure explicitement dans une liste ci-dessous.
 */
const PUBLIC_ROUTE_NAMES = ['ingest', 'billing.webhook'];

/** Routes d'écriture ouvertes à tout membre connecté (le reste exige le rôle propriétaire). */
const MEMBER_WRITE_ROUTE_NAMES = [
    'logout', 'profile.update', 'profile.destroy', 'password.update', 'password.confirm',
    'verification.send', 'api-keys.store', 'api-keys.destroy', 'events.replay',
];

function hasMiddleware(LaravelRoute $route, callable $match): bool
{
    return collect($route->gatherMiddleware())->contains(fn ($m) => is_string($m) && $match($m));
}

function isAuthMiddleware(string $m): bool
{
    return $m === 'auth' || str_starts_with($m, 'auth:') || $m === Authenticate::class || str_starts_with($m, Authenticate::class.':');
}

function isGuestOrSigned(string $m): bool
{
    return $m === 'guest' || str_starts_with($m, 'guest:') || str_starts_with($m, 'signed');
}

function allRoutes()
{
    return collect(Route::getRoutes()->getRoutes());
}

test('chaque route hors liste publique exige une authentification', function () {
    $unprotected = allRoutes()
        ->reject(fn (LaravelRoute $route) => in_array($route->getName(), PUBLIC_ROUTE_NAMES, true))
        ->reject(fn (LaravelRoute $route) => in_array($route->uri(), ['/', 'up', 'storage/{path}'], true))
        ->reject(fn (LaravelRoute $route) => $route->uri() === 'sanctum/csrf-cookie')
        // Routes Breeze réservées aux invités ou liens signés d'e-mail.
        ->reject(fn (LaravelRoute $route) => hasMiddleware($route, 'isGuestOrSigned'))
        ->reject(fn (LaravelRoute $route) => hasMiddleware($route, 'isAuthMiddleware'))
        ->map(fn (LaravelRoute $route) => implode('|', $route->methods()).' '.$route->uri())
        ->values()
        ->all();

    expect($unprotected)->toBe([]);
});

test('un visiteur non connecté est refusé sur chaque route protégée', function () {
    $routes = allRoutes()->filter(fn (LaravelRoute $route) => hasMiddleware($route, 'isAuthMiddleware'));

    expect($routes->count())->toBeGreaterThan(15);

    foreach ($routes as $route) {
        $uri = '/'.preg_replace('/\{[^}]+\}/', '1', $route->uri());
        $method = collect($route->methods())->first(fn ($m) => $m !== 'HEAD');

        $response = $this->call($method, $uri);

        expect(in_array($response->getStatusCode(), [302, 401], true))
            ->toBeTrue("{$method} {$uri} a répondu {$response->getStatusCode()} à un visiteur anonyme");

        if ($response->getStatusCode() === 302) {
            expect($response->headers->get('Location'))->toContain('/login');
        }
    }
});

test('toute route d\'écriture authentifiée est réservée au propriétaire, sauf liste explicite', function () {
    $open = allRoutes()
        ->filter(fn (LaravelRoute $route) => hasMiddleware($route, 'isAuthMiddleware'))
        ->reject(fn (LaravelRoute $route) => $route->methods() === ['GET', 'HEAD'])
        ->reject(fn (LaravelRoute $route) => hasMiddleware($route, fn ($m) => $m === 'owner'))
        ->reject(fn (LaravelRoute $route) => in_array($route->getName(), MEMBER_WRITE_ROUTE_NAMES, true))
        ->reject(fn (LaravelRoute $route) => $route->uri() === 'confirm-password') // route Breeze sans nom
        ->map(fn (LaravelRoute $route) => implode('|', $route->methods()).' '.$route->uri().' ('.$route->getName().')')
        ->values()
        ->all();

    expect($open)->toBe([]);
});

test('les routes de réception publiques ne servent que POST', function () {
    foreach (PUBLIC_ROUTE_NAMES as $name) {
        expect(Route::getRoutes()->getByName($name)->methods())->toBe(['POST']);
    }
});
