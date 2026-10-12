<?php

use App\Services\UrlGuard;
use Tests\TestCase;

uses(TestCase::class);

dataset('urls interdites', [
    'loopback' => 'http://127.0.0.1/x',
    'localhost' => 'http://localhost/x',
    'metadonnees cloud' => 'http://169.254.169.254/latest/meta-data',
    'reseau prive 10' => 'http://10.0.0.5/x',
    'reseau prive 192' => 'https://192.168.1.10/x',
    'reseau prive 172' => 'http://172.16.0.1/x',
    'ipv6 loopback' => 'http://[::1]/x',
    'schema ftp' => 'ftp://93.184.216.34/x',
    'schema file' => 'file:///etc/passwd',
    'identifiants dans l\'url' => 'http://user:pass@93.184.216.34/x',
    'ipv4 mappee loopback' => 'http://[::ffff:127.0.0.1]/x',
    'ipv4 mappee hexa' => 'http://[::ffff:7f00:1]/x',
    'ipv4 mappee privee' => 'http://[::ffff:10.0.0.1]/x',
    'nat64' => 'http://[64:ff9b::7f00:1]/x',
    '6to4' => 'http://[2002:7f00:1::]/x',
    'multidiffusion ipv6' => 'http://[ff02::1]/x',
    'teredo' => 'http://[2001::1]/x',
    'cgnat' => 'http://100.64.0.1/x',
    'multidiffusion' => 'http://224.0.0.1/x',
    'benchmark' => 'http://198.18.0.1/x',
    'sans hote' => 'http:///x',
    'texte' => 'pas une url',
]);

test('les cibles internes ou invalides sont refusées', function (string $url) {
    expect(app(UrlGuard::class)->isSafe($url))->toBeFalse();
})->with('urls interdites');

test('une adresse publique est acceptée', function () {
    expect(app(UrlGuard::class)->isSafe('https://93.184.216.34/hook'))->toBeTrue();
});

test('les cibles privées peuvent être autorisées explicitement en développement', function () {
    config(['relais.allow_private_targets' => true]);

    expect(app(UrlGuard::class)->isSafe('http://127.0.0.1:8000/hook'))->toBeTrue()
        ->and(app(UrlGuard::class)->isSafe('ftp://127.0.0.1/hook'))->toBeFalse();
});
