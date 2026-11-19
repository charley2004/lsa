@extends('layouts.app')

@section('title', 'LSA: Data-Driven Football Analysis & Match Previews')
@section('description', 'Data-driven football match analysis: projected goals (xG), recent form, head-to-head and calibrated probabilities — with every result published, hits and misses alike.')

@section('content')

<!-- ============ HERO ============ -->
<section class="hero" data-track-section="hero">
  <div class="hero__bg" aria-hidden="true">
    <div class="hero__grid"></div>
    <div class="hero__glow"></div>
    <svg class="hero__pitch" viewBox="0 0 1050 680" fill="none">
      <rect class="ln" pathLength="1" x="5" y="5" width="1040" height="670" rx="2"/>
      <path class="ln" pathLength="1" d="M525 5v670"/>
      <circle class="ln" pathLength="1" cx="525" cy="340" r="91.5"/>
      <rect class="ln" pathLength="1" x="5" y="138.5" width="165" height="403"/>
      <rect class="ln" pathLength="1" x="5" y="248.5" width="55" height="183"/>
      <path class="ln" pathLength="1" d="M170 266.9a91.5 91.5 0 0 1 0 146.2"/>
      <rect class="ln" pathLength="1" x="880" y="138.5" width="165" height="403"/>
      <rect class="ln" pathLength="1" x="990" y="248.5" width="55" height="183"/>
      <path class="ln" pathLength="1" d="M880 266.9a91.5 91.5 0 0 0 0 146.2"/>
      <g class="hero__shots">
        <circle cx="905" cy="300" r="9" style="--i:0"/>
        <circle cx="948" cy="352" r="14" style="--i:1"/>
        <circle cx="842" cy="390" r="6" style="--i:2"/>
        <circle cx="968" cy="318" r="11" style="--i:3"/>
        <circle cx="120" cy="330" r="10" style="--i:4"/>
        <circle cx="88" cy="372" r="7" style="--i:5"/>
        <circle cx="760" cy="260" r="5" style="--i:6"/>
      </g>
    </svg>
  </div>

  <div class="container hero__inner">
    <div class="hero__copy">
      <p class="eyebrow" data-hero style="--d:0ms"><span class="eyebrow__tag">New</span> Results ledger updated after every match</p>

      <h1 class="hero__title" data-split>Football analysis <span class="muted">that shows its work.</span></h1>

      <p class="hero__lede" data-hero style="--d:450ms">LSA breaks down fixtures with projected goals (xG), recent form and head-to-head patterns — then publishes how every read turned out. Hits and misses alike.</p>

      <div class="hero__actions" data-hero style="--d:560ms">
        <a class="btn btn--primary" href="{{ route('analysis') }}">Explore analysis
          <svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
        <a class="btn btn--ghost" href="{{ route('results') }}">See our record</a>
      </div>

      <dl class="hero__proof" data-hero style="--d:680ms">
        <div><dt>Fixtures analysed</dt><dd class="num" data-count="{{ $stats['fixtures_analysed'] }}">{{ $stats['fixtures_analysed'] }}</dd></div>
        <div><dt>Competitions</dt><dd class="num" data-count="{{ $stats['competitions'] }}">{{ $stats['competitions'] }}</dd></div>
        <div><dt>Aligned, all-time</dt><dd class="num"><span data-count="{{ $stats['hit_rate'] }}">{{ $stats['hit_rate'] }}</span>%</dd></div>
      </dl>
    </div>

    <!-- Analysis console: real fixtures from the Analysis page (see HomeController) -->
    @if ($consoleData->isNotEmpty())
    @php $c0 = $consoleData->first(); @endphp
    <div class="console-wrap" data-hero style="--d:300ms">
      <script type="application/json" id="console-data">{!! json_encode($consoleData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
      <div class="console" data-console aria-roledescription="carousel" aria-label="{{ $consoleLabel }}">
        <span class="console__progress" data-progress></span>

        <div class="console__top">
          <a class="console__tag" data-c-link href="{{ $c0['url'] }}"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 6-7"/></svg> {{ $consoleLabel }}</a>
          <span class="console__meta"><span class="console__league"><span data-c="league">{{ $c0['league'] }}</span> · </span><span data-c="kick">{{ $c0['kick'] }}</span></span>
        </div>

        <div class="console__body" data-swap aria-live="polite">
          <div class="fixture">
            <div class="team"><span class="crest" data-c="hcrest" style="--c:{{ $c0['homeColour'] }}">{{ $c0['homeCrest'] }}</span><span class="team__name" data-c="home">{{ $c0['home'] }}</span></div>
            <span class="fixture__vs">vs</span>
            <div class="team team--away"><span class="crest" data-c="acrest" style="--c:{{ $c0['awayColour'] }}">{{ $c0['awayCrest'] }}</span><span class="team__name" data-c="away">{{ $c0['away'] }}</span></div>
          </div>

          <div class="prob">
            <div class="prob__labels">
              <span>Model read <b data-c="market">{{ $c0['market'] }}</b></span>
              <span><b class="num" data-c="pct">{{ $c0['pct'] }}%</b></span>
            </div>
            <div class="prob__bar" aria-hidden="true">
              <i class="prob__seg prob__seg--h" data-pb="read" style="flex-basis:{{ $c0['pct'] }}%"></i>
              <i class="prob__seg prob__seg--d" data-pb="rest" style="flex-basis:{{ 100 - $c0['pct'] }}%"></i>
            </div>
          </div>

          <div class="xg">
            <div class="xg__head">
              <span class="cap">Projected goals (xG)</span>
              <span class="xg__legend"><span class="key key--h"></span><b class="num" data-c="hxg">{{ number_format($c0['xgHome'], 2) }}</b><span class="key key--a"></span><b class="num" data-c="axg">{{ number_format($c0['xgAway'], 2) }}</b></span>
            </div>
            @php $xgT = max($c0['xgHome'] + $c0['xgAway'], 0.01); @endphp
            <div class="split"><i data-sb="xgh" style="flex-basis:{{ $c0['xgHome'] / $xgT * 100 }}%"></i><i data-sb="xga" style="flex-basis:{{ $c0['xgAway'] / $xgT * 100 }}%"></i></div>
          </div>

          <div class="cstats">
            <div class="cform">
              <span class="pills" data-form="h" data-pills="{{ $c0['form'][0] }}"></span><span class="cap">Last 5</span><span class="pills" data-form="a" data-pills="{{ $c0['form'][1] }}"></span>
            </div>
          </div>
        </div>

        <div class="console__foot">
          <span>Verdict <b data-c="verdict">{{ $c0['verdict'] }}</b></span>
          <span>Odds <b class="num" data-c="odds">{{ $c0['odds'] }}</b></span>
        </div>
      </div>

      <div class="console__controls">
        <div class="console__dots" data-dots></div>
        <button class="console__pause" type="button" data-pause aria-pressed="false">
          <svg class="i-pause" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>
          <svg class="i-play" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5.5v13a1 1 0 0 0 1.5.86l11-6.5a1 1 0 0 0 0-1.72l-11-6.5A1 1 0 0 0 7 5.5z"/></svg>
          <span class="sr-only" data-pause-label>Pause rotation</span>
        </button>
      </div>
    </div>
    @endif
  </div>
</section>

<!-- ============ LEAGUES MARQUEE ============ -->
@if ($leagues->isNotEmpty())
<section class="leagues" aria-label="Competitions covered">
  <p class="leagues__cap">Analysis across {{ $leagues->count() }} competitions</p>
  <div class="leagues__viewport">
    <div class="leagues__track">
      <ul class="leagues__list">
        @foreach ($leagues as $l)<li>{{ $l }}</li>@endforeach
      </ul>
      <ul class="leagues__list" aria-hidden="true">
        @foreach ($leagues as $l)<li>{{ $l }}</li>@endforeach
      </ul>
    </div>
  </div>
</section>
@endif

<!-- ============ METHOD ============ -->
<section class="section" id="method" data-track-section="method">
  <div class="container">
    <header class="section-head" data-reveal>
      <p class="kicker">Method</p>
      <h2 class="h2">Every match runs through the same four-part framework.</h2>
      <p class="lede">No gut calls, no hot streaks. The same inputs, weighed the same way, for every fixture we publish.</p>
    </header>

    <div class="steps" data-stagger>
      <article class="step" data-spot>
        <span class="step__num num">01</span>
        <span class="step__icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path pathLength="1" d="M3 3v18h18"/><path pathLength="1" d="M8 16v-4M12 16V8M16 16v-6M20 16V5"/></svg></span>
        <h3>Projected goals (xG)</h3>
        <p>Models trained on many seasons of results across 20+ leagues project expected goals for both sides.</p>
      </article>
      <article class="step" data-spot>
        <span class="step__num num">02</span>
        <span class="step__icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path pathLength="1" d="M3 12h4l3-8 4 16 3-8h4"/></svg></span>
        <h3>Recent form</h3>
        <p>Last-five results for both teams, so you can see how each side is actually playing right now.</p>
      </article>
      <article class="step" data-spot>
        <span class="step__num num">03</span>
        <span class="step__icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path pathLength="1" d="M4 8h14l-4-4M20 16H6l4 4"/></svg></span>
        <h3>Head-to-head</h3>
        <p>Where two sides show a consistent pattern across past meetings, we flag it — and say so when the sample is small.</p>
      </article>
      <article class="step" data-spot>
        <span class="step__num num">04</span>
        <span class="step__icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path pathLength="1" d="M8 3h8l4 4v14H4V3h4"/><path pathLength="1" d="M8 12h8M8 16h5"/></svg></span>
        <h3>Calibrated probability</h3>
        <p>Every read carries a probability checked against how often similar reads came true. Only clear reads get published.</p>
      </article>
    </div>
  </div>
</section>

<!-- ============ BREAKDOWNS ============ -->
<section class="section section--soft" id="breakdowns" data-track-section="breakdowns">
  <div class="container">
    <header class="section-head section-head--split" data-reveal>
      <div>
        <p class="kicker">Latest analysis</p>
        <h2 class="h2">Recent match breakdowns</h2>
      </div>
      <a class="link-arrow" href="{{ route('analysis') }}">View all analysis
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
      </a>
    </header>

    @if ($previewPredictions->isEmpty())
      <p style="padding:2rem 0;color:var(--text-2)">No upcoming analysis available right now — check back after the next scan.</p>
    @else
      <div class="matches" data-stagger>
        @foreach ($previewPredictions as $p)
          <article class="match">
            <div class="match__top">
              <span class="chip">{{ $p->leagueLabel() }}</span>
              @if ($p->kickoff)
                <time class="num" datetime="{{ $p->kickoff->toAtomString() }}">{{ $p->localKickoff()->format('D j M · H:i T') }}</time>
              @else
                <time class="num">Date TBC</time>
              @endif
            </div>
            <div class="match__teams">
              <div class="match__team">
                <span class="crest" style="--c:#334155">{{ strtoupper(substr($p->home, 0, 3)) }}</span>{{ $p->home }}
                @if ($p->home_form)<span class="pills" data-pills="{{ implode('', $p->home_form) }}"></span>@endif
              </div>
              <div class="match__team">
                <span class="crest" style="--c:#475569">{{ strtoupper(substr($p->away, 0, 3)) }}</span>{{ $p->away }}
                @if ($p->away_form)<span class="pills" data-pills="{{ implode('', $p->away_form) }}"></span>@endif
              </div>
            </div>
            <p class="match__text">Top market from our calibrated scan: <strong>{{ $p->market }}</strong> at {{ number_format($p->predicted_probability * 100, 1) }}%.</p>
            <a class="match__link" href="{{ $p->fixtureUrl() ?? route('analysis') }}">Read analysis <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
          </article>
        @endforeach
      </div>
    @endif
  </div>
</section>

<!-- ============ RECORD (dark) ============ -->
<section class="section record" id="record" data-track-section="record">
  <div class="record__bg" aria-hidden="true"></div>
  <div class="container record__inner">
    <div class="record__copy" data-reveal>
      <p class="kicker kicker--light">Transparency</p>
      <h2 class="h2">Every call stays on the record.</h2>
      <p class="lede">We track our analysis openly. No guarantees, no deleted misses, no hype — football is unpredictable, and the ledger reflects that.</p>
      <ul class="checks">
        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5L20 7"/></svg>Every analysed match is published with its outcome</li>
        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5L20 7"/></svg>Unexpected results are never removed</li>
        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5L20 7"/></svg>Educational analysis — not betting advice</li>
      </ul>
      <a class="btn btn--light" href="{{ route('results') }}">View the full ledger
        <svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
      </a>
    </div>

    <div class="panel" data-reveal style="--i:1">
      <div class="panel__head"><span>Analysis performance</span><span class="num">All-time</span></div>

      <div class="panel__main">
        @php
          $aligned = $stats['hits'];
          $analysed = $stats['n_settled'];
          $unexpected = $analysed - $aligned;
          $pct = $stats['hit_rate'];
          $missPct = $analysed > 0 ? 100 - $pct : 0;
        @endphp
        <div class="ring" role="img" aria-label="{{ $pct }} percent aligned: {{ $aligned }} of {{ $analysed }} matches">
          <svg viewBox="0 0 120 120" aria-hidden="true">
            <circle class="ring__track" cx="60" cy="60" r="52"/>
            <circle class="ring__value" cx="60" cy="60" r="52" pathLength="100" style="--off:{{ 100 - $pct }}"/>
          </svg>
          <div class="ring__label"><span class="num ring__pct"><span data-count="{{ $pct }}">{{ $pct }}</span>%</span><span>aligned</span></div>
        </div>

        <dl class="tallies">
          <div class="tally"><dt>Graded</dt><dd class="num" data-count="{{ $analysed }}">{{ $analysed }}</dd><span class="tally__bar"><i style="width:100%"></i></span></div>
          <div class="tally tally--pos"><dt>Aligned</dt><dd class="num" data-count="{{ $aligned }}">{{ $aligned }}</dd><span class="tally__bar"><i style="width:{{ $pct }}%"></i></span></div>
          <div class="tally tally--miss"><dt>Unexpected</dt><dd class="num" data-count="{{ $unexpected }}">{{ $unexpected }}</dd><span class="tally__bar"><i style="width:{{ $missPct }}%"></i></span></div>
        </dl>
      </div>

      @if ($strip->isNotEmpty())
        <div class="strip">
          <div class="strip__head"><span>Last {{ $strip->count() }} analyses</span><span class="strip__key"><i class="cell cell--pos"></i>Aligned <i class="cell cell--miss"></i>Unexpected</span></div>
          <ol class="strip__cells" aria-label="Last {{ $strip->count() }}: {{ $strip->where('hit', true)->count() }} aligned, {{ $strip->where('hit', false)->count() }} unexpected">
            @foreach ($strip as $i => $s)
              <li class="cell {{ $s->hit ? 'cell--pos' : 'cell--miss' }}" style="--i:{{ $i }}"></li>
            @endforeach
          </ol>
        </div>
      @endif

      @if ($ledger->isNotEmpty())
        <ul class="ledger">
          @foreach ($ledger as $i => $l)
            <li style="--i:{{ $i }}">
              <span class="ledger__match"><time class="num">{{ $l->localKickoff()?->format('j M') ?? '—' }}</time>{{ $l->home }} <b class="num">{{ $l->home_goals }}–{{ $l->away_goals }}</b> {{ $l->away }}</span>
              <span class="badge {{ $l->hit ? 'badge--pos' : 'badge--miss' }}">{{ $l->hit ? 'Aligned' : 'Unexpected' }}</span>
            </li>
          @endforeach
        </ul>
      @endif
    </div>
  </div>
</section>

<!-- ============ CONNECT ============ -->
<section class="section" id="connect" data-track-section="connect">
  <div class="container">
    <header class="section-head section-head--center" data-reveal>
      <p class="kicker">Stay updated</p>
      <h2 class="h2">Analysis where you already are.</h2>
    </header>

    <div class="connect" data-stagger>
      <article class="connect-card" data-spot>
        <span class="connect-card__icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="6" y="2.5" width="12" height="19" rx="2.5"/><path d="M11 18.5h2"/></svg></span>
        <h3>The LSA mobile app</h3>
        <p>Match analysis, results and kickoff alerts in your pocket. <strong>Coming soon</strong> — join the Telegram channel to hear first when it launches.</p>
        <ul class="minilist">
          <li>Kickoff and result notifications</li>
          <li>Offline access to saved analysis</li>
          <li>Follow the leagues you care about</li>
        </ul>
      </article>

      <article class="connect-card" data-spot>
        <span class="connect-card__icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.5 3.5 10.5 14.5"/><path d="M21.5 3.5 14.5 21l-4-6.5-7-4z"/></svg></span>
        <h3>The Telegram channel</h3>
        <p>Previews before kickoff, results after full time, and discussion with other followers.</p>
        <a class="btn btn--primary btn--block" href="{{ config('lsa.telegram_url') }}" rel="noopener" target="_blank">Join on Telegram
          <svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
        <dl class="facts">
          <div><dt>Previews</dt><dd>Daily</dd></div>
          <div><dt>Results</dt><dd>After FT</dd></div>
          <div><dt>Cost</dt><dd>Free</dd></div>
        </dl>
      </article>
    </div>
  </div>
</section>

<!-- ============ CTA ============ -->
<section class="cta">
  <div class="container cta__inner" data-reveal>
    <h2 class="cta__title">Read the analysis.<br><span class="muted">Then check the record.</span></h2>
    <div class="cta__actions">
      <a class="btn btn--primary" href="{{ route('analysis') }}">Today's analysis
        <svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
      </a>
      <a class="btn btn--ghost" href="{{ route('results') }}">Results ledger</a>
    </div>
  </div>
</section>

@include('partials.offers')

@endsection
