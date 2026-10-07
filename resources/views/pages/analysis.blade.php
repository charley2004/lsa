@extends('layouts.app')

@section('title', 'Match Analysis | LEGIT SOURCE ANALYSIS')
@section('description', 'Current football match analysis with statistics, form, and insights from LSA.')

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
        $date = \Illuminate\Support\Carbon::parse($key);
        if ($date->isToday()) {
            return 'Today — ' . $date->format('j M');
        }
        if ($date->isTomorrow()) {
            return 'Tomorrow — ' . $date->format('j M');
        }
        return $date->format('l, j F');
    };

    // Short label for the filter tab itself (stays compact at any slate length).
    $dayTabLabel = function (string $key) use ($dayLabel): string {
        if ($key === 'tbc') {
            return 'TBC';
        }
        $date = \Illuminate\Support\Carbon::parse($key);
        if ($date->isToday()) {
            return 'Today';
        }
        if ($date->isTomorrow()) {
            return 'Tomorrow';
        }
        return $date->format('D j M');
    };
@endphp

@section('content')

    <!-- Page header -->
    <section class="page-head">
        <div class="hero__bg" aria-hidden="true"><div class="hero__grid"></div><div class="hero__glow"></div></div>
        <div class="container">
            <div class="page-head__inner">
                <p class="kicker" data-hero style="--d:0ms">Match intelligence</p>
                <h1 class="page-head__title" data-split>Football match analysis</h1>
                <p class="page-head__lede" data-hero style="--d:350ms">Form, projected goals and our calibrated model's read for upcoming fixtures — with every outcome later published on the results ledger.</p>
                <div class="page-head__meta" data-hero style="--d:480ms">
                    <span><i class="dot-live" aria-hidden="true"></i><b>{{ $predictions->count() }}</b> {{ \Illuminate\Support\Str::plural('fixture', $predictions->count()) }} analysed</span>
                    <span>Updated after every scan</span>
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
    <section class="section section--soft">
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
                            <h3>{{ $dayLabel($day) }}</h3>
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
                                    <header class="acard__top">
                                        <span class="chip">{{ $p->league ?? 'Football' }}</span>
                                        <span class="acard__kick num">
                                            <b>{{ $p->kickoff?->format('D j M') ?? 'Date TBC' }}</b>
                                            {{ $p->kickoff?->format('H:i') }}
                                        </span>
                                    </header>

                                    <div class="acard__fixture">
                                        <div class="acard__team">
                                            <span class="crest" style="--c:{{ $hCol }}" aria-hidden="true">{{ $hInit }}</span>
                                            <div><h2>{{ $p->home }}</h2><span class="cap">Home</span></div>
                                        </div>
                                        <span class="acard__vs">VS</span>
                                        <div class="acard__team acard__team--away">
                                            <span class="crest" style="--c:{{ $aCol }}" aria-hidden="true">{{ $aInit }}</span>
                                            <div><h2>{{ $p->away }}</h2><span class="cap">Away</span></div>
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
                                            <div class="read__prob"><b>{{ number_format($prob, 1) }}%</b><span>calibrated probability</span></div>
                                            <div class="meter" aria-hidden="true"><i style="width:{{ min($prob, 100) }}%"></i></div>

                                            <dl class="read__facts">
                                                <div><dt>Verdict</dt><dd><span class="verdict {{ $p->verdictClass() }}">{{ $p->verdictLabel() }}</span></dd></div>
                                                <div><dt>Market skill</dt><dd class="num">{{ number_format($p->skill ?? 0, 4) }}</dd></div>
                                            </dl>

                                            <div class="read__notes">
                                                @if ($p->h2h_note)
                                                    <div class="callout callout--info"><p><strong>Head-to-head:</strong> {{ $p->h2h_note }}</p></div>
                                                @endif
                                                <div class="callout callout--plain"><p>A statistical model output, not a guarantee. Skill is measured against historical calibration. <a href="{{ route('disclaimer') }}">Read the disclaimer</a>.</p></div>
                                            </div>
                                        </aside>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endforeach
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

@endsection
