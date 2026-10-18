<?php

namespace App\Http\Controllers;

use App\Models\BookmakerOffer;
use Illuminate\Http\RedirectResponse;

class BookmakerOfferController extends Controller
{
    // Every affiliate click goes through here before reaching the real bookmaker
    // URL — the only way to know which page/placement actually drives signups,
    // which is the thing that matters once the business model is "a lot of
    // bookmaker deals," not just having the links live somewhere on the site.
    public function go(string $slug): RedirectResponse
    {
        $offer = BookmakerOffer::active()->where('slug', $slug)->firstOrFail();
        $offer->increment('click_count');

        return redirect()->away($offer->affiliate_url);
    }
}
