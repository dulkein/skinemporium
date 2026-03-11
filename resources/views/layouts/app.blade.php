<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Skin Emporium' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body>
    <header class="site-header">
        <div class="container nav-wrap">
            <a class="brand" href="{{ route('home') }}">SkinEmporium</a>
            <nav class="site-nav">
                <a class="{{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}">Home</a>
                <a class="{{ request()->routeIs('market.*') ? 'is-active' : '' }}" href="{{ route('market.index') }}">Market</a>
                <a class="{{ request()->routeIs('sell.*') ? 'is-active' : '' }}" href="{{ route('sell.create') }}">Sell</a>
            </nav>
        </div>
    </header>

    <main class="container page-main">
        @if (session('status'))
            <p class="alert">{{ session('status') }}</p>
        @endif

        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>Simple learning project inspired by skin marketplaces.</p>
        </div>
    </footer>
</body>
</html>
