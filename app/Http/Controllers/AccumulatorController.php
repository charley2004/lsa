<?php

namespace App\Http\Controllers;

use App\Models\AccumulatorBundle;
use App\Models\BookmakerOffer;

class AccumulatorController extends Controller
{
    public function index()
    {
        $bundles = AccumulatorBundle::with('legs')
            ->upcoming()
            ->get()
            ->keyBy('type');

        return view('pages.combos', [
            'banker2' => $bundles->get(AccumulatorBundle::TYPE_BANKER_2),
            'odds10' => $bundles->get(AccumulatorBundle::TYPE_ODDS_10),
            'weekendPicks' => $bundles->get(AccumulatorBundle::TYPE_WEEKEND_PICKS),
            // Every active bookmaker deal, not just Paripesa/BC Games by name —
            // add a new one in /admin and it shows up here automatically, no
            // code change needed.
            'stakeOffers' => BookmakerOffer::active()->get(),
        ]);
    }
}
