<?php

namespace App\Http\Controllers;

use App\Models\Prediction;

class AnalysisController extends Controller
{
    public function index()
    {
        $predictions = Prediction::upcoming()->get();

        // Grouped by kickoff day so a long slate renders as same-day sections
        // with day tabs, instead of one undifferentiated scroll of cards.
        $byDay = $predictions->groupBy(fn ($p) => $p->kickoff?->format('Y-m-d') ?? 'tbc');

        return view('pages.analysis', compact('predictions', 'byDay'));
    }
}
