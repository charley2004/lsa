<?php

namespace App\Http\Controllers;

use App\Models\Prediction;

class HomeController extends Controller
{
    public function index()
    {
        // Headline record = the current model's published picks (config lsa.current_model).
        $track = Prediction::trackRecord('current');

        // Leagues we actually have analysis for (not a hand-written list), so
        // the homepage can never advertise a competition the model doesn't cover.
        $leagues = Prediction::query()
            ->whereNotNull('league')
            ->distinct()
            ->pluck('league')
            ->map(fn ($code) => (new Prediction(['league' => $code]))->leagueLabel())
            ->unique()
            ->sort()
            ->values();

        $stats = [
            'fixtures_analysed' => Prediction::query()->distinct()->count(Prediction::slugsEnabled() ? 'slug' : 'id'),
            'competitions' => $leagues->count(),
            'hit_rate' => $track['n_settled'] > 0 ? round($track['hit_rate'] * 100) : 0,
            'n_settled' => $track['n_settled'],
            'hits' => $track['hits'] ?? 0,
        ];

        $previewPredictions = Prediction::upcoming()->limit(3)->get();

        // Hero console: real fixtures from the same data the Analysis page shows
        // (strongest upcoming reads that have projected goals). Falls back to the
        // most recent analysed fixtures when nothing is upcoming.
        $console = Prediction::upcoming()->whereNotNull('xg_home')->get()
            ->sortByDesc('predicted_probability')->take(5)->sortBy('kickoff')->values();
        $consoleLabel = 'Live analysis';
        if ($console->isEmpty()) {
            $console = Prediction::settled()->whereNotNull('xg_home')->orderByDesc('kickoff')->limit(5)->get();
            $consoleLabel = 'Recent analysis';
        }
        $consoleData = $console->map(fn (Prediction $p) => [
            'league' => $p->leagueLabel(),
            'kick' => $p->localKickoff()?->format('D j M, H:i T') ?? 'Date TBC',
            'home' => $p->home,
            'away' => $p->away,
            'homeCrest' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $p->home), 0, 3)),
            'awayCrest' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $p->away), 0, 3)),
            'homeColour' => 'hsl(' . (crc32($p->home) % 360) . ' 55% 32%)',
            'awayColour' => 'hsl(' . (crc32($p->away) % 360) . ' 55% 32%)',
            'market' => $p->market,
            'pct' => round($p->predicted_probability * 100, 1),
            'xgHome' => round((float) $p->xg_home, 2),
            'xgAway' => round((float) $p->xg_away, 2),
            'form' => [implode('', $p->home_form ?? []), implode('', $p->away_form ?? [])],
            'verdict' => $p->verdictLabel(),
            'odds' => $p->odds !== null ? number_format($p->odds, 2) : '—',
            'url' => $p->fixtureUrl() ?? route('analysis'),
        ])->values();

        // Win/miss strip: last 12 settled picks, oldest-to-newest left-to-right.
        $strip = Prediction::currentModel()->settled()->orderByDesc('kickoff')->limit(12)->get(['hit'])->reverse()->values();

        // Ledger: 3 most recent settled picks, newest first.
        $ledger = Prediction::currentModel()->settled()->orderByDesc('kickoff')->limit(3)->get();

        return view('pages.home', compact('stats', 'leagues', 'previewPredictions', 'consoleData', 'consoleLabel', 'strip', 'ledger'));
    }
}
