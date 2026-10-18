@php
    $offers = \App\Models\BookmakerOffer::active()->get();
@endphp

@if ($offers->isNotEmpty())
    <section class="offers" aria-label="Bookmaker offers" data-track-section="offers">
        <div class="container">
            <div class="offers__grid">
                @foreach ($offers as $offer)
                    <a class="offer-card" href="{{ route('offer.go', $offer->slug) }}" rel="sponsored noopener" target="_blank">
                        @if ($offer->logo_path)
                            <img class="offer-card__logo" src="{{ asset($offer->logo_path) }}" alt="{{ $offer->name }}" loading="lazy">
                        @else
                            <span class="offer-card__name">{{ $offer->name }}</span>
                        @endif
                        @if ($offer->offer_detail)
                            <span class="offer-card__detail">{{ $offer->offer_detail }}</span>
                        @endif
                        <span class="btn btn--primary btn--sm offer-card__cta">{{ $offer->cta_text }}</span>
                    </a>
                @endforeach
            </div>
            <p class="offers__disclosure">
                Advertisement. LSA may earn a commission if you sign up through a link above — this never affects
                our analysis or results ledger, which are published independently of any bookmaker relationship.
                18+. Gambling can be addictive — please play responsibly. Free support:
                <a href="https://www.begambleaware.org" rel="noopener" target="_blank">BeGambleAware.org</a>
                or your local responsible-gambling service.
            </p>
        </div>
    </section>
@endif
