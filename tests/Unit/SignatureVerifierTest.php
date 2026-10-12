<?php

use App\Services\SignatureVerifier;

test('le calcul correspond à un HMAC-SHA256 de référence', function () {
    // Vecteur connu : HMAC_SHA256(clé "key", "The quick brown fox jumps over the lazy dog").
    expect((new SignatureVerifier)->sign('The quick brown fox jumps over the lazy dog', 'key'))
        ->toBe('f7bc83f430538424b13298e6aa6fb143ef4d59a14946175997479dbc2d1a3cd8');
});

test('la comparaison distingue valide, invalide et absente', function () {
    $verifier = new SignatureVerifier;
    $good = $verifier->sign('corps', 'secret');

    expect($verifier->check('corps', 'secret', $good))->toBe('valid')
        ->and($verifier->check('corps', 'secret', 'sha256='.$good))->toBe('valid')
        ->and($verifier->check('corps modifié', 'secret', $good))->toBe('invalid')
        ->and($verifier->check('corps', 'autre secret', $good))->toBe('invalid')
        ->and($verifier->check('corps', 'secret', ''))->toBe('missing')
        ->and($verifier->check('corps', 'secret', null))->toBe('missing');
});
