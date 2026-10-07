@extends('layouts.app')

@section('title', 'About LSA | LEGIT SOURCE ANALYSIS')
@section('description', "Learn about LSA's analytical approach to football match analysis and our commitment to transparent, data-driven insights.")

@section('content')

    <!-- Page header -->
    <section class="page-head">
        <div class="hero__bg" aria-hidden="true"><div class="hero__grid"></div><div class="hero__glow"></div></div>
        <div class="container">
            <div class="page-head__inner">
                <p class="kicker" data-hero style="--d:0ms">About LSA</p>
                <h1 class="page-head__title" data-split>Analysis built on evidence, <span class="muted">not certainty.</span></h1>
                <p class="page-head__lede" data-hero style="--d:450ms">LEGIT SOURCE ANALYSIS provides transparent, research-based football match analysis through data, statistics and tactical insight.</p>
            </div>
        </div>
    </section>

    <!-- What is LSA -->
    <section class="section">
        <div class="container split-2">
            <div data-reveal>
                <p class="kicker">Our purpose</p>
                <h2 class="h2">What is LSA?</h2>
                <p class="lede">A football analysis platform dedicated to transparent, research-based insight across major leagues and competitions.</p>
                <p class="lede" style="margin-top:12px">We present factual information, statistical data and analytical perspective to help you understand what shapes a match — an educational resource for the analytical side of football.</p>

                <div class="values">
                    <div class="value">
                        <span class="value__icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18"/><path d="M8 16v-4M12 16V8M16 16v-6M20 16V5"/></svg></span>
                        <div><h3>Data-driven approach</h3><p>Every analysis is backed by statistics and historical data.</p></div>
                    </div>
                    <div class="value">
                        <span class="value__icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg></span>
                        <div><h3>Transparent methodology</h3><p>Clear about our process — and its limitations.</p></div>
                    </div>
                    <div class="value">
                        <span class="value__icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2z"/><path d="M4 21V5"/></svg></span>
                        <div><h3>Educational focus</h3><p>Teaching football analysis, not making promises.</p></div>
                    </div>
                </div>
            </div>

            <dl class="tiles" data-reveal style="--i:1">
                <div class="tile"><dt>Matches analysed</dt><dd data-count="{{ (int) $stats['matches_analyzed'] }}">{{ $stats['matches_analyzed'] }}</dd></div>
                <div class="tile"><dt>Leagues covered</dt><dd><span data-count="15">15</span>+</dd></div>
                <div class="tile"><dt>Alignment rate</dt><dd><span data-count="{{ $stats['hit_rate'] }}">{{ $stats['hit_rate'] }}</span>%</dd></div>
                <div class="tile"><dt>Analysis updates</dt><dd>Daily</dd></div>
            </dl>
        </div>
    </section>

    <!-- Approach -->
    <section class="section section--soft">
        <div class="container">
            <header class="section-head" data-reveal>
                <p class="kicker">Methodology</p>
                <h2 class="h2">Six lenses on every fixture.</h2>
                <p class="lede">Multiple data points and evaluation angles — so no single number decides the read.</p>
            </header>

            <div class="steps steps--3" data-stagger>
                @foreach ([
                    ['Form analysis', 'Performance over the last 5–10 matches: results, scoring patterns, defensive stability and momentum.', '<path pathLength="1" d="M3 12h4l3-8 4 16 3-8h4"/>'],
                    ['Statistical review', 'Goals for and against, possession, shots on target, expected goals (xG), and home and away records.', '<path pathLength="1" d="M3 3v18h18"/><path pathLength="1" d="M8 16v-4M12 16V8M16 16v-6M20 16V5"/>'],
                    ['Head-to-head history', 'Previous meetings often reveal patterns and tendencies, along with notable tactical factors.', '<path pathLength="1" d="M4 8h14l-4-4M20 16H6l4 4"/>'],
                    ['Team news & context', 'Injuries, suspensions, tactical changes, managerial situations and motivation.', '<path pathLength="1" d="M8 3h8l4 4v14H4V3h4"/><path pathLength="1" d="M8 12h8M8 16h5"/>'],
                    ['Tactical considerations', 'How each side sets up, where their strengths lie, and the dynamics their styles create.', '<circle pathLength="1" cx="12" cy="12" r="9"/><path pathLength="1" d="M12 3v18M3 12h4M17 12h4"/>'],
                    ['Multiple perspectives', 'Rather than one conclusion, we set out several scenarios. Football is complex and unpredictable.', '<circle pathLength="1" cx="12" cy="12" r="3"/><path pathLength="1" d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/>'],
                ] as $i => [$title, $desc, $icon])
                    <article class="step" data-spot>
                        <span class="step__num num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="step__icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icon !!}</svg></span>
                        <h3>{{ $title }}</h3>
                        <p>{{ $desc }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Mission (dark) -->
    <section class="section record">
        <div class="record__bg" aria-hidden="true"></div>
        <div class="container mission">
            <div data-reveal>
                <p class="kicker kicker--light">Our mission</p>
                <p class="mission__statement">To raise the standard of football analysis with honest, transparent, data-driven insight — <span class="muted">that respects how unpredictable the sport really is.</span></p>
            </div>
            <div class="mission__values" data-stagger>
                @foreach ([
                    ['Transparency', 'Clear about methodology and limitations'],
                    ['Education', 'Teaching analysis, not making guarantees'],
                    ['Honesty', 'Tracking successful and unsuccessful reads alike'],
                    ['Responsibility', "Acknowledging football's unpredictability"],
                    ['Quality', 'Thorough research and well-reasoned analysis'],
                ] as $i => [$title, $desc])
                    <div class="mission__value">
                        <span class="num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        <h3>{{ $title }}</h3>
                        <p>{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- What we don't do -->
    <section class="section">
        <div class="container">
            <header class="section-head" data-reveal>
                <p class="kicker">Important</p>
                <h2 class="h2">What LSA does not provide.</h2>
                <p class="lede">Being clear about our limits is part of the method.</p>
            </header>
            <div class="nots" data-stagger>
                @foreach ([
                    ['No guaranteed outcomes', 'We do not claim to predict results with certainty. Football is unpredictable and unexpected outcomes happen regularly.'],
                    ['No fixed-match information', 'LSA has no inside information or guaranteed tips. Our analysis uses only publicly available information.'],
                    ['No betting instructions', 'We do not provide betting advice or recommendations. Content is for educational and analytical purposes only.'],
                    ['No financial promises', 'We make no claims about financial gain. Analysis is informational, not a route to profit.'],
                ] as [$title, $desc])
                    <article class="not">
                        <span class="not__icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></span>
                        <h3>{{ $title }}</h3>
                        <p>{{ $desc }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta">
        <div class="container cta__inner" data-reveal>
            <h2 class="cta__title">See the method in action.<br><span class="muted">Then check the record.</span></h2>
            <div class="cta__actions">
                <a class="btn btn--primary" href="{{ route('analysis') }}">View match analysis
                    <svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
                <a class="btn btn--ghost" href="{{ route('results') }}">View our results</a>
            </div>
        </div>
    </section>

@endsection
