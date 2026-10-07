<?php

namespace App\Http\Controllers;

use App\Models\Prediction;

class HomeController extends Controller
{
    public function index()
    {
        $track = Prediction::trackRecord();
        $totalLogged = Prediction::count();

        $stats = [
            'matches_analyzed' => $totalLogged > 0 ? $totalLogged : '500+',
            'hit_rate' => $track['n_settled'] > 0 ? round($track['hit_rate'] * 100) : 0,
            'n_settled' => $track['n_settled'],
            'hits' => $track['hits'] ?? 0,
        ];

        $previewPredictions = Prediction::upcoming()->limit(3)->get();

        // Win/miss strip: last 12 settled picks, oldest-to-newest left-to-right.
        $strip = Prediction::settled()->orderByDesc('kickoff')->limit(12)->get(['hit'])->reverse()->values();

        // Ledger: 3 most recent settled picks, newest first.
        $ledger = Prediction::settled()->orderByDesc('kickoff')->limit(3)->get();

        return view('pages.home', compact('stats', 'previewPredictions', 'strip', 'ledger'));
    }
}
