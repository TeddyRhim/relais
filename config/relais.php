<?php

return [

    /*
    | Taille maximale du corps d'un webhook reçu, en octets.
    */
    'max_body_bytes' => (int) env('RELAIS_MAX_BODY_BYTES', 1_000_000),

    /*
    | En-tête de signature par défaut pour une nouvelle source.
    */
    'default_signature_header' => 'X-Relais-Signature',

    /*
    | Rejeu : délai d'attente de la cible (secondes) et délais entre tentatives (secondes).
    | Le nombre de tentatives est le nombre de délais plus un.
    */
    'replay' => [
        'timeout' => (int) env('RELAIS_REPLAY_TIMEOUT', 10),
        'backoff' => [10, 60, 300, 1800],
        'response_excerpt_bytes' => 2000,
    ],

    /*
    | Autorise les cibles de rejeu sur des adresses privées ou locales.
    | À laisser à false hors développement : protège contre les requêtes vers le réseau interne (SSRF).
    */
    'allow_private_targets' => (bool) env('RELAIS_ALLOW_PRIVATE_TARGETS', false),

    /*
    | Limite de débit de la réception, par jeton de source (requêtes par minute).
    */
    'ingest_rate_per_minute' => (int) env('RELAIS_INGEST_RATE', 120),

];
