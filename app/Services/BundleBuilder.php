<?php

namespace App\Services;

use App\Models\AccumulatorBundle;
use App\Models\Prediction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds today's Banker 2 Odds and 10-Odds combos (plus the weekend list) from
 * the confident picks that have real bookmaker odds, then grades past combos.
 * Called at the end of every algorithm sync (PredictionSyncController@store).
 *
 * How a combo is chosen
 *   Each combo has an odds band and a leg range:
 *     Banker 2 Odds: combined odds 2.00-2.50, 2-5 legs (at least 2.00)
 *     10-Odds:       combined odds 10.00-15.00, 3-12 legs (at least 10.00)
 *   Among every combination of today's picks (one leg per fixture) whose odds
 *   land in the band, it picks the one with the HIGHEST chance of every leg
 *   landing, using the model's calibrated probabilities. That is an exact
 *   search (knapsack-style dynamic programming over log-odds), not "take the
 *   top N": the old top-two rule always produced about 1.40 odds, and the old
 *   12-pick / 6-leg cap meant short-priced slates could never reach 10.00.
 *
 * Locking and grading
 *   A combo is rebuilt freely until its first leg kicks off; from then on it
 *   is frozen (locked_at) so the published slip never changes mid-day. After
 *   every sync, pending combos are graded: lost as soon as any leg fails, won
 *   when every leg has landed, so the page can show which combos landed.
 *   Nothing is ever padded: if no combination fits the band, no combo that day.
 */
class BundleBuilder
{
    public const BANKER_2 = ['min_legs' => 2, 'max_legs' => 5, 'low' => 2.00, 'high' => 2.50, 'target' => 2.0];

    public const ODDS_10 = ['min_legs' => 3, 'max_legs' => 12, 'low' => 10.0, 'high' => 15.0, 'target' => 10.0];

    private const LOG_STEP = 0.005;   // log-odds resolution of the search (about 0.5% in odds)

    public function rebuild(): void
    {
        // The visitor's calendar day (Lagos), not the server's UTC day.
        $today = Carbon::today(Prediction::displayTimezone());

        $this->buildCombo(AccumulatorBundle::TYPE_BANKER_2, self::BANKER_2, $today);
        $this->buildCombo(AccumulatorBundle::TYPE_ODDS_10, self::ODDS_10, $today);
        $this->buildWeekendPicks($today);
        $this->gradePending();
    }

    /** Today's open, priced confident picks; the best market per fixture. */
    private function pool(Carbon $day): Collection
    {
        return Prediction::query()
            ->open()
            ->where('verdict', 'confident_pick')
            ->whereNotNull('odds')
            ->where('odds', '>', 1)
            ->whereNotNull('kickoff')
            ->where('kickoff', '>', now())
            ->where('kickoff', '<=', $day->copy()->endOfDay()->utc())
            ->get()
            // One leg per fixture: two markets from one match aren't two
            // independent reads. Keep that fixture's most likely market.
            ->sortByDesc('predicted_probability')
            ->unique(fn (Prediction $p) => $p->home . '|' . $p->away)
            ->values();
    }

    private function buildCombo(string $type, array $rule, Carbon $day): void
    {
        $existing = AccumulatorBundle::with('legs')->where('type', $type)->whereDate('date', $day->toDateString())->first();

        // Frozen once a leg has kicked off: the published slip never changes.
        if ($existing && ($existing->locked_at || $existing->legs->contains(fn ($p) => $p->kickoff && $p->kickoff->lte(now())))) {
            if (! $existing->locked_at) {
                $existing->update(['locked_at' => now()]);
            }
            return;
        }

        $legs = $this->bestCombo($this->pool($day), $rule);
        $this->replaceBundle($type, $day, $legs);
    }

