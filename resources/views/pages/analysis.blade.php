@extends('layouts.app')

@section('title', 'Football Analysis Today: Match Previews & Stats | LSA')
@section('description', "Today's football match analysis: model-projected xG, last-5 form, head-to-head records and model probabilities checked against the bookmaker's price — with every result published.")

@php
    // Crest helpers: initials + a stable colour per team name (no club logos needed)
    $crest = function (?string $name): array {
        $name = $name ?? '?';
        $letters = preg_replace('/[^A-Za-z]/', '', $name);
        $initials = strtoupper(substr($letters, 0, 3)) ?: '?';
        $hue = crc32($name) % 360;
        return [$initials, "hsl({$hue} 55% 32%)"];
    };

    // Human day label for a 'Y-m-d' group key (or the 'tbc' bucket).
    $dayLabel = function (string $key): string {
        if ($key === 'tbc') {
            return 'Date TBC';
        }
        $date = \Illuminate\Support\Carbon::parse($key, \App\Models\Prediction::displayTimezone());
        if ($date->isToday()) {
            return "Today's matches — " . $date->format('l j F');
        }
        if ($date->isTomorrow()) {
            return "Tomorrow's matches — " . $date->format('l j F');
        }
        return 'Matches on ' . $date->format('l j F');
    };

    // Short label for the filter tab itself (stays compact at any slate length).
    $dayTabLabel = function (string $key) use ($dayLabel): string {
        if ($key === 'tbc') {
            return 'TBC';
        }
        $date = \Illuminate\Support\Carbon::parse($key, \App\Models\Prediction::displayTimezone());
        if ($date->isToday()) {
            return 'Today';
        }
        if ($date->isTomorrow()) {
            return 'Tomorrow';
        }
        return $date->format('D j M');
    };
@endphp

