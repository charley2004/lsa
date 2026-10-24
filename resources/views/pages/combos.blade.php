@extends('layouts.app')

@section('title', 'Combo Picks | LEGIT SOURCE ANALYSIS')
@section('description', 'Banker 2, the 10-Odds Combo, and Top Weekend Picks — curated multi-pick bundles built only from confident, odds-backed reads.')

@php
    $bundles = [
        ['data' => $banker2, 'type' => \App\Models\AccumulatorBundle::TYPE_BANKER_2],
        ['data' => $odds10, 'type' => \App\Models\AccumulatorBundle::TYPE_ODDS_10],
        ['data' => $weekendPicks, 'type' => \App\Models\AccumulatorBundle::TYPE_WEEKEND_PICKS],
    ];
    $anyLive = collect($bundles)->contains(fn ($b) => $b['data'] !== null);
@endphp

@section('content')

    <!-- Page header -->
    <section class="page-head" data-track-section="page-head">
        <div class="hero__bg" aria-hidden="true"><div class="hero__grid"></div><div class="hero__glow"></div></div>
        <div class="container">
            <div class="page-head__inner">
                <p class="kicker" data-hero style="--d:0ms">Multi-pick bundles</p>
                <h1 class="page-head__title" data-split>Combo picks</h1>
                <p class="page-head__lede" data-hero style="--d:350ms">Banker 2, the 10-Odds Combo, and Top Weekend Picks — built only when the picks actually qualify. No bundle gets padded out just to fill a slot.</p>
            </div>
        </div>
    </section>

    <div class="notice-bar">
        <div class="container notice-bar__inner">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
            <p><b>Educational analysis, not betting advice.</b> Combining picks multiplies risk, not just odds — one leg missing breaks the whole accumulator. 18+ only.</p>
        </div>
    </div>

    <section class="section" data-track-section="combos">
        <div class="container">
            @if (! $anyLive)
                <div class="empty" data-reveal>
                    <span class="empty__icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                    <h3>No combo bundles qualify right now</h3>
                    <p>Banker 2 and the 10-Odds Combo only publish when real picks actually meet the bar — nothing gets force-filled. Check back after the next scan, or see every individual read on the <a href="{{ route('analysis') }}">analysis page</a>.</p>
                </div>
            @else
                <div class="combo-grid" data-stagger>
                    @foreach ($bundles as $bundle)
                        @continue(! $bundle['data'])
                        @php $b = $bundle['data']; @endphp
                        <article class="combo-panel" data-spot>
                            <header class="combo-panel__head">
                                <div>
                                    <h2 class="h3">{{ $b->label() }}</h2>
                                    <p class="combo-panel__strap">{{ $b->strapline() }}</p>
                                </div>
                                @if ($b->combined_odds)
                                    <span class="combo-odds"><span class="combo-odds__num">{{ number_format($b->combined_odds, 2) }}</span><span class="combo-odds__label">combined odds</span></span>
                                @endif
                            </header>

                            <div class="combo-panel__legs">
                                @foreach ($b->legs as $p)
                                    <article class="match match--compact">
                                        <div class="match__top">
                                            <span class="chip">{{ $p->leagueLabel() }}</span>
                                            @if ($p->kickoff)
                                                <time class="num" datetime="{{ $p->kickoff->format('Y-m-d\TH:i') }}">{{ $p->kickoff->format('D j M · H:i') }}</time>
                                            @else
                                                <time class="num">Date TBC</time>
                                            @endif
                                        </div>
                                        <div class="match__teams">
                                            <div class="match__team"><span class="crest" style="--c:#334155">{{ strtoupper(substr($p->home, 0, 3)) }}</span>{{ $p->home }}</div>
                                            <div class="match__team"><span class="crest" style="--c:#475569">{{ strtoupper(substr($p->away, 0, 3)) }}</span>{{ $p->away }}</div>
                                        </div>
                                        <p class="match__text">
                                            <strong>{{ $p->market }}</strong> at {{ number_format($p->predicted_probability * 100, 1) }}%
                                            @if ($p->odds)
                                                <span class="combo-leg-odds">@ {{ number_format($p->odds, 2) }}</span>
                                            @endif
                                        </p>
                                    </article>
                                @endforeach
                            </div>

                            @if ($stakeOffers->isNotEmpty())
                                <div class="combo-panel__stake">
                                    <p class="combo-panel__stake-label">Stake this combo</p>
                                    <div class="combo-panel__stake-btns">
                                        @foreach ($stakeOffers as $offer)
                                            <a class="btn btn--ghost btn--sm" href="{{ route('offer.go', $offer->slug) }}" rel="sponsored noopener" target="_blank">Stake on {{ $offer->name }}</a>
                                        @endforeach
                                    </div>
                                    <p class="combo-panel__stake-note">Opens the bookmaker's own site/app — build the combo above there yourself using the picks and odds shown.</p>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <!-- CTA -->
    <section class="cta">
        <div class="container cta__inner" data-reveal>
            <h2 class="cta__title">Want the full picture?<br><span class="muted">Every individual read, every day.</span></h2>
            <div class="cta__actions">
                <a class="btn btn--primary" href="{{ route('analysis') }}">Latest analysis
                    <svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
                <a class="btn btn--ghost" href="{{ route('results') }}">See our track record</a>
            </div>
        </div>
    </section>

@endsection
