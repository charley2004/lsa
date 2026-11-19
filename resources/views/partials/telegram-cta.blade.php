{{-- Inline Telegram invitation for content pages. Deliberately an in-flow
     callout (existing .callout styles), not a pop-up or interstitial — those
     hurt both mobile SEO and the reading experience. --}}
<div class="callout callout--info tg-cta" data-track-section="telegram-cta-{{ $placement ?? 'page' }}">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21.05 3.56 17.9 19.33c-.24 1.07-.87 1.33-1.76.83l-4.86-3.58-2.35 2.26c-.26.26-.48.48-.98.48l.35-4.94L17.3 6.1c.38-.34-.08-.53-.59-.19L8.3 11.14l-4.87-1.52c-1.06-.33-1.08-1.06.22-1.57L19.7 2.26c.88-.33 1.65.2 1.35 1.3z"/></svg>
    <div>
        <p><strong>{{ $headline ?? "Get each day's analysis on Telegram." }}</strong> New fixtures, combos and results are posted to the LSA community as soon as each scan finishes.</p>
        <p><a href="{{ config('lsa.telegram_url') }}" rel="noopener" target="_blank">Join LSA on Telegram</a></p>
    </div>
</div>
