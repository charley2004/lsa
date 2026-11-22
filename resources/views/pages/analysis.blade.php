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
                <p class="page-head__lede">Today's football match previews, built from data: model-projected goals (xG), last-five form, head-to-head records and a model probability checked against the bookmaker's price for every pick we publish — with every outcome later published on the results ledger.</p>
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

    <!-- Analysis cards -->
    <section class="section section--soft" data-track-section="fixtures">
        <div class="container">

            @if ($predictions->isEmpty())
                <div class="empty" data-reveal>
                    <span class="empty__icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                    <h3>No upcoming analysis right now</h3>
                    <p>New fixtures appear here after the next scan. In the meantime, every past read is on the results ledger.</p>
                    <a class="btn btn--ghost btn--sm" href="{{ route('results') }}">View results</a>
                </div>
            @else
                @if ($byDay->count() > 1)
                    <div class="ledger-tools" data-fixture-filters data-reveal>
                        <div class="filters" role="group" aria-label="Filter by day">
                            <button type="button" class="filter" data-day-filter="all" aria-pressed="true">All <span class="num">{{ $predictions->count() }}</span></button>
                            @foreach ($byDay as $day => $dayPredictions)
                                <button type="button" class="filter" data-day-filter="{{ $day }}" aria-pressed="false">{{ $dayTabLabel($day) }} <span class="num">{{ $dayPredictions->count() }}</span></button>
                            @endforeach
                        </div>
                    </div>
                @endif

                @foreach ($byDay as $day => $dayPredictions)
                    <section data-day-group="{{ $day }}">
                        <div class="day-divider" data-reveal>
                            <h2>{{ $dayLabel($day) }}</h2>
                            <span class="num">{{ $dayPredictions->count() }} {{ \Illuminate\Support\Str::plural('fixture', $dayPredictions->count()) }}</span>
                        </div>

                        <div class="acards">
                            @foreach ($dayPredictions as $p)
                                @php
                                    [$hInit, $hCol] = $crest($p->home);
                                    [$aInit, $aCol] = $crest($p->away);
                                    $prob = round(($p->predicted_probability ?? 0) * 100, 1);
                                @endphp
                                <article class="acard" data-reveal>
                                    <h3 class="sr-only">@if ($p->slug)<a href="{{ $p->fixtureUrl() }}">{{ $p->home }} vs {{ $p->away }} analysis</a>@else{{ $p->home }} vs {{ $p->away }} analysis @endif</h3>
                                    <header class="acard__top">
                                        <span class="chip">{{ $p->leagueLabel() }}</span>
                                        <span class="acard__kick num">
                                            <b>{{ $p->localKickoff()?->format('D j M') ?? 'Date TBC' }}</b>
                                            {{ $p->kickoffTime() }}
                                        </span>
                                    </header>

                                    <div class="acard__fixture">
                                        <div class="acard__team">
                                            <span class="crest" style="--c:{{ $hCol }}" aria-hidden="true">{{ $hInit }}</span>
                                            <div><p class="acard__name">{{ $p->home }}</p><span class="cap">Home</span></div>
                                        </div>
                                        <span class="acard__vs">VS</span>
                                        <div class="acard__team acard__team--away">
                                            <span class="crest" style="--c:{{ $aCol }}" aria-hidden="true">{{ $aInit }}</span>
                                            <div><p class="acard__name">{{ $p->away }}</p><span class="cap">Away</span></div>
                                        </div>
                                    </div>

                                    <div class="acard__body">
                                        <div class="acard__main">
                                            <div class="acard__section">
                                                <p class="acard__label">Recent form · last 5</p>
                                                @foreach ([[$p->home, $p->home_form ?? []], [$p->away, $p->away_form ?? []]] as [$team, $form])
                                                    <div class="formrow">
                                                        <span class="formrow__name">{{ $team }}</span>
                                                        <span class="pills pills--lg" @if (count($form)) role="img" aria-label="Last five: {{ implode(', ', array_map(fn ($r) => ['W' => 'win', 'D' => 'draw', 'L' => 'loss'][$r] ?? $r, $form)) }}" @endif>
                                                            @forelse ($form as $i => $result)
                                                                <span class="pill pill--{{ $result }}" style="--n:{{ $i }}" aria-hidden="true">{{ $result }}</span>
                                                            @empty
                                                                <span class="pill pill--none">No recent data</span>
                                                            @endforelse
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>

                                            @if ($p->xg_home !== null && $p->xg_away !== null)
                                                @php
                                                    $xgTotal = max((float) $p->xg_home + (float) $p->xg_away, 0.01);
                                                    $xgH = round((float) $p->xg_home / $xgTotal * 100, 1);
                                                @endphp
                                                <div class="acard__section">
                                                    <p class="acard__label">Model-projected goals</p>
                                                    <div class="xgbar">
                                                        <div class="xgbar__nums"><b>{{ number_format((float) $p->xg_home, 2) }}</b><span>xG</span><b>{{ number_format((float) $p->xg_away, 2) }}</b></div>
                                                        <div class="split grow" aria-hidden="true"><i style="flex-basis:{{ $xgH }}%"></i><i style="flex-basis:{{ 100 - $xgH }}%"></i></div>
                                                        <div class="xgbar__teams"><span>{{ $p->home }}</span><span>{{ $p->away }}</span></div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        <aside class="read">
                                            <p class="acard__label">Model read</p>
                                            <p class="read__market">{{ $p->market }}</p>
                                            <div class="read__prob"><b>{{ number_format($prob, 1) }}%</b><span>model probability</span></div>
                                            <div class="meter" aria-hidden="true"><i style="width:{{ min($prob, 100) }}%"></i></div>

                                            <dl class="read__facts">
                                                <div><dt>Verdict</dt><dd><span class="verdict {{ $p->verdictClass() }}">{{ $p->verdictLabel() }}</span></dd></div>
                                                @if ($p->prob_market !== null)
                                                    <div><dt>Bookmaker's fair chance</dt><dd class="num">{{ number_format($p->prob_market * 100, 1) }}%</dd></div>
                                                @endif
                                                @if ($p->odds !== null)
                                                    <div><dt>Odds</dt><dd class="num">{{ number_format($p->odds, 2) }}</dd></div>
                                                @endif
                                            </dl>
                                            @if ($p->odds === null)
                                                <p class="read__odds-note">No bookmaker price for this market — <a href="#odds-note">why</a>.</p>
                                            @endif

                                            <div class="read__notes">
                                                @if ($p->h2hNoteLabel())
                                                    <div class="callout callout--info"><p><strong>Head-to-head:</strong> {{ $p->h2hNoteLabel() }}</p></div>
                                                @endif
                                            </div>
                                            @if ($p->slug)
                                                <p class="acard__more"><a class="text-link" href="{{ $p->fixtureUrl() }}">Full {{ $p->home }} vs {{ $p->away }} analysis</a></p>
                                            @endif
                                        </aside>
                                    </div>
                                </article>
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
