@php
    $map = [
        'valid' => ['ok', 'Signature valide'],
        'invalid' => ['bad', 'Signature invalide'],
        'missing' => ['neutral', 'Non signé'],
    ];
    [$tone, $label] = $map[$status];
@endphp
<x-badge :tone="$tone">{{ $label }}</x-badge>
