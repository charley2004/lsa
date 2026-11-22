<?php

namespace App\Http\Controllers;

use App\Models\Prediction;

class ResultsController extends Controller
{
    public function index()
    {
        // Headline: the current model's real, published record (config lsa.current_model).
        $track = Prediction::trackRecord('current');
        $settled = Prediction::currentModel()->settled()->orderByDesc('kickoff')->limit(50)->get();
        $perf = Prediction::performance('current');
        $openCurrent = Prediction::currentModel()->upcoming()->count();

        // Archive: the previous model's record, unchanged, shown collapsed.
        $prevTrack = Prediction::trackRecord('previous');
        $prevPerf = Prediction::performance('previous');
        $prevSettled = Prediction::previousModel()->settled()->orderByDesc('kickoff')->limit(50)->get();

        return view('pages.results', compact('track', 'settled', 'perf', 'openCurrent',
            'prevTrack', 'prevPerf', 'prevSettled'));
    }
}
