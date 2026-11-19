@extends('layouts.app')

@php
    $home = $fixture->home;
    $away = $fixture->away;
    $dateLong = $fixture->localKickoff()?->format('j M Y');
    $league = $fixture->leagueLabel();

    $crest = function (?string $name): array {
        $name = $name ?? '?';
        $letters = preg_replace('/[^A-Za-z]/', '', $name);
        $initials = strtoupper(substr($letters, 0, 3)) ?: '?';
        $hue = crc32($name) % 360;
        return [$initials, "hsl({$hue} 55% 32%)"];
    };
    [$hInit, $hCol] = $crest($home);
    [$aInit, $aCol] = $crest($away);

    $score = $reads->first(fn ($p) => $p->home_goals !== null && $p->away_goals !== null);
    $published = $reads->min('logged_at') ?? $reads->min('created_at');
    $modified = $reads->max('updated_at');
@endphp

@section('title', "{$home} vs {$away} Prediction & Analysis" . ($dateLong ? " ({$dateLong})" : '') . ' | LSA')
@section('description', $metaDescription)
@section('og_type', 'article')
@if (! $indexable)
    @section('robots', 'noindex, follow')
@endif

@push('head')
    @php
        $pageUrl = route('analysis.show', $slug);
        $event = array_filter([
            '@type' => 'SportsEvent',
            '@id' => $pageUrl . '#event',
            'name' => "{$home} vs {$away}",
            'sport' => 'Soccer',
            'startDate' => $fixture->kickoff?->toAtomString(),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'homeTeam' => ['@type' => 'SportsTeam', 'name' => $home],
            'awayTeam' => ['@type' => 'SportsTeam', 'name' => $away],
            'competitor' => [
                ['@type' => 'SportsTeam', 'name' => $home],
                ['@type' => 'SportsTeam', 'name' => $away],
            ],
            'superEvent' => $league !== 'Football' ? ['@type' => 'SportsEvent', 'name' => $league] : null,
            'url' => $pageUrl,
        ]);
        $fixtureSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                array_filter([
                    '@type' => 'WebPage',
                    '@id' => $pageUrl . '#webpage',
                    'url' => $pageUrl,
                    'name' => "{$home} vs {$away} Prediction & Analysis",
                    'description' => $metaDescription,
                    'isPartOf' => ['@id' => url('/') . '#website'],
                    'inLanguage' => 'en',
                    'datePublished' => $published ? \Illuminate\Support\Carbon::parse($published)->toAtomString() : null,
                    'dateModified' => $modified ? \Illuminate\Support\Carbon::parse($modified)->toAtomString() : null,
                    'about' => ['@id' => $pageUrl . '#event'],
                    'breadcrumb' => ['@id' => $pageUrl . '#breadcrumb'],
                    'publisher' => ['@id' => url('/') . '#organization'],
                ]),
                $event,
                [
                    '@type' => 'BreadcrumbList',
                    '@id' => $pageUrl . '#breadcrumb',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Analysis', 'item' => route('analysis')],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => "{$home} vs {$away}", 'item' => $pageUrl],
                    ],
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($fixtureSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')

    <!-- Page header -->
    <section class="page-head" data-track-section="page-head">
        <div class="hero__bg" aria-hidden="true"><div class="hero__grid"></div><div class="hero__glow"></div></div>
        <div class="container">
            <div class="page-head__inner">
                <nav class="crumbs" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}">Home</a><span aria-hidden="true">/</span>
                    <a href="{{ route('analysis') }}">Analysis</a><span aria-hidden="true">/</span>
                    <span aria-current="page">{{ $home }} vs {{ $away }}</span>
                </nav>
                <h1 class="page-head__title">{{ $home }} vs {{ $away }}: Match Analysis &amp; Preview</h1>
                <p class="page-head__lede">{{ implode(' ', $summary) }}</p>
                <div class="page-head__meta">
                    <span class="chip">{{ $league }}</span>
                    @if ($fixture->kickoff)
                        <span>Kickoff <time datetime="{{ $fixture->kickoff->toAtomString() }}">{{ $fixture->localKickoff()->format('D j M Y, H:i T') }}</time></span>
                    @else
                        <span>Kickoff date TBC</span>
                    @endif
                    @if ($score)
                        <span><b>Full time</b></span>
                    @endif
                    @if ($modified)
                        <span>Updated {{ \Illuminate\Support\Carbon::parse($modified)->setTimezone(\App\Models\Prediction::displayTimezone())->format('j M, H:i T') }}</span>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="notice-bar">
        <div class="container notice-bar__inner">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
            <p><b>Educational analysis.</b> Based on available statistics and current form. Football outcomes are unpredictable.</p>
        </div>
    </div>

    <section class="section section--soft" data-track-section="fixture">
        <div class="container">

            <!-- Fixture card: same visual block as the analysis hub -->
            <article class="acard">
                <header class="acard__top">
                    <span class="chip">{{ $league }}</span>
                    <span class="acard__kick num">
                        <b>{{ $fixture->localKickoff()?->format('D j M') ?? 'Date TBC' }}</b>
                        {{ $fixture->kickoffTime() }}
                    </span>
                </header>
                <div class="acard__fixture">
                    <div class="acard__team">
                        <span class="crest" style="--c:{{ $hCol }}" aria-hidden="true">{{ $hInit }}</span>
                        <div><p class="acard__name">{{ $home }}</p><span class="cap">Home</span></div>
                    </div>
                    @if ($score)
                        <span class="acard__vs fx-score" aria-label="Final score {{ $score->home_goals }} to {{ $score->away_goals }}">{{ $score->home_goals }}–{{ $score->away_goals }}</span>
                    @else
                        <span class="acard__vs">VS</span>
                    @endif
                    <div class="acard__team acard__team--away">
                        <span class="crest" style="--c:{{ $aCol }}" aria-hidden="true">{{ $aInit }}</span>
                        <div><p class="acard__name">{{ $away }}</p><span class="cap">Away</span></div>
                    </div>
                </div>
            </article>

            <!-- Recent form -->
            <div class="fx-section">
                <h2>Recent form</h2>
                <div class="acard"><div class="acard__section">
                    <p class="acard__label">Last five matches before kickoff</p>
                    @foreach ([[$home, $fixture->home_form ?? []], [$away, $fixture->away_form ?? []]] as [$team, $form])
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
                </div></div>
            </div>

            <!-- Projected goals -->
            @if ($fixture->xg_home !== null && $fixture->xg_away !== null)
                @php
                    $xgTotal = max((float) $fixture->xg_home + (float) $fixture->xg_away, 0.01);
                    $xgH = round((float) $fixture->xg_home / $xgTotal * 100, 1);
                @endphp
                <div class="fx-section">
                    <h2>Projected goals (xG)</h2>
                    <div class="acard"><div class="acard__section">
                        <div class="xgbar">
                            <div class="xgbar__nums"><b>{{ number_format((float) $fixture->xg_home, 2) }}</b><span>xG</span><b>{{ number_format((float) $fixture->xg_away, 2) }}</b></div>
                            <div class="split grow" aria-hidden="true"><i style="flex-basis:{{ $xgH }}%"></i><i style="flex-basis:{{ 100 - $xgH }}%"></i></div>
                            <div class="xgbar__teams"><span>{{ $home }}</span><span>{{ $away }}</span></div>
                        </div>
                    </div></div>
                </div>
            @endif

            <!-- Head-to-head -->
            @php $h2h = $reads->map(fn ($p) => $p->h2hNoteLabel())->filter()->first(); @endphp
            @if ($h2h || $previousMeetings->isNotEmpty())
                <div class="fx-section">
                    <h2>Head-to-head</h2>
                    @if ($h2h)
                        <div class="callout callout--info"><p>{{ $h2h }}</p></div>
                    @endif
                    @if ($previousMeetings->isNotEmpty())
                        <p class="acard__label" style="margin-top:16px">Our earlier analysis of this fixture</p>
                        <ul class="fx-list">
                            @foreach ($previousMeetings as $m)
                                <li><a href="{{ $m->fixtureUrl() }}">{{ $m->home }} vs {{ $m->away }}</a><span class="num">{{ $m->localKickoff()?->format('j M Y') ?? 'Date TBC' }}@if ($m->home_goals !== null) · {{ $m->home_goals }}–{{ $m->away_goals }}@endif</span></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            <!-- Model reads -->
            <div class="fx-section">
                <h2>Model read</h2>
                <div class="fx-reads">
                    @foreach ($reads as $p)
                        @php $prob = round(($p->predicted_probability ?? 0) * 100, 1); @endphp
                        <div class="read">
                            <h3 class="read__market">{{ $p->market }}</h3>
                            <div class="read__prob"><b>{{ number_format($prob, 1) }}%</b><span>calibrated probability</span></div>
                            <div class="meter" aria-hidden="true"><i style="width:{{ min($prob, 100) }}%"></i></div>
                            <dl class="read__facts">
                                <div><dt>Verdict</dt><dd><span class="verdict {{ $p->verdictClass() }}">{{ $p->verdictLabel() }}</span></dd></div>
                                @if ($p->odds !== null)
                                    <div><dt>Odds</dt><dd class="num">{{ number_format($p->odds, 2) }}</dd></div>
                                @endif
                                @if ($p->settled_at)
                                    <div><dt>Outcome</dt><dd><span class="outcome {{ $p->outcomeClass() }}">{{ $p->outcomeLabel() }}</span></dd></div>
                                @endif
                            </dl>
                        </div>
                    @endforeach
                </div>
                <p class="read__odds-note" style="margin-top:12px">A statistical model output, not a guarantee. Odds are shown only where a real bookmaker price exists for that market.</p>
            </div>

            <!-- Result -->
            @if ($score)
                <div class="fx-section">
                    <h2>Result</h2>
                    <p>Final score: <strong>{{ $home }} {{ $score->home_goals }}–{{ $score->away_goals }} {{ $away }}</strong>. This fixture is graded on the <a class="text-link" href="{{ route('results') }}">results ledger</a> alongside every other read we've published — hits and misses alike.</p>
                </div>
            @endif

            @include('partials.telegram-cta', ['placement' => 'fixture', 'headline' => $score ? 'Get the next round of analysis on Telegram.' : 'Get analysis like this before kickoff on Telegram.'])

            <!-- More fixtures -->
            @if ($sameDay->isNotEmpty())
                <div class="fx-section">
                    <h2>More matches on {{ $fixture->localKickoff()->format('l j F') }}</h2>
                    <ul class="fx-list">
                        @foreach ($sameDay as $m)
                            <li><a href="{{ $m->fixtureUrl() }}">{{ $m->home }} vs {{ $m->away }}</a><span class="num">{{ $m->leagueLabel() }}</span></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <p style="margin-top:32px"><a class="btn btn--ghost btn--sm" href="{{ route('analysis') }}">All of today's football analysis</a></p>

            <div class="callout" style="margin-top:32px">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
                <div>
                    <p><strong>This analysis does not predict or guarantee any specific outcome.</strong> It is based on publicly available statistics and recent form, and football is shaped by many variables no model captures. Content is for educational purposes only. 18+.</p>
                    <p><a href="{{ route('disclaimer') }}">Read the full disclaimer</a></p>
                </div>
            </div>
        </div>
    </section>

@include('partials.offers')

@endsection