@push('head')
    @php
        // One ItemList entry per fixture page (several markets can share one).
        $fixturePages = $predictions->filter(fn ($p) => $p->slug)->unique('slug')->values();
        $hubSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'CollectionPage',
                    '@id' => route('analysis') . '#webpage',
                    'url' => route('analysis'),
                    'name' => 'Football Analysis Today: Match Previews & Stats',
                    'description' => "Today's football match analysis: model-projected xG, last-5 form, head-to-head records and model probabilities checked against the bookmaker's price.",
                    'isPartOf' => ['@id' => url('/') . '#website'],
                    'inLanguage' => 'en',
                    'dateModified' => $lastUpdated ? \Illuminate\Support\Carbon::parse($lastUpdated)->toAtomString() : null,
                    'breadcrumb' => ['@id' => route('analysis') . '#breadcrumb'],
                    'mainEntity' => ['@id' => route('analysis') . '#fixtures'],
                ],
                [
                    '@type' => 'BreadcrumbList',
                    '@id' => route('analysis') . '#breadcrumb',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Analysis', 'item' => route('analysis')],
                    ],
                ],
                [
                    '@type' => 'ItemList',
                    '@id' => route('analysis') . '#fixtures',
                    'numberOfItems' => $fixturePages->count(),
                    'itemListElement' => $fixturePages->map(fn ($p, $i) => [
                        '@type' => 'ListItem',
                        'position' => $i + 1,
                        'url' => $p->fixtureUrl(),
                        'name' => "{$p->home} vs {$p->away}",
                    ])->all(),
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($hubSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')

    <!-- Page header -->
    <section class="page-head" data-track-section="page-head">
        <div class="hero__bg" aria-hidden="true"><div class="hero__grid"></div><div class="hero__glow"></div></div>
        <div class="container">
            <div class="page-head__inner">
                <p class="kicker" data-hero style="--d:0ms">Match intelligence</p>
                {{-- No data-split/data-hero on the H1 and lede on purpose: those hide the
                     text until lsa.js runs, which delays Largest Contentful Paint. --}}
                <h1 class="page-head__title">Football Analysis Today</h1>
                <p class="page-head__lede">Every fixture we analysed, with the model's pick, its probability and the bookmaker's odds at a glance. Tap a match for form, projected goals (xG) and head-to-head.</p>
                <div class="page-head__meta" data-hero style="--d:480ms">
                    <span><i class="dot-live" aria-hidden="true"></i><b>{{ $predictions->count() }}</b> {{ \Illuminate\Support\Str::plural('fixture', $predictions->count()) }} analysed</span>
                    @if ($lastUpdated)
                        <span>Updated <time datetime="{{ \Illuminate\Support\Carbon::parse($lastUpdated)->toAtomString() }}">{{ \Illuminate\Support\Carbon::parse($lastUpdated)->setTimezone(\App\Models\Prediction::displayTimezone())->format('j M, H:i T') }}</time></span>
                    @else
                        <span>Updated after every scan</span>
                    @endif
                    <a class="text-link" href="{{ route('results') }}">See how past reads turned out</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Notice -->
    <div class="notice-bar">
        <div class="container notice-bar__inner">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
            <p><b>Educational analysis.</b> Based on available statistics and current form. Football outcomes are unpredictable.</p>
        </div>
    </div>

    <!-- Match list: the pick is visible on every row; details expand in place -->
    <section class="section section--soft section--tight" data-track-section="fixtures">
        <div class="container">

            @if ($predictions->isEmpty())
                <div class="empty">
                    <span class="empty__icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                    <h3>No upcoming analysis right now</h3>
                    <p>New fixtures appear here after the next scan. In the meantime, every past read is on the results ledger.</p>
                    <a class="btn btn--ghost btn--sm" href="{{ route('results') }}">View results</a>
                </div>
            @else
                @php
                    // one row per fixture; its strongest market leads, any others go in the details
                    $fixtureKey = fn ($p) => $p->slug ?? ($p->home . '|' . $p->away . '|' . $p->kickoff);
                    $top = $predictions->filter(fn ($p) => $p->kickoff && $p->kickoff->isFuture())
                        ->sortByDesc('predicted_probability')->unique($fixtureKey)->take(3);
                @endphp

                @if ($top->isNotEmpty())
                    <div class="toppicks" aria-label="Strongest picks">
                        <p class="toppicks__label">Strongest picks</p>
                        <div class="toppicks__grid">
                            @foreach ($top as $p)
                                <a class="toppick" href="{{ $p->fixtureUrl() ?? '#' }}">
                                    <span class="toppick__meta num">{{ $p->localKickoff()?->format('D H:i') }} · {{ \Illuminate\Support\Str::before($p->leagueLabel(), ' (') }}</span>
                                    <span class="toppick__match">{{ $p->home }} <span class="vs-sm">vs</span> {{ $p->away }}</span>
                                    <span class="toppick__pick">{{ $p->market }}</span>
                                    <span class="toppick__nums"><b class="num">{{ number_format($p->predicted_probability * 100, 0) }}%</b>@if ($p->odds !== null)<span class="num">@ {{ number_format($p->odds, 2) }}</span>@endif</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($byDay->count() > 1)
                    <div class="ledger-tools" data-fixture-filters>
                        <div class="filters" role="group" aria-label="Filter by day">
                            <button type="button" class="filter" data-day-filter="all" aria-pressed="true">All <span class="num">{{ $predictions->unique($fixtureKey)->count() }}</span></button>
                            @foreach ($byDay as $day => $dayPredictions)
                                <button type="button" class="filter" data-day-filter="{{ $day }}" aria-pressed="false">{{ $dayTabLabel($day) }} <span class="num">{{ $dayPredictions->unique($fixtureKey)->count() }}</span></button>
                            @endforeach
                        </div>
                    </div>
                @endif

                @foreach ($byDay as $day => $dayPredictions)
                    @php
                        $fixtures = $dayPredictions->groupBy($fixtureKey)->map(fn ($g) => $g->sortByDesc('predicted_probability')->values());
                        $byLeague = $fixtures->groupBy(fn ($g) => $g->first()->leagueLabel())
                            ->sortBy(fn ($l) => $l->min(fn ($g) => $g->first()->kickoff));
                    @endphp
                    <section data-day-group="{{ $day }}" class="mday">
                        <div class="day-divider">
                            <h2>{{ $dayLabel($day) }}</h2>
                            <span class="num">{{ $fixtures->count() }} {{ \Illuminate\Support\Str::plural('match', $fixtures->count()) }}</span>
                        </div>

                        <div class="mlist">
                            <div class="mlist__head" aria-hidden="true">
                                <span>Time</span><span>Match</span><span>Our pick</span><span>Probability</span><span>Odds</span>
                            </div>
                            @foreach ($byLeague as $league => $group)
                                <div class="mleague">{{ $league }}</div>
                                @foreach ($group->sortBy(fn ($g) => $g->first()->kickoff) as $reads)
                                    @php
                                        $p = $reads->first();
                                        $prob = round(($p->predicted_probability ?? 0) * 100);
                                    @endphp
                                    <details class="mrow">
                                        <summary class="mrow__sum">
                                            <span class="mrow__time num">{{ $p->localKickoff()?->format('H:i') ?? 'TBC' }}</span>
                                            <h3 class="mrow__match">
                                                <span class="mrow__team">{{ $p->home }}</span>
                                                <span class="mrow__team">{{ $p->away }}</span>
                                            </h3>
                                            <span class="mrow__pick">{{ $p->market }}@if ($reads->count() > 1)<small> +{{ $reads->count() - 1 }} more</small>@endif</span>
                                            <span class="mrow__prob">
                                                <b class="num">{{ $prob }}%</b>
                                                <span class="mrow__bar" aria-hidden="true"><i style="width:{{ min($prob, 100) }}%"></i></span>
                                            </span>
                                            <span class="mrow__odds num">{{ $p->odds !== null ? number_format($p->odds, 2) : '–' }}</span>
                                            <span class="mrow__chev" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg></span>
                                        </summary>

                                        <div class="mrow__detail">
                                            <div class="mdet">
                                                <p class="acard__label">Last 5</p>
                                                @foreach ([[$p->home, $p->home_form ?? []], [$p->away, $p->away_form ?? []]] as [$team, $form])
                                                    <div class="formrow">
                                                        <span class="formrow__name">{{ $team }}</span>
                                                        <span class="pills" @if (count($form)) role="img" aria-label="Last five: {{ implode(', ', array_map(fn ($r) => ['W' => 'win', 'D' => 'draw', 'L' => 'loss'][$r] ?? $r, $form)) }}" @endif>
                                                            @forelse ($form as $result)
                                                                <span class="pill pill--{{ $result }}" aria-hidden="true">{{ $result }}</span>
                                                            @empty
                                                                <span class="pill pill--none">No data</span>
                                                            @endforelse
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="mdet">
                                                @if ($p->xg_home !== null && $p->xg_away !== null)
                                                    <p class="acard__label">Projected goals (xG)</p>
                                                    <p class="mdet__xg num"><b>{{ number_format((float) $p->xg_home, 2) }}</b> – <b>{{ number_format((float) $p->xg_away, 2) }}</b></p>
                                                @endif
                                                <dl class="mdet__facts">
                                                    @foreach ($reads as $r)
                                                        <div><dt>{{ $r->market }}</dt><dd class="num">{{ number_format($r->predicted_probability * 100, 1) }}%@if ($r->odds !== null) @ {{ number_format($r->odds, 2) }}@endif</dd></div>
                                                    @endforeach
                                                    @if ($p->prob_market !== null)
                                                        <div><dt>Bookmaker's fair chance</dt><dd class="num">{{ number_format($p->prob_market * 100, 1) }}%</dd></div>
                                                    @endif
                                                </dl>
                                            </div>
                                            @if ($p->h2hNoteLabel())
                                                <p class="mdet__h2h"><strong>Head-to-head:</strong> {{ $p->h2hNoteLabel() }}</p>
                                            @endif
                                            @if ($p->slug)
                                                <a class="text-link mdet__more" href="{{ $p->fixtureUrl() }}">Full {{ $p->home }} vs {{ $p->away }} analysis</a>
                                            @endif
                                        </div>
                                    </details>
                                @endforeach
                            @endforeach
                        </div>
                    </section>
                    @if ($loop->first)
                        @include('partials.telegram-cta', ['placement' => 'analysis'])
                    @endif
                @endforeach
            @endif

            @if ($predictions->isNotEmpty())
                <p class="read__odds-note" id="odds-note" style="margin-top:32px">About odds: every current-model pick comes with a real bookmaker price (match result, double chance or over/under 2.5 goals). The bookmaker's fair chance is their price with the margin taken out; we only publish a pick where our probability holds up against it.</p>
            @endif

            <!-- Disclaimer -->
            <div class="callout" style="margin-top:32px" data-reveal>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
                <div>
                    <p><strong>This analysis does not predict or guarantee any specific outcome.</strong> It is based on publicly available statistics and recent form, and football is shaped by many variables no model captures. Content is for educational purposes only.</p>
                    <p><a href="{{ route('disclaimer') }}">Read the full disclaimer</a></p>
                </div>
            </div>
        </div>
    </section>

    <!-- How it works: crawlable explanatory copy for the page's topic -->
    <section class="section" data-track-section="method">
        <div class="container">
            <div class="section-head">
                <p class="kicker">Method</p>
                <h2 class="h2">How our football analysis works</h2>
            </div>
            <div class="prose">
                <h3>Projected goals (xG)</h3>
                <p>Each fixture gets a projected-goals figure for both sides from models trained on 25+ seasons of historical results across {{ config('lsa.current_model.leagues') }} leagues. It shows which side is more likely to create the better chances — not a promised scoreline.</p>
                <h3>Recent form and head-to-head</h3>
                <p>Last-five form shows how each team is actually playing right now. Where two sides show a consistent pattern across past meetings we note it, and say so plainly when the sample is small.</p>
                <h3>Model probability and the price check</h3>
                <p>The headline number on each card is our probability for the pick. It starts from the bookmaker's own odds, with their margin removed, and our model only moves it as far as it has earned on past seasons. A pick is published only where a real bookmaker price isn't a bad deal against that probability: Confident picks need about a 75% chance, Lean picks about 72%. Matches where nothing clears the bar don't appear on this page.</p>
                <h3>Every result published</h3>
                <p>After full time, every fixture is graded and added to the <a href="{{ route('results') }}">results ledger</a> — the misses as well as the hits. Each fixture keeps its own analysis page, so you can always check what we said before kickoff. See also <a href="{{ route('combos') }}">today's combos</a> and <a href="{{ route('about') }}">our approach</a>.</p>
            </div>
        </div>
    </section>

@include('partials.offers')

@endsection
