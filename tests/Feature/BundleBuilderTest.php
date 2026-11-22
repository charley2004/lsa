<?php

namespace Tests\Feature;

use App\Models\AccumulatorBundle;
use App\Models\Prediction;
use App\Services\BundleBuilder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BundleBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Pinned mid-morning so "+N hours" kickoffs stay inside today's (Lagos) window.
        Carbon::setTestNow(Carbon::parse('2026-10-24 08:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function pick(array $overrides = []): Prediction
    {
        return Prediction::factory()->create(array_merge([
            'verdict' => 'confident_pick',
            'kickoff' => now()->addHours(6),
            'odds' => 1.30,
            'predicted_probability' => 0.8,
        ], $overrides));
    }

    /** Three picks that make a valid Banker 2 Odds (1.26^3 = 2.00). */
    private function threePicks(array $kickoffs = [1, 2, 3]): array
    {
        return array_map(fn ($h) => $this->pick(['odds' => 1.26, 'kickoff' => now()->addHours($h)]), $kickoffs);
    }

    private function bundle(string $type): ?AccumulatorBundle
    {
        return AccumulatorBundle::with('legs')->where('type', $type)->first();
    }

    private function rebuild(): void
    {
        app(BundleBuilder::class)->rebuild();
    }

    /* ------------------------------------------------ Banker 2 Odds = top 3-4 most likely */

    public function test_banker_takes_the_three_most_likely_picks_when_they_reach_2_00(): void
    {
        $a = $this->pick(['predicted_probability' => 0.90, 'odds' => 1.30]);
        $b = $this->pick(['predicted_probability' => 0.88, 'odds' => 1.28]);
        $c = $this->pick(['predicted_probability' => 0.86, 'odds' => 1.25]);  // 1.30*1.28*1.25 = 2.08
        $this->pick(['predicted_probability' => 0.80, 'odds' => 1.60]);      // less likely: excluded
        $this->rebuild();

        $legs = $this->bundle(AccumulatorBundle::TYPE_BANKER_2)->legs->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$a->id, $b->id, $c->id], $legs);
    }

    public function test_banker_adds_the_fourth_most_likely_when_three_fall_short_of_2_00(): void
    {
        $a = $this->pick(['predicted_probability' => 0.92, 'odds' => 1.20]);
        $b = $this->pick(['predicted_probability' => 0.90, 'odds' => 1.22]);
        $c = $this->pick(['predicted_probability' => 0.89, 'odds' => 1.25]);  // 1.83: short of 2.00
        $d = $this->pick(['predicted_probability' => 0.87, 'odds' => 1.21]);  // 2.21 with the 4th
        $this->pick(['predicted_probability' => 0.60, 'odds' => 2.10]);
        $this->rebuild();

        $b2 = $this->bundle(AccumulatorBundle::TYPE_BANKER_2);
        $this->assertEqualsCanonicalizing([$a->id, $b->id, $c->id, $d->id], $b2->legs->pluck('id')->all());
        $this->assertEqualsWithDelta(1.20 * 1.22 * 1.25 * 1.21, $b2->combined_odds, 0.01);
    }

    public function test_banker_is_never_padded_beyond_four_legs(): void
    {
        foreach ([0.95, 0.94, 0.93, 0.92, 0.91] as $p) {
            $this->pick(['predicted_probability' => $p, 'odds' => 1.10]);  // 4 legs = 1.46
        }
        $this->rebuild();

        $b = $this->bundle(AccumulatorBundle::TYPE_BANKER_2);
        $this->assertCount(4, $b->legs);
        $this->assertEqualsWithDelta(1.4641, $b->combined_odds, 0.01);  // published honestly, not padded
    }

    public function test_banker_needs_at_least_three_picks(): void
    {
        $this->pick();
        $this->pick();
        $this->rebuild();
        $this->assertNull($this->bundle(AccumulatorBundle::TYPE_BANKER_2));
    }

    public function test_one_leg_per_fixture_keeping_its_most_likely_market(): void
    {
        $this->pick(['home' => 'Arsenal', 'away' => 'Chelsea', 'market' => 'Home win', 'predicted_probability' => 0.95]);
        $this->pick(['home' => 'Arsenal', 'away' => 'Chelsea', 'market' => 'Over 1.5 goals', 'predicted_probability' => 0.94]);
        $this->pick(['predicted_probability' => 0.80]);
        $this->pick(['predicted_probability' => 0.79]);
        $this->rebuild();

        $legs = $this->bundle(AccumulatorBundle::TYPE_BANKER_2)->legs;
        $this->assertCount(3, $legs->map(fn ($p) => $p->home . '|' . $p->away)->unique());
        $this->assertTrue($legs->contains('market', 'Home win'));
    }

    public function test_picks_without_odds_are_never_used(): void
    {
        foreach (range(1, 3) as $i) {
            $this->pick(['odds' => null]);
        }
        $this->rebuild();
        $this->assertNull($this->bundle(AccumulatorBundle::TYPE_BANKER_2));
    }

    /* ------------------------------------------------ 10 Odds */

    public function test_10_odds_is_built_from_a_short_priced_slate(): void
    {
        // 17 picks at 1.15-1.37 (like the real 22 Nov slate)
        foreach ([1.15, 1.16, 1.20, 1.20, 1.20, 1.22, 1.22, 1.23, 1.24, 1.25, 1.25, 1.26, 1.26, 1.27, 1.35, 1.37, 1.77] as $i => $o) {
            $this->pick(['odds' => $o, 'predicted_probability' => 0.88 - $i * 0.01]);
        }
        $this->rebuild();

        $b = $this->bundle(AccumulatorBundle::TYPE_ODDS_10);
        $this->assertNotNull($b, '10 Odds should be built');
        $this->assertGreaterThanOrEqual(10.0, $b->combined_odds);
        $this->assertLessThanOrEqual(15.0, $b->combined_odds);
        $this->assertLessThanOrEqual(12, $b->legs->count());
    }

    public function test_10_odds_not_built_when_slate_cannot_reach_the_band(): void
    {
        foreach ([1.2, 1.3, 1.1] as $o) {
            $this->pick(['odds' => $o]);
        }
        $this->rebuild();
        $this->assertNull($this->bundle(AccumulatorBundle::TYPE_ODDS_10));
    }

    /* ------------------------------------------------ locking and grading */

    public function test_combo_is_frozen_once_its_first_leg_kicks_off(): void
    {
        $this->threePicks([1, 4, 5]);
        $this->rebuild();
        $before = $this->bundle(AccumulatorBundle::TYPE_BANKER_2)->legs->pluck('id')->all();

        Carbon::setTestNow(now()->addHours(2));  // first leg is now in play
        $this->pick(['odds' => 1.30, 'predicted_probability' => 0.99, 'kickoff' => now()->addHours(3)]);
        $this->rebuild();

        $b = $this->bundle(AccumulatorBundle::TYPE_BANKER_2);
        $this->assertNotNull($b->locked_at);
        $this->assertSame($before, $b->legs->pluck('id')->all());
    }

    public function test_combo_is_graded_won_when_every_leg_lands(): void
    {
        $legs = $this->threePicks();
        $this->rebuild();

        Carbon::setTestNow(now()->addHours(6));
        foreach ($legs as $p) {
            $p->update(['settled_at' => now(), 'hit' => true, 'home_goals' => 2, 'away_goals' => 0]);
        }
        $this->rebuild();

        $this->assertSame(AccumulatorBundle::STATUS_WON, $this->bundle(AccumulatorBundle::TYPE_BANKER_2)->status);
    }

    public function test_combo_is_graded_lost_as_soon_as_one_leg_fails(): void
    {
        [$first] = $this->threePicks([1, 5, 6]);
        $this->rebuild();

        Carbon::setTestNow(now()->addHours(3));  // other legs not played yet
        $first->update(['settled_at' => now(), 'hit' => false, 'home_goals' => 0, 'away_goals' => 1]);
        $this->rebuild();

        $this->assertSame(AccumulatorBundle::STATUS_LOST, $this->bundle(AccumulatorBundle::TYPE_BANKER_2)->status);
    }

    public function test_rebuild_is_idempotent(): void
    {
        $this->threePicks();
        $this->rebuild();
        $this->rebuild();
        $this->assertSame(1, AccumulatorBundle::where('type', AccumulatorBundle::TYPE_BANKER_2)->count());
    }

    public function test_combos_page_shows_won_combo_in_the_record(): void
    {
        $legs = $this->threePicks();
        $this->rebuild();
        Carbon::setTestNow(now()->addHours(6));
        foreach ($legs as $p) {
            $p->update(['settled_at' => now(), 'hit' => true, 'home_goals' => 2, 'away_goals' => 0]);
        }
        $this->rebuild();

        $this->get(route('combos'))->assertOk()
            ->assertSee('Banker 2 Odds')
            ->assertSee('won 1 of 1')
            ->assertSee('Every combo, graded.')
            ->assertSee('3 of 3 landed');
    }

    /* ------------------------------------------------ legacy / invalid slips */

    public function test_old_invalid_slip_with_a_leg_in_play_is_replaced_by_a_valid_one(): void
    {
        // A 2-leg 1.44 "2 odds" built by the old top-two logic; its first leg already started.
        $started = $this->pick(['odds' => 1.20, 'kickoff' => now()->subMinutes(30)]);
        $later = $this->pick(['odds' => 1.20, 'kickoff' => now()->addHours(8)]);
        $old = AccumulatorBundle::create(['type' => AccumulatorBundle::TYPE_BANKER_2, 'date' => now('Africa/Lagos')->toDateString(), 'combined_odds' => 1.44, 'status' => 'pending']);
        $old->legs()->attach([$started->id => ['position' => 0], $later->id => ['position' => 1]]);

        $this->threePicks([2, 3, 4]);
        $this->rebuild();

        $b = $this->bundle(AccumulatorBundle::TYPE_BANKER_2);
        $this->assertGreaterThanOrEqual(3, $b->legs->count());
        $this->assertFalse($b->legs->contains('id', $started->id), 'must not include a leg already in play');
    }

    public function test_invalid_slips_never_count_in_the_record(): void
    {
        $a = $this->pick(['odds' => 1.20, 'kickoff' => now()->subHours(3), 'settled_at' => now(), 'hit' => true, 'home_goals' => 1, 'away_goals' => 0]);
        $old = AccumulatorBundle::create(['type' => AccumulatorBundle::TYPE_BANKER_2, 'date' => now()->subDay()->toDateString(), 'combined_odds' => 1.44, 'status' => 'pending']);
        $old->legs()->attach([$a->id => ['position' => 0]]);

        $this->rebuild();

        $this->assertSame('pending', $old->fresh()->status, 'invalid slip is not graded');
        $this->assertSame(0, AccumulatorBundle::graded()->count());
    }

    public function test_tomorrows_slips_are_built_too(): void
    {
        foreach ([14, 16, 18] as $h) {
            $this->pick(['odds' => 1.26, 'kickoff' => now()->addDay()->setTime($h, 0)]);
        }
        $this->rebuild();

        $b = $this->bundle(AccumulatorBundle::TYPE_BANKER_2);
        $this->assertNotNull($b);
        $this->assertSame(now('Africa/Lagos')->addDay()->toDateString(), $b->date->toDateString());
    }

    public function test_combos_command_rebuilds(): void
    {
        $this->threePicks();
        $this->artisan('lsa:combos')->assertSuccessful();
        $this->assertNotNull($this->bundle(AccumulatorBundle::TYPE_BANKER_2));
    }

    /* ------------------------------------------------ weekend list (unchanged) */

    public function test_weekend_picks_only_builds_on_friday_saturday_or_sunday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-27 10:00:00')); // a Tuesday
        $this->pick(['verdict' => 'lean', 'kickoff' => Carbon::parse('2026-10-31 15:00:00')]);
        $this->rebuild();
        $this->assertNull($this->bundle(AccumulatorBundle::TYPE_WEEKEND_PICKS));
    }

    public function test_weekend_picks_builds_on_friday_for_the_coming_weekend(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-30 10:00:00')); // a Friday
        $sat = $this->pick(['verdict' => 'confident_pick', 'kickoff' => Carbon::parse('2026-10-31 15:00:00')]);
        $sun = $this->pick(['verdict' => 'lean', 'kickoff' => Carbon::parse('2026-11-01 15:00:00')]);
        $this->pick(['verdict' => 'confident_pick', 'kickoff' => Carbon::parse('2026-11-03 15:00:00')]);
        $this->rebuild();

        $b = $this->bundle(AccumulatorBundle::TYPE_WEEKEND_PICKS);
        $this->assertEquals('2026-10-31', $b->date->toDateString());
        $this->assertEqualsCanonicalizing([$sat->id, $sun->id], $b->legs->pluck('id')->all());
    }
}
