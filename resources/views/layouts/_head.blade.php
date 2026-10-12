<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#0e1322">

<title>{{ $title ?? config('app.name', 'Relais') }}</title>

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=fraunces:600,700|inter:400,500,600|jetbrains-mono:400,500&display=swap" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
