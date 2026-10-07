@extends('layouts.app')

@section('title', 'Privacy Policy | LEGIT SOURCE ANALYSIS')
@section('description', 'How LSA collects, uses, and protects the information you share with us.')

@php
    $clauses = [
        'overview'    => 'Overview',
        'collect'     => 'What we collect',
        'use'         => 'How we use it',
        'cookies'     => 'Cookies',
        'third-party' => 'Third parties',
        'retention'   => 'Data retention',
        'rights'      => 'Your rights',
        'children'    => 'Children',
        'changes'     => 'Changes to this policy',
        'contact'     => 'Contact us',
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
                <h1 class="page-head__title" data-split>Privacy policy</h1>
                <p class="page-head__lede" data-hero style="--d:300ms">What we collect when you use this site, why, and how to ask us to delete it.</p>
                <div class="page-head__meta" data-hero style="--d:420ms">
                    <span>Effective <b class="num">4 Oct 2026</b></span>
                    <span>{{ count($clauses) }} sections · ~3 min read</span>
                </div>
            </div>
        </div>
    </section>

    <section class="section" style="padding-top:48px">
        <div class="container doc">

            <details class="toc" data-toc open>
                <summary>Contents <span class="faq__icon" aria-hidden="true"></span></summary>
                <ol>
                    @foreach ($clauses as $id => $label)
                        <li><a href="#{{ $id }}"><span class="num">{{ $n($id) }}</span>{{ $label }}</a></li>
                    @endforeach
                </ol>
            </details>

            <div class="doc__body">

                <article class="clause" id="overview" data-clause>
                    <span class="clause__num">{{ $n('overview') }}</span>
                    <h2>Overview</h2>
                    <div class="prose">
                        <p>LEGIT SOURCE ANALYSIS (LSA) respects your privacy. This page explains what information we collect when you use this website, why we collect it, and what control you have over it. We collect the minimum needed to run the site and respond to you — nothing is sold, and nothing is used for advertising.</p>
                    </div>
                </article>

                <article class="clause" id="collect" data-clause>
                    <span class="clause__num">{{ $n('collect') }}</span>
                    <h2>What we collect</h2>
                    <div class="prose">
                        <p>We collect information in two ways:</p>
                        <ul class="bullets">
                            <li><strong>Information you give us directly</strong> — if you use the <a href="{{ route('contact') }}">contact form</a>, we collect your name, email address, the subject you choose, and your message.</li>
                            <li><strong>Information collected automatically</strong> — standard web server logs (IP address, browser type, pages visited, timestamps) for security and to keep the site running reliably. We do not currently run any analytics or tracking service.</li>
                        </ul>
                    </div>
                </article>

                <article class="clause" id="use" data-clause>
                    <span class="clause__num">{{ $n('use') }}</span>
                    <h2>How we use it</h2>
                    <div class="prose">
                        <ul class="bullets">
                            <li>To reply to messages sent through the contact form.</li>
                            <li>To detect and prevent abuse of the contact form (automated rate limiting on submissions).</li>
                            <li>To operate, secure, and maintain the website.</li>
                        </ul>
                        <div class="callout callout--info"><p><strong>We do not sell, rent, or share your information with advertisers or data brokers.</strong></p></div>
                    </div>
                </article>

                <article class="clause" id="cookies" data-clause>
                    <span class="clause__num">{{ $n('cookies') }}</span>
                    <h2>Cookies</h2>
                    <div class="prose">
                        <p>This site uses a small number of strictly necessary cookies:</p>
                        <ul class="bullets">
                            <li>A session cookie, so the site works correctly as you navigate between pages.</li>
                            <li>A CSRF token cookie, which protects the contact form from cross-site forgery attacks.</li>
                        </ul>
                        <p>These cookies are required for the site to function and are not used for tracking or advertising. We don't use third-party advertising or analytics cookies.</p>
                    </div>
                </article>

                <article class="clause" id="third-party" data-clause>
                    <span class="clause__num">{{ $n('third-party') }}</span>
                    <h2>Third parties</h2>
                    <div class="prose">
                        <p>This site loads web fonts directly from Google Fonts (fonts.googleapis.com / fonts.gstatic.com). Loading a font involves a request to Google's servers, which may log standard request data (such as your IP address) under <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Google's own privacy policy</a>. We don't control or receive that data ourselves.</p>
                        <p>We do not use any advertising networks, third-party analytics, or social media tracking pixels.</p>
                    </div>
                </article>

                <article class="clause" id="retention" data-clause>
                    <span class="clause__num">{{ $n('retention') }}</span>
                    <h2>Data retention</h2>
                    <div class="prose">
                        <p>Contact form submissions are kept for as long as needed to respond to you and maintain a record of correspondence, after which they can be deleted on request (see "Your rights" below). Server logs are retained only as long as needed for security and site operation.</p>
                    </div>
                </article>

                <article class="clause" id="rights" data-clause>
                    <span class="clause__num">{{ $n('rights') }}</span>
                    <h2>Your rights</h2>
                    <div class="prose">
                        <p>You can ask us at any time to:</p>
                        <ul class="bullets">
                            <li>Tell you what information we hold about you.</li>
                            <li>Correct inaccurate information.</li>
                            <li>Delete your information from our records.</li>
                        </ul>
                        <p>To do any of this, email us at <a href="mailto:info@legitsourceanalysis.com">info@legitsourceanalysis.com</a> or use the <a href="{{ route('contact') }}">contact form</a>. We'll respond within 24–48 hours.</p>
                    </div>
                </article>

                <article class="clause" id="children" data-clause>
                    <span class="clause__num">{{ $n('children') }}</span>
                    <h2>Children</h2>
                    <div class="prose">
                        <p>This site is intended for users aged 18 and over (see our <a href="{{ route('disclaimer') }}">disclaimer</a>). We do not knowingly collect information from children.</p>
                    </div>
                </article>

                <article class="clause" id="changes" data-clause>
                    <span class="clause__num">{{ $n('changes') }}</span>
                    <h2>Changes to this policy</h2>
                    <div class="prose">
                        <p>If this policy changes, we'll update the effective date at the top of this page. Continued use of the site after a change means you accept the updated policy.</p>
                    </div>
                </article>

                <article class="clause" id="contact" data-clause>
                    <span class="clause__num">{{ $n('contact') }}</span>
                    <h2>Contact us</h2>
                    <div class="prose">
                        <p>Questions about this policy or your data? Email <a href="mailto:info@legitsourceanalysis.com">info@legitsourceanalysis.com</a> or use the <a href="{{ route('contact') }}">contact form</a>.</p>
                    </div>
                </article>

            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta">
        <div class="container cta__inner" data-reveal>
            <h2 class="cta__title">Questions about your data?<br><span class="muted">We're happy to help.</span></h2>
            <div class="cta__actions">
                <a class="btn btn--primary" href="{{ route('contact') }}">Contact us
                    <svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
                <a class="btn btn--ghost" href="{{ route('home') }}">Back to home</a>
            </div>
        </div>
    </section>

@endsection
