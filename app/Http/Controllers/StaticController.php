<?php

namespace App\Http\Controllers;

use App\Models\Prediction;

class StaticController extends Controller
{
    public function about()
    {
        $track = Prediction::trackRecord();
        $totalLogged = Prediction::count();

        $stats = [
            'matches_analyzed' => $totalLogged > 0 ? $totalLogged : '500+',
            'hit_rate' => $track['n_settled'] > 0 ? round($track['hit_rate'] * 100) : 0,
        ];

        return view('pages.about', compact('stats'));
    }

    public function disclaimer()
    {
        return view('pages.disclaimer');
    }

    public function privacy()
    {
        return view('pages.privacy');
    }
}
