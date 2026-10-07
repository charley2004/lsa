<!doctype html>
<html lang="en">
<head>
    @php
        $pageTitle = trim($__env->yieldContent('title', 'LSA — LEGIT SOURCE ANALYSIS'));
        $pageDescription = trim($__env->yieldContent('description', 'Data-driven football match analysis. Expected goals, form, head-to-head and team news — with every result published, hits and misses alike.'));
        $canonicalUrl = url()->current();
        $ogImageUrl = asset('images/og-default.png');
    @endphp

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta name="theme-color" content="#0b1220">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicons -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="LSA — LEGIT SOURCE ANALYSIS">
    <meta property="og:locale" content="en_US">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:image" content="{{ $ogImageUrl }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="LSA — LEGIT SOURCE ANALYSIS">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $ogImageUrl }}">

    <!-- Structured data -->
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => url('/') . '#organization',
                    'name' => 'LSA',
                    'legalName' => 'LEGIT SOURCE ANALYSIS',
                    'alternateName' => 'LEGIT SOURCE ANALYSIS',
                    'url' => url('/'),
                    'logo' => asset('images/icon-512.png'),
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => url('/') . '#website',
                    'name' => 'LSA — LEGIT SOURCE ANALYSIS',
                    'url' => url('/'),
                    'publisher' => ['@id' => url('/') . '#organization'],
                    'inLanguage' => 'en',
                ],
            ],
        ], JSON_UNESCAPED_SLASHES) !!}
    </script>

    <script>
        // Enables entrance animations. Safety net: if lsa.js fails to load, show everything.
        document.documentElement.classList.add('js');
        setTimeout(function () { if (!window.LSA_READY) document.documentElement.classList.remove('js'); }, 2500);
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/lsa.css') }}">
    <script src="{{ asset('js/lsa.js') }}" defer></script>
    @stack('head')
</head>
<body>

@php
    $navItems = [
        ['home',     'Home'],
        ['analysis', 'Analysis'],
        ['results',  'Results'],
        ['about',    'About'],
        ['contact',  'Contact'],
    ];
@endphp

<a class="skip-link" href="#main">Skip to content</a>

<header class="nav" data-nav>
    <div class="container nav__inner">
        <a href="{{ route('home') }}" class="brand" aria-label="LSA home">
            <img class="brand__mark" src="{{ asset('images/badge-256.png') }}" width="32" height="32" alt="">
            <span class="brand__text"><span class="brand__name">LSA</span><span class="brand__sub">LEGIT SOURCE ANALYSIS</span></span>
        </a>

        <nav class="nav__links" aria-label="Primary">
            @foreach ($navItems as [$route, $label])
                <a class="nav__link" href="{{ route($route) }}" @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        <a class="btn btn--primary btn--sm nav__cta" href="{{ route('analysis') }}">Latest analysis
            <svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>

        <button class="nav__toggle" type="button" aria-expanded="false" aria-controls="menu" data-menu-toggle>
            <span class="sr-only">Menu</span><i></i><i></i>
        </button>
    </div>
</header>

<div class="menu" id="menu" data-menu>
    @foreach ($navItems as $i => [$route, $label])
        <a href="{{ route($route) }}" style="--i:{{ $i }}" @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }} <span class="num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span></a>
    @endforeach
    <p class="menu__note" style="--i:{{ count($navItems) }}">Educational analysis only. Not betting advice. 18+</p>
</div>

<main id="main">
    @yield('content')
</main>

<footer class="footer">
    <div class="container">
        <div class="footer__grid">
            <div class="footer__brand">
                <a href="{{ route('home') }}" class="brand brand--light" aria-label="LSA home">
                    <img class="brand__mark" src="{{ asset('images/badge-256-light.png') }}" width="32" height="32" alt="">
                    <span class="brand__text"><span class="brand__name">LSA</span><span class="brand__sub">LEGIT SOURCE ANALYSIS</span></span>
                </a>
                <p>Data-driven football analysis, with every result on the record.</p>
            </div>
            <nav class="footer__col" aria-label="Platform">
                <h3>Platform</h3>
                <a href="{{ route('analysis') }}">Analysis</a>
                <a href="{{ route('results') }}">Results</a>
                <a href="{{ route('about') }}">About</a>
            </nav>
            <nav class="footer__col" aria-label="Company">
                <h3>Company</h3>
                <a href="{{ route('contact') }}">Contact</a>
                <a href="{{ route('disclaimer') }}">Disclaimer</a>
                <a href="{{ route('privacy') }}">Privacy Policy</a>
            </nav>
            <div class="footer__notice">
                <h3>Important</h3>
                <p>LSA publishes educational football analysis. It is not betting advice and does not guarantee any outcome. 18+ only. If gambling stops being fun, stop and seek support.</p>
            </div>
        </div>
        <div class="footer__bottom">
            <p>© {{ date('Y') }} LSA — LEGIT SOURCE ANALYSIS</p>
            <p class="num">Built on data, judged on results.</p>
        </div>
    </div>
</footer>

@stack('scripts')
</body>
</html>
