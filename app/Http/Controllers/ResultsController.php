<?php

namespace App\Http\Controllers;

use App\Models\Prediction;

class ResultsController extends Controller
{
    public function index()
    {
        $track = Prediction::trackRecord();
        $settled = Prediction::settled()->orderByDesc('kickoff')->limit(50)->get();

        return view('pages.results', compact('track', 'settled'));
    }
}
