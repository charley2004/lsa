@extends('layouts.app')

@section('title', 'Contact Us | LEGIT SOURCE ANALYSIS')
@section('description', 'Contact LEGIT SOURCE ANALYSIS for inquiries, feedback, or questions about our football match analysis services.')

@section('content')

    <!-- Page header -->
    <section class="page-head">
        <div class="hero__bg" aria-hidden="true"><div class="hero__grid"></div><div class="hero__glow"></div></div>
        <div class="container">
            <div class="page-head__inner">
                <p class="kicker" data-hero style="--d:0ms">Get in touch</p>
                <h1 class="page-head__title" data-split>Contact the LSA team</h1>
                <p class="page-head__lede" data-hero style="--d:350ms">Questions about an analysis, feedback, or a technical issue — we read every message.</p>
                <div class="page-head__meta" data-hero style="--d:480ms">
                    <span><i class="dot-live" aria-hidden="true"></i>Replies within <b>24–48 hours</b></span>
                    <span>Mon–Fri, 9:00–18:00 GMT</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Form + info -->
    <section class="section section--soft">
        <div class="container contact">

            <div class="form-card" data-reveal>
                <h2>Send us a message</h2>
                <p>Fill in the form and we'll get back to you within 24–48 hours.</p>

                @if (session('status'))
                    <div class="alert alert--success" role="status">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5L20 7"/></svg>
                        <p>{{ session('status') }}</p>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert--error" role="alert">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
                        <p>Please fix the {{ $errors->count() === 1 ? 'field' : $errors->count() . ' fields' }} highlighted below.</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('contact.store') }}" id="contactForm" class="form" novalidate data-validate>
                    @csrf

                    <div class="form__row">
                        <div class="field @error('name') is-invalid @enderror">
                            <label for="name">Full name <span class="req">(required)</span></label>
                            <input class="input" type="text" id="name" name="name" required autocomplete="name"
                                   value="{{ old('name') }}" placeholder="Your name"
                                   aria-describedby="name-error" @error('name') aria-invalid="true" @enderror
                                   data-msg="Enter your name.">
                            <span class="field__error" id="name-error">{{ $errors->first('name') }}</span>
                        </div>

                        <div class="field @error('email') is-invalid @enderror">
                            <label for="email">Email address <span class="req">(required)</span></label>
                            <input class="input" type="email" id="email" name="email" required autocomplete="email" inputmode="email"
                                   value="{{ old('email') }}" placeholder="you@example.com"
                                   aria-describedby="email-error" @error('email') aria-invalid="true" @enderror
                                   data-msg="Enter a valid email address, like name@example.com.">
                            <span class="field__error" id="email-error">{{ $errors->first('email') }}</span>
                        </div>
                    </div>

                    <div class="field @error('subject') is-invalid @enderror">
                        <label for="subject">Subject <span class="req">(required)</span></label>
                        <select class="select" id="subject" name="subject" required
                                aria-describedby="subject-error" @error('subject') aria-invalid="true" @enderror
                                data-msg="Choose a subject.">
                            <option value="">Select a subject</option>
                            @foreach (['general' => 'General inquiry', 'analysis' => 'Analysis question', 'feedback' => 'Feedback', 'technical' => 'Technical support', 'partnership' => 'Partnership', 'other' => 'Other'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('subject') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field__error" id="subject-error">{{ $errors->first('subject') }}</span>
                    </div>

                    <div class="field @error('message') is-invalid @enderror">
                        <label for="message">Message <span class="req">(required)</span></label>
                        <textarea class="textarea" id="message" name="message" rows="6" required
                                  placeholder="How can we help?"
                                  aria-describedby="message-error" @error('message') aria-invalid="true" @enderror
                                  data-msg="Write your message.">{{ old('message') }}</textarea>
                        <span class="field__error" id="message-error">{{ $errors->first('message') }}</span>
                    </div>

                    <div class="field @error('consent') is-invalid @enderror">
                        <label class="check">
                            <input type="checkbox" id="consent" name="consent" required @checked(old('consent'))
                                   aria-describedby="consent-error" @error('consent') aria-invalid="true" @enderror
                                   data-msg="Please confirm you've read the disclaimer.">
                            <span>I have read the <a href="{{ route('disclaimer') }}">disclaimer</a> and understand LSA provides educational content only, with no guaranteed outcomes.</span>
                        </label>
                        <span class="field__error" id="consent-error">{{ $errors->first('consent') }}</span>
                    </div>

                    <button type="submit" class="btn btn--primary">Send message
                        <svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </button>
                </form>
            </div>

            <aside class="info" data-stagger>
                <div class="info-card">
                    <div class="info-card__head">
                        <span class="info-card__icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></span>
                        <h3>Email</h3>
                    </div>
                    <p><a href="mailto:info@legitsourceanalysis.com">info@legitsourceanalysis.com</a></p>
                    <span class="cap">We typically respond within 24–48 hours</span>
                </div>

                <div class="info-card">
                    <div class="info-card__head">
                        <span class="info-card__icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                        <h3>Business hours</h3>
                    </div>
                    <p class="num">Mon – Fri · 09:00 – 18:00 GMT</p>
                    <span class="cap">Closed on weekends and holidays</span>
                </div>

                <div class="info-card info-card--dark">
                    <div class="info-card__head">
                        <span class="info-card__icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg></span>
                        <h3>Please note</h3>
                    </div>
                    <p>We do not provide betting tips, guaranteed predictions or financial advice. Messages asking for these won't receive a reply.</p>
                </div>
            </aside>
        </div>
    </section>

    <!-- FAQ -->
    <section class="section">
        <div class="container">
            <header class="section-head section-head--center" data-reveal>
                <p class="kicker">FAQ</p>
                <h2 class="h2">Frequently asked questions</h2>
            </header>

            <div class="faq" data-stagger>
                @foreach ([
                    ['Do you provide betting tips?', 'No. LSA provides educational football analysis only. We do not offer betting tips, predictions, or gambling advice of any kind.'],
                    ['How often is analysis updated?', 'We update our analysis regularly based on upcoming fixtures and available data. Check the Analysis page for the most current content.'],
                    ['Can I request analysis for specific matches?', 'You can suggest matches through the contact form, but we cannot guarantee coverage of specific fixtures as our schedule depends on various factors.'],
                    ['Is your content free?', 'Yes. All LSA content is currently free. We do not offer paid subscriptions or premium analysis.'],
                    ['How can I report technical issues?', 'Use the contact form with "Technical support" as the subject, or email us directly with details of the issue.'],
                    ['Do you guarantee analysis accuracy?', 'No. Football is inherently unpredictable and we make no guarantees about outcomes. Our analysis is educational and based on available data.'],
                ] as $i => [$q, $a])
                    <details @if ($i === 0) open @endif>
                        <summary>{{ $q }}<span class="faq__icon" aria-hidden="true"></span></summary>
                        <p class="faq__answer">{{ $a }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

@endsection
