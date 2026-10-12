<?php

use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;

/**
 * Filet de sécurité : une route ajoutée plus tard sans protection fait échouer ce test,
 * sauf si elle figure explicitement dans la liste des routes publiques ci-dessous.
 */
const PUBLIC_ROUTE_NAMES = [
    'ingest', 'billing.webhook', 'login', 'register', 'password.request', 'password.email',
    'password.reset', 'password.store', 'verification.verify',
];

test('chaque route hors liste publique exige une authentification', function () {
    $unprotected = collect(Route::getRoutes()->getRoutes())
        ->reject(fn (LaravelRoute $route) => in_array($route->getName(), PUBLIC_ROUTE_NAMES, true))
        ->reject(fn (LaravelRoute $route) => in_array($route->uri(), ['/', 'up', 'storage/{path}'], true))
        ->reject(fn (LaravelRoute $route) => str_starts_with($route->uri(), 'sanctum/'))
        // Routes Breeze réservées aux invités ou à la vérification d'e-mail (middleware guest ou signed).
        ->reject(fn (LaravelRoute $route) => collect($route->gatherMiddleware())->contains(fn ($m) => is_string($m) && (str_starts_with($m, 'guest') || str_starts_with($m, 'signed'))))
        ->filter(fn (LaravelRoute $route) => ! collect($route->gatherMiddleware())->contains(fn ($m) => is_string($m) && str_starts_with($m, 'auth')))
        ->map(fn (LaravelRoute $route) => implode('|', $route->methods()).' '.$route->uri())
        ->values()
        ->all();

    expect($unprotected)->toBe([]);
});

test('un visiteur non connecté est refusé sur chaque route protégée', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (LaravelRoute $route) => collect($route->gatherMiddleware())->contains(fn ($m) => is_string($m) && str_starts_with($m, 'auth')));

    expect($routes->count())->toBeGreaterThan(15);

    foreach ($routes as $route) {
        $uri = '/'.preg_replace('/\{[^}]+\}/', '1', $route->uri());
        $method = collect($route->methods())->first(fn ($m) => ! in_array($m, ['HEAD'], true));

        $response = $this->call($method, $uri);

        expect(in_array($response->getStatusCode(), [302, 401], true))
            ->toBeTrue("{$method} {$uri} a répondu {$response->getStatusCode()} à un visiteur anonyme");

        if ($response->getStatusCode() === 302) {
            expect($response->headers->get('Location'))->toContain('/login');
        }
    }
});

test('les routes de réception publiques ne servent que POST', function () {
    foreach (['ingest', 'billing.webhook'] as $name) {
        expect(Route::getRoutes()->getByName($name)->methods())->toBe(['POST']);
    }
});
