@extends('layouts.app')

@section('title', 'Disclaimer | LEGIT SOURCE ANALYSIS')
@section('description', 'Important disclaimer and terms for LSA football match analysis content.')

@php
    $clauses = [
        'general'       => 'General disclaimer',
        'no-guarantees' => 'No guaranteed outcomes',
        'educational'   => 'Educational purpose only',
        'not-betting'   => 'Not betting or gambling services',
        'liability'     => 'Limitation of liability',
        'responsibility'=> 'User responsibility',
        'age'           => 'Age restriction',
        'accuracy'      => 'Accuracy and completeness',
        'no-insider'    => 'No insider information',
        'responsible'   => 'Responsible behaviour',
        'final'         => 'Final notice',
    ];
    $n = fn ($key) => str_pad(array_search($key, array_keys($clauses)) + 1, 2, '0', STR_PAD_LEFT);
@endphp

@section('content')

    <div class="read-progress" data-read-progress aria-hidden="true"></div>

    <!-- Page header -->
    <section class="page-head">
        <div class="hero__bg" aria-hidden="true"><div class="hero__grid"></div><div class="hero__glow"></div></div>
        <div class="container">
            <div class="page-head__inner">
                <p class="kicker" data-hero style="--d:0ms">Legal information</p>
                <h1 class="page-head__title" data-split>Disclaimer</h1>
                <p class="page-head__lede" data-hero style="--d:300ms">Important information about LSA content and services. Please read it carefully before using the site.</p>
                <div class="page-head__meta" data-hero style="--d:420ms">
                    <span>Effective <b class="num">1 Jan 2026</b></span>
                    <span>Last updated <b class="num">13 Jan 2026</b></span>
                    <span>{{ count($clauses) }} sections · ~6 min read</span>
                </div>
            </div>
        </div>
    </section>

    <section class="section" style="padding-top:48px">
        <div class="container doc">

            <!-- Contents: collapsible on mobile, sticky with scroll-spy on desktop -->
            <details class="toc" data-toc open>
                <summary>Contents <span class="faq__icon" aria-hidden="true"></span></summary>
                <ol>
                    @foreach ($clauses as $id => $label)
                        <li><a href="#{{ $id }}"><span class="num">{{ $n($id) }}</span>{{ $label }}</a></li>
                    @endforeach
                </ol>
            </details>

            <div class="doc__body">

                <article class="clause" id="general" data-clause>
                    <span class="clause__num">{{ $n('general') }}</span>
                    <h2>General disclaimer</h2>
                    <div class="prose">
                        <p>LEGIT SOURCE ANALYSIS (LSA) provides football match analysis, statistics, and insights for educational and informational purposes only. By accessing and using this website, you acknowledge and agree to the terms outlined in this disclaimer.</p>
                        <div class="callout callout--info"><p><strong>Please read this disclaimer carefully before using our services.</strong></p></div>
                    </div>
                </article>

                <article class="clause" id="no-guarantees" data-clause>
                    <span class="clause__num">{{ $n('no-guarantees') }}</span>
                    <h2>No guaranteed outcomes</h2>
                    <div class="prose">
                        <p>LSA does not predict, guarantee, or promise any specific football match outcomes. All analysis is based on publicly available statistics, historical data, and current form, but football matches are inherently unpredictable and influenced by countless variables including but not limited to:</p>
                        <ul class="bullets">
                            <li>Individual player performance on match day</li>
                            <li>Referee decisions and match officiating</li>
                            <li>Weather and pitch conditions</li>
                            <li>Psychological factors and team motivation</li>
                            <li>Tactical adjustments during matches</li>
                            <li>Injuries occurring during matches</li>
                            <li>Random events and moments of individual brilliance</li>
                            <li>External factors beyond analytical scope</li>
                        </ul>
                        <div class="callout"><p><strong>No analysis, regardless of depth or quality, can account for all variables that influence match outcomes.</strong></p></div>
                    </div>
                </article>

                <article class="clause" id="educational" data-clause>
                    <span class="clause__num">{{ $n('educational') }}</span>
                    <h2>Educational purpose only</h2>
                    <div class="prose">
                        <p>All content provided by LSA is for educational and informational purposes. Our analysis is intended to help users understand football matches from an analytical perspective, including statistical trends, tactical considerations, and historical patterns.</p>
                        <p><strong>LSA content should NOT be interpreted as:</strong></p>
                        <ul class="bullets bullets--x">
                            <li>Betting advice or recommendations</li>
                            <li>Financial guidance or investment advice</li>
                            <li>Guaranteed predictions or outcomes</li>
                            <li>Professional sports consulting</li>
                            <li>Instructions or directives for any action</li>
                        </ul>
                    </div>
                </article>

                <article class="clause" id="not-betting" data-clause>
                    <span class="clause__num">{{ $n('not-betting') }}</span>
                    <h2>Not betting or gambling services</h2>
                    <div class="prose">
                        <div class="callout">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
                            <p><strong>LSA is NOT a betting service, gambling platform, or tipster service.</strong></p>
                        </div>
                        <p><strong>We do NOT:</strong></p>
                        <ul class="bullets bullets--x">
                            <li>Provide betting tips, picks, or recommendations</li>
                            <li>Offer guaranteed wins or fixed matches</li>
                            <li>Facilitate betting or gambling activities</li>
                            <li>Promote or encourage gambling behavior</li>
                            <li>Accept payment for betting-related services</li>
                            <li>Have any affiliation with betting companies or bookmakers</li>
                        </ul>
                        <p>Any use of our analysis in connection with betting or gambling activities is done entirely at your own risk and discretion. <strong>LSA bears no responsibility for any financial losses or consequences resulting from such use.</strong></p>
                    </div>
                </article>

                <article class="clause" id="liability" data-clause>
                    <span class="clause__num">{{ $n('liability') }}</span>
                    <h2>Limitation of liability</h2>
                    <div class="prose">
                        <p>To the fullest extent permitted by law, LSA and its operators, contributors, and affiliates shall not be liable for:</p>
                        <ul class="bullets">
                            <li>Any direct, indirect, incidental, or consequential damages</li>
                            <li>Financial losses resulting from use of our content</li>
                            <li>Decisions made based on our analysis</li>
                            <li>Any losses related to betting or gambling activities</li>
                            <li>Errors, omissions, or inaccuracies in our content</li>
                            <li>Any damages arising from use or inability to use our website</li>
                        </ul>
                        <div class="callout"><p><strong>You use this website and its content entirely at your own risk.</strong></p></div>
                    </div>
                </article>

                <article class="clause" id="responsibility" data-clause>
                    <span class="clause__num">{{ $n('responsibility') }}</span>
                    <h2>User responsibility</h2>
                    <div class="prose">
                        <p>By using LSA's services, you acknowledge and agree that:</p>
                        <ul class="bullets">
                            <li>You are solely responsible for your decisions and actions</li>
                            <li>You will not rely on our analysis as guaranteed or certain</li>
                            <li>You understand football outcomes are inherently unpredictable</li>
                            <li>You will use our content for educational purposes only</li>
                            <li>You are responsible for compliance with applicable laws in your jurisdiction</li>
                            <li>You will not hold LSA liable for any losses or consequences</li>
                        </ul>
                    </div>
                </article>

                <article class="clause" id="age" data-clause>
                    <span class="clause__num">{{ $n('age') }}</span>
                    <h2>Age restriction</h2>
                    <div class="prose">
                        <div class="age">
                            <span class="age__badge">18+</span>
                            <div>
                                <strong>LSA content is intended for users aged 18 and over only.</strong>
                                <p>By accessing this website, you confirm that you are at least 18 years of age (or the age of majority in your jurisdiction, whichever is higher). If you are under the required age, you must leave this website immediately.</p>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="clause" id="accuracy" data-clause>
                    <span class="clause__num">{{ $n('accuracy') }}</span>
                    <h2>Accuracy and completeness</h2>
                    <div class="prose">
                        <p>While LSA strives to provide accurate and up-to-date information, we make no representations or warranties regarding:</p>
                        <ul class="bullets">
                            <li>The accuracy, reliability, or completeness of our analysis</li>
                            <li>The currency or timeliness of information presented</li>
                            <li>The suitability of our content for any particular purpose</li>
                            <li>The absence of errors or omissions in our analysis</li>
                        </ul>
                        <p>Information on this website may contain inaccuracies or typographical errors and is subject to change without notice.</p>
                    </div>
                </article>

                <article class="clause" id="no-insider" data-clause>
                    <span class="clause__num">{{ $n('no-insider') }}</span>
                    <h2>No insider information</h2>
                    <div class="prose">
                        <div class="callout callout--info"><p><strong>LSA has NO access to insider information, fixed matches, or non-public data.</strong></p></div>
                        <p>All our analysis is based solely on publicly available information including match statistics, team news, historical data, and other information accessible to the general public.</p>
                        <p><strong>Any claims of insider information, guaranteed tips, or fixed matches should be considered fraudulent and reported to appropriate authorities.</strong></p>
                    </div>
                </article>

                <article class="clause" id="responsible" data-clause>
                    <span class="clause__num">{{ $n('responsible') }}</span>
                    <h2>Responsible behaviour</h2>
                    <div class="prose">
                        <p>If you choose to engage in any activities related to sports analysis, including any gambling or betting activities (which LSA does not promote or facilitate), you should:</p>
                        <ul class="bullets">
                            <li>Only use funds you can afford to lose</li>
                            <li>Set personal limits and stick to them</li>
                            <li>Seek help if you believe you have a gambling problem</li>
                            <li>Be aware of and comply with all applicable laws</li>
                            <li>Never chase losses or act impulsively</li>
                        </ul>
                        <div class="resources">
                            <h3>Resources for support</h3>
                            <ul class="bullets">
                                <li>National Council on Problem Gambling: <span class="num">1-800-522-4700</span></li>
                                <li>GamCare: <a href="https://www.gamcare.org.uk" target="_blank" rel="noopener">www.gamcare.org.uk</a></li>
                                <li>BeGambleAware: <a href="https://www.begambleaware.org" target="_blank" rel="noopener">www.begambleaware.org</a></li>
                            </ul>
                        </div>
                    </div>
                </article>

                <article class="clause" id="final" data-clause>
                    <div class="final">
                        <span class="clause__num" style="color:var(--dark-accent)">{{ $n('final') }}</span>
                        <h2>Final notice</h2>
                        <p class="lead">By using this website, you acknowledge that you have read, understood, and agree to be bound by this disclaimer.</p>
                        <div class="prose">
                            <p><strong>You acknowledge that:</strong></p>
                            <ul class="bullets">
                                <li>Football outcomes are inherently unpredictable</li>
                                <li>No analysis can guarantee specific results</li>
                                <li>You use this website entirely at your own risk</li>
                                <li>LSA bears no liability for your decisions or actions</li>
                                <li>All content is for educational purposes only</li>
                                <li>You are responsible for your own decisions and their consequences</li>
                            </ul>
                            <p><strong>If you do not agree with any part of this disclaimer, you must not use this website.</strong></p>
                        </div>
                    </div>
                </article>

            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta">
        <div class="container cta__inner" data-reveal>
            <h2 class="cta__title">Questions about this disclaimer?<br><span class="muted">We're happy to clarify.</span></h2>
            <div class="cta__actions">
                <a class="btn btn--primary" href="{{ route('contact') }}">Contact us
                    <svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
                <a class="btn btn--ghost" href="{{ route('home') }}">Back to home</a>
            </div>
        </div>
    </section>

@endsection
