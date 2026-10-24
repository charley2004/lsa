<?php

namespace App\Services;

use App\Models\AccumulatorBundle;
use App\Models\Prediction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

// Rebuilds today's Banker 2 / 10-Odds Combo / Top Weekend Picks from whatever
// confident_pick predictions currently have real odds — called at the end of
// every sync from the algorithm (see PredictionSyncController@store), so the
// bundles never need a separate manual trigger. Always a full
// delete-and-recreate per (type, date), never an incremental patch: simpler
// to reason about, and cheap since there are only ever a handful of legs.
//
// Deliberately conservative throughout: every selection rule below exists to
// avoid publishing a bundle that LOOKS complete but is actually padded with a
// weak leg just to hit a count. A bundle that doesn't qualify today simply
// doesn't get published today (and any stale row for it is removed) — see
// the "set it and forget it" principle this whole pipeline was built on.
class BundleBuilder
{
    private const NEAR_TERM_HOURS = 48;

    private const ODDS_10_TARGET = 10.0;

    private const ODDS_10_BAND_LOW = 8.0;

    private const ODDS_10_BAND_HIGH = 13.0;

    private const ODDS_10_MIN_LEGS = 3;

    private const ODDS_10_MAX_LEGS = 6;

    private const ODDS_10_POOL_CAP = 12; // bounds the combination search

    public function rebuild(): void
    {
        $today = Carbon::today();

        $this->buildBanker2($today);
        $this->buildOdds10($today);
        $this->buildWeekendPicks($today);
    }

    private function nearTermPool(): Collection
    {
        return Prediction::query()
            ->open()
            ->where('verdict', 'confident_pick')
            ->whereNotNull('odds')
            ->whereNotNull('kickoff')
            ->where('kickoff', '>=', now())
            ->where('kickoff', '<=', now()->addHours(self::NEAR_TERM_HOURS))
            ->get()
            // One leg per fixture — an accumulator leaning on two markets
            // from the same match isn't two independent reads, it's one
            // match's outcome counted twice.
            ->unique(fn (Prediction $p) => $p->home.'|'.$p->away)
            ->values();
    }

    private function buildBanker2(Carbon $today): void
    {
        $picks = $this->nearTermPool()
            ->sortByDesc(fn (Prediction $p) => [$p->predicted_probability, $p->skill])
            ->values();

        $this->replaceBundle(AccumulatorBundle::TYPE_BANKER_2, $today, $picks->count() >= 2
            ? $picks->take(2)
            : null);
    }

    private function buildOdds10(Carbon $today): void
    {
        $pool = $this->nearTermPool()
            ->sortByDesc('predicted_probability')
            ->take(self::ODDS_10_POOL_CAP)
            ->values();

        $best = $this->bestOdds10Combo($pool);

        $this->replaceBundle(AccumulatorBundle::TYPE_ODDS_10, $today, $best);
    }

    /**
     * Searches combination sizes 3..6, smallest first, and within a size
     * keeps whichever combo lands closest to the 10.0 target. Smallest-first
     * means a clean 3-leg combo at 9.8 wins over a messier 6-leg combo at
     * 10.1 — fewer legs is a real tie-break, not just a search shortcut,
     * since every extra leg is another independent way for the accumulator
     * to fail.
     */
    private function bestOdds10Combo(Collection $pool): ?Collection
    {
        for ($size = self::ODDS_10_MIN_LEGS; $size <= min(self::ODDS_10_MAX_LEGS, $pool->count()); $size++) {
            $best = null;
            $bestDistance = null;

            foreach ($this->combinations($pool->all(), $size) as $combo) {
                $combinedOdds = array_product(array_map(fn (Prediction $p) => $p->odds, $combo));

                if ($combinedOdds < self::ODDS_10_BAND_LOW || $combinedOdds > self::ODDS_10_BAND_HIGH) {
                    continue;
                }

                $distance = abs($combinedOdds - self::ODDS_10_TARGET);

                if ($best === null || $distance < $bestDistance) {
                    $best = $combo;
                    $bestDistance = $distance;
                }
            }

            if ($best !== null) {
                return collect($best);
            }
        }

        return null;
    }

    /** @return iterable<array<Prediction>> */
    private function combinations(array $items, int $size): iterable
    {
        $n = count($items);
        if ($size > $n) {
            return;
        }

        $indices = range(0, $size - 1);

        while (true) {
            yield array_map(fn ($i) => $items[$i], $indices);

            $i = $size - 1;
            while ($i >= 0 && $indices[$i] === $i + $n - $size) {
                $i--;
            }
            if ($i < 0) {
                return;
            }
            $indices[$i]++;
            for ($j = $i + 1; $j < $size; $j++) {
                $indices[$j] = $indices[$j - 1] + 1;
            }
        }
    }

    private function buildWeekendPicks(Carbon $today): void
    {
        // Only a Fri/Sat/Sun product — publishing a stale "weekend picks"
        // bundle on a Tuesday would be misleading, not just unhelpful. Not
        // bothering to clean up a past weekend's row here: the public query
        // only ever fetches today-or-future bundles (see
        // AccumulatorBundle::scopeUpcoming), so a stale past row just never
        // gets shown regardless of whether it's deleted.
        if (! $today->isFriday() && ! $today->isSaturday() && ! $today->isSunday()) {
            return;
        }

        $saturday = match (true) {
            $today->isSaturday() => $today->copy(),
            $today->isSunday() => $today->copy()->subDay(),
            default => $today->copy()->next(Carbon::SATURDAY), // Friday -> tomorrow
        };
        $sundayEnd = $saturday->copy()->addDay()->endOfDay();

        $picks = Prediction::query()
            ->open()
            ->whereIn('verdict', ['confident_pick', 'lean'])
            ->whereNotNull('kickoff')
            ->whereBetween('kickoff', [$saturday->copy()->startOfDay(), $sundayEnd])
            ->get()
            ->unique(fn (Prediction $p) => $p->home.'|'.$p->away)
            ->sortByDesc(fn (Prediction $p) => [$p->verdict === 'confident_pick' ? 1 : 0, $p->skill])
            ->take(5)
            ->values();

        $this->replaceBundle(AccumulatorBundle::TYPE_WEEKEND_PICKS, $saturday, $picks->isNotEmpty() ? $picks : null);
    }

    private function replaceBundle(string $type, Carbon $date, ?Collection $legs): void
    {
        $dateStr = $date->toDateString();

        AccumulatorBundle::where('type', $type)->whereDate('date', $dateStr)->delete();

        if ($legs === null || $legs->isEmpty()) {
            return;
        }

        $combinedOdds = $type === AccumulatorBundle::TYPE_WEEKEND_PICKS
            ? null
            : round($legs->reduce(fn ($carry, Prediction $p) => $carry * $p->odds, 1.0), 2);

        $bundle = AccumulatorBundle::create([
            'type' => $type,
            'date' => $dateStr,
            'combined_odds' => $combinedOdds,
        ]);

        foreach ($legs->values() as $position => $prediction) {
            $bundle->legs()->attach($prediction->id, ['position' => $position]);
        }
    }
}
