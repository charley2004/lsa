<?php

namespace App\Http\Controllers;

use App\Models\Prediction;
use App\Services\FixtureNarrative;

class AnalysisController extends Controller
{
    public function index()
    {
        $predictions = Prediction::upcoming()->get();

        // Grouped by kickoff day so a long slate renders as same-day sections
        // with day tabs, instead of one undifferentiated scroll of cards.
        $byDay = $predictions->groupBy(fn ($p) => $p->localKickoff()?->format('Y-m-d') ?? 'tbc');

        // Shown as "Updated HH:MM" and used as the page's dateModified.
        $lastUpdated = $predictions->max('updated_at');

        return view('pages.analysis', compact('predictions', 'byDay', 'lastUpdated'));
    }

    /**
     * One permanent page per fixture. Every market row sharing the slug is
     * shown together; the page stays live after full time and gains the
     * result, so an indexed URL never disappears.
     */
    public function show(string $slug, FixtureNarrative $narrative)
    {
        abort_unless(Prediction::slugsEnabled(), 404);

        $reads = Prediction::where('slug', $slug)
            ->orderByDesc('predicted_probability')
            ->get();

        abort_if($reads->isEmpty(), 404);

        // The richest row carries the shared fixture data (form, xG, league).
        $fixture = $reads->sortByDesc(fn ($p) => (int) $p->hasAnalysisData())->first();

        $isSettled = $reads->contains(fn ($p) => $p->settled_at !== null);

        // Other fixtures on the same day — the main internal-link path between
        // match pages, and back to fresh content for anyone landing on an old one.
        $sameDay = collect();
        if ($fixture->kickoff) {
            $sameDay = Prediction::query()
                ->whereNotNull('slug')
                ->where('slug', '!=', $slug)
                ->whereBetween('kickoff', [
                    $fixture->localKickoff()->startOfDay()->utc(),
                    $fixture->localKickoff()->endOfDay()->utc(),
                ])
                ->orderBy('kickoff')
                ->get()
                ->unique('slug')
                ->take(10);
        }

        // Earlier meetings between the same two sides that we analysed.
        $previousMeetings = Prediction::query()
            ->whereNotNull('slug')
            ->where('slug', '!=', $slug)
            ->where(fn ($q) => $q
                ->where(fn ($m) => $m->where('home', $fixture->home)->where('away', $fixture->away))
                ->orWhere(fn ($m) => $m->where('home', $fixture->away)->where('away', $fixture->home)))
            ->orderByDesc('kickoff')
            ->get()
            ->unique('slug')
            ->take(5);

        return view('pages.analysis-show', [
            'slug' => $slug,
            'reads' => $reads,
            'fixture' => $fixture,
            'isSettled' => $isSettled,
            'summary' => $narrative->summary($fixture, $reads),
            'metaDescription' => $narrative->metaDescription($fixture, $reads),
            'indexable' => $reads->contains(fn ($p) => $p->hasAnalysisData()),
            'sameDay' => $sameDay,
            'previousMeetings' => $previousMeetings,
        ]);
    }
}
