@extends('layouts.app')

@section('title', 'Banker 2 Odds & 10 Odds Combos Today | LSA')
@section('description', "Today's 2 odds and 10 odds combos built from LSA's confident, odds-backed picks: every leg shown, the model's chance of landing, and every past combo graded won or lost.")

@php
    $bundles = collect([$banker2, $odds10, $weekendPicks])->filter();
    $legIcon = ['won' => '✓', 'lost' => '✕', 'void' => '–', 'pending' => ''];
@endphp

@section('content')

    <!-- Page header -->
    <section class="page-head" data-track-section="page-head">
        <div class="hero__bg" aria-hidden="true"><div class="hero__grid"></div><div class="hero__glow"></div></div>
        <div class="container">
            <div class="page-head__inner">
                <p class="kicker">Multi-pick combos</p>
                <h1 class="page-head__title">Banker 2 Odds &amp; 10 Odds</h1>
                <p class="page-head__lede">Built from today's confident picks with real bookmaker odds, choosing the combination most likely to land together. Every combo is frozen at its first kickoff and graded won or lost, so you can see exactly how they've done.</p>
                @foreach ([\App\Models\AccumulatorBundle::TYPE_BANKER_2 => 'Banker 2 Odds', \App\Models\AccumulatorBundle::TYPE_ODDS_10 => '10 Odds'] as $t => $name)
                    @if ($record[$t]['graded'] > 0)
                        <span class="combo-recordline"><b>{{ $name }}</b> won {{ $record[$t]['won'] }} of {{ $record[$t]['graded'] }}</span>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    <div class="notice-bar">
        <div class="container notice-bar__inner">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
            <p><b>Educational analysis, not betting advice.</b> Combining picks multiplies risk, not just odds: one leg missing breaks the whole combo. 18+ only.</p>
        </div>
    </div>

    <section class="section" data-track-section="combos">
        <div class="container">
            @if ($bundles->isEmpty())
                <div class="empty">
                    <span class="empty__icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                    <h3>No combo qualifies right now</h3>
                    <p>A combo only publishes when today's priced picks can reach the target odds; nothing gets force-filled. Check back after the next scan, or see every individual pick on the <a href="{{ route('analysis') }}">analysis page</a>.</p>
                </div>
            @else
                <div class="combo-grid">
                    @foreach ($bundles as $b)
                        @php $chance = $b->modelChance(); @endphp
                        <article class="slip slip--{{ $b->statusClass() }}" data-track-section="combo-{{ $b->type }}">
                            <header class="slip__head">
                                <div>
                                    <h2 class="slip__title">{{ $b->label() }}</h2>
                                    <p class="slip__date num">{{ $b->date->format('D j M') }} · {{ $b->legs->count() }} {{ \Illuminate\Support\Str::plural('leg', $b->legs->count()) }}</p>
                                </div>
                                @if ($b->combined_odds)
                                    <div class="slip__odds"><span class="num">{{ number_format($b->combined_odds, 2) }}</span><small>combined odds</small></div>
                                @endif
                            </header>

                            @if ($b->type !== \App\Models\AccumulatorBundle::TYPE_WEEKEND_PICKS)
                                <div class="slip__meta">
                                    <span class="slip__status">{{ $b->statusLabel() }}</span>
                                    @if ($chance !== null)
                                        <span>Model's chance all legs land: <b class="num">{{ number_format($chance * 100, 0) }}%</b></span>
                                    @endif
                                </div>
                            @else
                                <p class="slip__strap">{{ $b->strapline() }}</p>
                            @endif

                            <ol class="slip__legs">
                                @foreach ($b->legs as $p)
                                    @php $state = \App\Models\AccumulatorBundle::legState($p); @endphp
                                    <li class="leg leg--{{ $state }}">
                                        <span class="leg__time num">{{ $p->localKickoff()?->format('H:i') ?? 'TBC' }}</span>
                                        <span class="leg__body">
                                            <a class="leg__match" href="{{ $p->fixtureUrl() ?? route('analysis') }}">{{ $p->home }} <span class="vs-sm">vs</span> {{ $p->away }}</a>
                                            <span class="leg__pick"><b>{{ $p->market }}</b> · {{ $p->leagueLabel() }}</span>
                                        </span>
                                        <span class="leg__right">
                                            @if ($p->home_goals !== null)
                                                <span class="leg__score num">{{ $p->home_goals }}–{{ $p->away_goals }}</span>
                                            @endif
                                            @if ($p->odds)<span class="leg__odds num">{{ number_format($p->odds, 2) }}</span>@endif
                                            @if ($state !== 'pending')<span class="leg__icon" aria-label="{{ $state }}">{{ $legIcon[$state] }}</span>@endif
                                        </span>
                                    </li>
                                @endforeach
                            </ol>

                            @if ($b->status === \App\Models\AccumulatorBundle::STATUS_PENDING && ! $b->isLive() && $stakeOffers->isNotEmpty())
                                <div class="combo-panel__stake">
                                    <p class="combo-panel__stake-label">Build this combo on</p>
                                    <div class="combo-panel__stake-btns">
                                        @foreach ($stakeOffers as $offer)
                                            <a class="btn btn--ghost btn--sm" href="{{ route('offer.go', $offer->slug) }}" rel="sponsored noopener" target="_blank">{{ $offer->name }}</a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <!-- Combo record: every graded slip, wins and losses -->
    @if ($history->isNotEmpty())
        <section class="section section--soft" id="combo-record" data-track-section="combo-record">
            <div class="container">
                <header class="section-head">
                    <p class="kicker">Combo record</p>
                    <h2 class="h2">Every combo, graded.</h2>
                    <p class="lede">Each Banker 2 Odds and 10 Odds slip is frozen at its first kickoff, then marked won or lost. Nothing is removed.</p>
                </header>
                <div class="rtable-wrap">
                    <table class="rtable">
                        <thead><tr><th scope="col">Date</th><th scope="col">Combo</th><th scope="col">Legs</th><th scope="col">Odds</th><th scope="col">Result</th></tr></thead>
                        <tbody>
                            @foreach ($history as $h)
                                <tr class="is-{{ $h->status === 'won' ? 'aligned' : 'unexpected' }}">
                                    <td class="c-date" data-label="Date">{{ $h->date->format('j M') }}</td>
                                    <td class="c-match" data-label="Combo">{{ $h->label() }}</td>
                                    <td class="c-factor" data-label="Legs">{{ $h->legs->where(fn ($p) => $p->hit === true)->count() }} of {{ $h->legs->count() }} landed</td>
                                    <td class="num" data-label="Odds">{{ number_format($h->combined_odds, 2) }}</td>
                                    <td class="c-outcome" data-label="Result"><span class="outcome {{ $h->status === 'won' ? 'aligned' : 'unexpected' }}">{{ $h->statusLabel() }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif

    <!-- CTA -->
    <section class="cta">
        <div class="container cta__inner">
            <h2 class="cta__title">Want the full picture?<br><span class="muted">Every individual pick, every day.</span></h2>
            <div class="cta__actions">
                <a class="btn btn--primary" href="{{ route('analysis') }}">Today's analysis
                    <svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
                <a class="btn btn--ghost" href="{{ route('results') }}">See our track record</a>
            </div>
        </div>
    </section>

@endsection
