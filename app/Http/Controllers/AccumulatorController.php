<?php

namespace App\Http\Controllers;

use App\Models\AccumulatorBundle;
use App\Models\BookmakerOffer;

class AccumulatorController extends Controller
{
    public function index()
    {
        $upcoming = AccumulatorBundle::with('legs')->upcoming()->get();

        return view('pages.combos', [
            // today's slips first, then tomorrow's; 2 odds before 10 odds
            'slips' => $upcoming->whereIn('type', [AccumulatorBundle::TYPE_BANKER_2, AccumulatorBundle::TYPE_ODDS_10])
                ->filter(fn ($b) => AccumulatorBundle::meetsRule($b->type, $b->combined_odds, $b->legs->count()))
                ->sortBy(fn ($b) => [$b->date->toDateString(), $b->type === AccumulatorBundle::TYPE_BANKER_2 ? 0 : 1])
                ->values(),
            'weekendPicks' => $upcoming->firstWhere('type', AccumulatorBundle::TYPE_WEEKEND_PICKS),
            // Every active bookmaker deal, not just Paripesa/BC Games by name —
            // add a new one in /admin and it shows up here automatically, no
            // code change needed.
            'stakeOffers' => BookmakerOffer::active()->get(),
            // Combo record: every graded 2 Odds / 10 Odds slip, wins and losses.
            'history' => AccumulatorBundle::with('legs')->graded()->limit(30)->get(),
            'record' => collect([AccumulatorBundle::TYPE_BANKER_2, AccumulatorBundle::TYPE_ODDS_10])
                ->mapWithKeys(fn ($t) => [$t => [
                    'won' => AccumulatorBundle::where('type', $t)->where('status', AccumulatorBundle::STATUS_WON)->count(),
                    'graded' => AccumulatorBundle::graded()->where('type', $t)->count(),
                ]]),
        ]);
    }
}