    /**
     * Highest joint probability among combinations whose combined odds fall in
     * [low, high] with min..max legs. 0/1 knapsack over discretised log-odds:
     * state (legs, log-odds bucket) keeps the best sum of log-probabilities.
     * Candidates are then re-checked with exact odds; ties go to the combo
     * closer to the target odds, then to fewer legs.
     */
    public function bestCombo(Collection $pool, array $rule): ?Collection
    {
        $items = $pool->values()->all();
        if (count($items) < $rule['min_legs']) {
            return null;
        }
        $maxB = (int) ceil(log($rule['high']) / self::LOG_STEP);

        $dp = array_fill(0, $rule['max_legs'] + 1, []);
        $dp[0][0] = [0.0, []];
        foreach ($items as $i => $p) {
            $w = (int) round(log($p->odds) / self::LOG_STEP);
            $lp = log(max(min((float) $p->predicted_probability, 0.999), 0.001));
            for ($k = $rule['max_legs'] - 1; $k >= 0; $k--) {
                foreach ($dp[$k] as $b => [$score, $idx]) {
                    $nb = $b + $w;
                    if ($nb > $maxB) {
                        continue;
                    }
                    $ns = $score + $lp;
                    if (! isset($dp[$k + 1][$nb]) || $ns > $dp[$k + 1][$nb][0]) {
                        $dp[$k + 1][$nb] = [$ns, [...$idx, $i]];
                    }
                }
            }
        }

        $best = null;
        for ($k = $rule['min_legs']; $k <= $rule['max_legs']; $k++) {
            foreach ($dp[$k] as [$score, $idx]) {
                $odds = array_product(array_map(fn ($i) => $items[$i]->odds, $idx));
                if ($odds < $rule['low'] || $odds > $rule['high']) {
                    continue;
                }
                $cand = [$score, abs($odds - $rule['target']), $k, $idx];
                if ($best === null
                    || $cand[0] > $best[0] + 1e-9
                    || (abs($cand[0] - $best[0]) <= 1e-9 && ($cand[1] < $best[1] || ($cand[1] == $best[1] && $cand[2] < $best[2])))) {
                    $best = $cand;
                }
            }
        }

        return $best ? collect(array_map(fn ($i) => $items[$i], $best[3]))->sortBy('kickoff')->values() : null;
    }

    private function buildWeekendPicks(Carbon $today): void
    {
        // Only a Fri/Sat/Sun product. Past weekends' rows are never shown
        // (AccumulatorBundle::scopeUpcoming), so they're left as they are.
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
            ->whereBetween('kickoff', [$saturday->copy()->startOfDay()->utc(), $sundayEnd->copy()->utc()])
            ->get()
            ->unique(fn (Prediction $p) => $p->home . '|' . $p->away)
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
            'status' => AccumulatorBundle::STATUS_PENDING,
        ]);

        foreach ($legs->values() as $position => $prediction) {
            $bundle->legs()->attach($prediction->id, ['position' => $position]);
        }
    }

    /**
     * Lost as soon as any leg fails; won when every leg has landed. A leg
     * settled without a grade (postponed/void) drops out, as at a bookmaker.
     */
    public function gradePending(): void
    {
        AccumulatorBundle::with('legs')
            ->whereIn('type', [AccumulatorBundle::TYPE_BANKER_2, AccumulatorBundle::TYPE_ODDS_10])
            ->where('status', AccumulatorBundle::STATUS_PENDING)
            ->get()
            ->each(function (AccumulatorBundle $b) {
                $legs = $b->legs;
                if ($legs->isEmpty()) {
                    return;
                }
                $status = null;
                if ($legs->contains(fn ($p) => $p->settled_at && $p->hit === false)) {
                    $status = AccumulatorBundle::STATUS_LOST;
                } elseif ($legs->every(fn ($p) => $p->settled_at !== null)) {
                    $graded = $legs->filter(fn ($p) => $p->hit !== null);
                    $status = $graded->isEmpty() ? AccumulatorBundle::STATUS_VOID : AccumulatorBundle::STATUS_WON;
                }
                if ($status) {
                    $b->update(['status' => $status, 'settled_at' => now(), 'locked_at' => $b->locked_at ?? now()]);
                }
            });
    }
}
