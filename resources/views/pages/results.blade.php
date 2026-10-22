@extends('layouts.app')

@section('title', 'Analysis Results | LEGIT SOURCE ANALYSIS')
@section('description', 'Historical performance tracking of LSA match analysis - transparent results showing both successful and unsuccessful assessments.')

@php
    $settledCount = (int) ($track['n_settled'] ?? 0);
    $hits         = (int) ($track['hits'] ?? 0);
    $misses       = max($settledCount - $hits, 0);
    $rate         = $settledCount ? (int) round(($track['hit_rate'] ?? 0) * 100) : 0;
@endphp

@section('content')

    <!-- Page header -->
    <section class="page-head">
        <div class="hero__bg" aria-hidden="true"><div class="hero__grid"></div><div class="hero__glow"></div></div>
        <div class="container">
            <div class="page-head__inner">
                <p class="kicker" data-hero style="--d:0ms">Performance tracking</p>
                <h1 class="page-head__title" data-split>The results ledger</h1>
                <p class="page-head__lede" data-hero style="--d:350ms">Every match we analyse, and how it actually ended — the reads that held up and the ones that didn't.</p>
                <div class="page-head__meta" data-hero style="--d:480ms">
                    <span><i class="dot-live" aria-hidden="true"></i><b>{{ $settledCount }}</b> settled {{ \Illuminate\Support\Str::plural('match', $settledCount) }}</span>
                    <span>Nothing removed, nothing edited after kickoff</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Performance (dark) -->
    <section class="section record">
        <div class="record__bg" aria-hidden="true"></div>
        <div class="container record__inner">
            <div class="record__copy" data-reveal>
                <p class="kicker kicker--light">Our commitment</p>
                <h2 class="h2">Honest reporting, including the misses.</h2>
                <p class="lede">This page compares actual outcomes with the factors highlighted in our analysis. It is a review of our reads — not a record of predictions or guarantees.</p>
                <ul class="checks">
                    <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5L20 7"/></svg><span><strong style="color:#fff;font-weight:500">Aligned</strong> — the key factors we identified were reflected in the result</span></li>
                    <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5L20 7"/></svg><span><strong style="color:#fff;font-weight:500">Unexpected</strong> — the outcome differed significantly from the analysed patterns</span></li>
                </ul>
            </div>

            <div class="panel" data-reveal style="--i:1">
                <div class="panel__head"><span>Analysis performance</span><span class="num">All time</span></div>

                @if ($settledCount === 0)
                    <div class="empty" style="background:transparent;border-color:var(--dark-line);margin-top:20px">
                        <h3 style="color:#fff">No settled results yet</h3>
                        <p style="color:var(--dark-muted)">Results appear here once analysed matches have finished.</p>
                    </div>
                @else
                    <div class="panel__main">
                        <div class="ring" role="img" aria-label="{{ $rate }} percent aligned: {{ $hits }} of {{ $settledCount }} matches">
                            <svg viewBox="0 0 120 120" aria-hidden="true">
                                <circle class="ring__track" cx="60" cy="60" r="52"/>
                                <circle class="ring__value" cx="60" cy="60" r="52" pathLength="100" style="--off:{{ 100 - $rate }}"/>
                            </svg>
                            <div class="ring__label"><span class="num ring__pct"><span data-count="{{ $rate }}">{{ $rate }}</span>%</span><span>aligned</span></div>
                        </div>

                        <dl class="tallies">
                            <div class="tally"><dt>Analysed</dt><dd class="num" data-count="{{ $settledCount }}">{{ $settledCount }}</dd><span class="tally__bar"><i style="width:100%"></i></span></div>
                            <div class="tally tally--pos"><dt>Aligned</dt><dd class="num" data-count="{{ $hits }}">{{ $hits }}</dd><span class="tally__bar"><i style="width:{{ $rate }}%"></i></span></div>
                            <div class="tally tally--miss"><dt>Unexpected</dt><dd class="num" data-count="{{ $misses }}">{{ $misses }}</dd><span class="tally__bar"><i style="width:{{ 100 - $rate }}%"></i></span></div>
                        </dl>
                    </div>

                    @php $recent = $settled->take(12)->reverse()->values(); @endphp
                    @if ($recent->isNotEmpty())
                        <div class="strip">
                            <div class="strip__head"><span>Last {{ $recent->count() }} analyses</span><span class="strip__key"><i class="cell cell--pos"></i>Aligned <i class="cell cell--miss"></i>Unexpected</span></div>
                            <ol class="strip__cells" style="grid-template-columns:repeat({{ max($recent->count(), 6) }},1fr)" aria-label="Most recent results, oldest first">
                                @foreach ($recent as $i => $r)
                                    <li class="cell {{ $r->outcomeClass() === 'aligned' ? 'cell--pos' : 'cell--miss' }}" style="--i:{{ $i }}" title="{{ $r->home }} vs {{ $r->away }}: {{ $r->outcomeLabel() }}"><span class="sr-only">{{ $r->home }} vs {{ $r->away }}: {{ $r->outcomeLabel() }}</span></li>
                                @endforeach
                            </ol>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </section>

    <!-- Ledger table -->
    <section class="section" id="ledger">
        <div class="container">
            <header class="section-head section-head--split" data-reveal>
                <div>
                    <p class="kicker">Ledger</p>
                    <h2 class="h2">Recent match results</h2>
                </div>
            </header>

            @if ($settled->isNotEmpty())
                <div class="ledger-tools" data-reveal>
                    <div class="filters" role="group" aria-label="Filter results">
                        <button type="button" class="filter" data-filter="all" aria-pressed="true">All <span class="num">{{ $settled->count() }}</span></button>
                        <button type="button" class="filter" data-filter="aligned" aria-pressed="false">Aligned <span class="num" data-filter-count="aligned"></span></button>
                        <button type="button" class="filter" data-filter="unexpected" aria-pressed="false">Unexpected <span class="num" data-filter-count="unexpected"></span></button>
                    </div>
                    <p class="ledger-count" aria-live="polite" data-filter-status>Showing all {{ $settled->count() }}</p>
                </div>
            @endif

            <div class="rtable-wrap" data-reveal>
                <table class="rtable" data-ledger>
                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Match</th>
                            <th scope="col">Key factor analysed</th>
                            <th scope="col">Result</th>
                            <th scope="col">Outcome</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($settled as $i => $p)
                            @php $oc = $p->outcomeClass(); @endphp
                            <tr class="is-{{ $oc }}" data-outcome="{{ $oc }}" style="--n:{{ $i }}">
                                <td class="c-date" data-label="Date">{{ $p->kickoff?->format('j M') ?? '—' }}</td>
                                <td class="c-match" data-label="Match">{{ $p->home }}<span class="vs-sm">vs</span>{{ $p->away }}</td>
                                <td class="c-factor" data-label="Key factor">{{ $p->market }}@if ($p->odds)<span class="c-factor__odds"> @ {{ number_format($p->odds, 2) }}</span>@endif</td>
                                <td class="c-score" data-label="Result"><span class="score">{{ $p->home_goals }}–{{ $p->away_goals }}</span></td>
                                <td class="c-outcome" data-label="Outcome"><span class="outcome {{ $oc }}">{{ $p->outcomeLabel() }}</span></td>
                            </tr>
                        @empty
                            <tr><td class="c-empty" colspan="5">No settled results yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- What we learn -->
    <section class="section section--soft">
        <div class="container">
            <header class="section-head" data-reveal>
                <p class="kicker">Insights</p>
                <h2 class="h2">What we learn from results</h2>
            </header>
            <div class="steps steps--3" data-stagger>
                <article class="step" data-spot>
                    <span class="step__num num">01</span>
                    <span class="step__icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path pathLength="1" d="M3 17l6-6 4 4 8-8"/><path pathLength="1" d="M15 7h6v6"/></svg></span>
                    <h3>Unexpected outcomes</h3>
                    <p>When results diverge from the analysis, it shows football involves variables beyond statistics — individual moments, refereeing and psychology.</p>
                </article>
                <article class="step" data-spot>
                    <span class="step__num num">02</span>
                    <span class="step__icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path pathLength="1" d="M5 12l5 5L20 7"/></svg></span>
                    <h3>Pattern validation</h3>
                    <p>When outcomes align with highlighted factors, the framework is capturing real trends. That still doesn't make the next match predictable.</p>
                </article>
                <article class="step" data-spot>
                    <span class="step__num num">03</span>
                    <span class="step__icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path pathLength="1" d="M20 12a8 8 0 1 1-2.3-5.6"/><path pathLength="1" d="M20 4v5h-5"/></svg></span>
                    <h3>Continuous improvement</h3>
                    <p>Every result is reviewed to refine the approach and find blind spots. Publishing both outcomes is central to that process.</p>
                </article>
            </div>
        </div>
    </section>

    <!-- Honest assessment -->
    @if ($settledCount > 0)
    <section class="section">
        <div class="container" data-reveal>
            <p class="kicker">Our honest assessment</p>
            <p class="statement" style="margin-top:18px">A <span class="hl">{{ $rate }}%</span> alignment rate shows that statistical analysis can find meaningful patterns — <span class="muted">and that football remains substantially shaped by what no model can see.</span></p>
            <div class="statement-body">
                <p>This is why we don't claim to predict results or guarantee outcomes. The goal is analysis that helps you understand matches more deeply, not certainty in an uncertain sport.</p>
                <div class="callout callout--info"><p><strong>Past performance of our analysis does not indicate future accuracy.</strong> Each match presents its own circumstances.</p></div>
            </div>
        </div>
    </section>
    @endif

    <!-- CTA -->
    <section class="cta">
        <div class="container cta__inner" data-reveal>
            <h2 class="cta__title">See the method in action.<br><span class="muted">Read today's analysis.</span></h2>
            <div class="cta__actions">
                <a class="btn btn--primary" href="{{ route('analysis') }}">Latest analysis
                    <svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
                <a class="btn btn--ghost" href="{{ route('about') }}">Our approach</a>
            </div>
        </div>
    </section>

@endsection
