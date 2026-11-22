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
            'odds' => 1.25,
            'predicted_probability' => 0.8,
        ], $overrides));
    }

    private function bundle(string $type): ?AccumulatorBundle
    {
        return AccumulatorBundle::with('legs')->where('type', $type)->first();
    }

    private function rebuild(): void
    {
        app(BundleBuilder::class)->rebuild();
    }

    /* ------------------------------------------------ Banker 2 Odds */

    public function test_banker_2_odds_reaches_about_2_00_from_short_prices(): void
    {
        // The old rule (top two by probability) gave 1.20 x 1.22 = 1.46. Now: ~2.00.
        foreach ([1.20, 1.22, 1.25, 1.26, 1.27, 1.30, 1.35] as $i => $o) {
            $this->pick(['odds' => $o, 'predicted_probability' => 0.86 - $i * 0.01]);
        }
        $this->rebuild();

        $b = $this->bundle(AccumulatorBundle::TYPE_BANKER_2);
        $this->assertNotNull($b);
        $this->assertGreaterThanOrEqual(2.00, $b->combined_odds);
        $this->assertLessThanOrEqual(2.50, $b->combined_odds);
        $this->assertGreaterThanOrEqual(2, $b->legs->count());
    }

    public function test_banker_2_odds_chooses_the_combination_most_likely_to_land(): void
    {
        // Both pairs reach ~2.0, but the first pair is far more likely to land.
        $a = $this->pick(['odds' => 1.42, 'predicted_probability' => 0.80]);
        $b = $this->pick(['odds' => 1.45, 'predicted_probability' => 0.78]);
        $this->pick(['odds' => 1.05, 'predicted_probability' => 0.97]);  // too short to pair to 2.0 with anything here
        $this->pick(['odds' => 1.95, 'predicted_probability' => 0.40]);
        $this->pick(['odds' => 1.02, 'predicted_probability' => 0.20]);

        $this->rebuild();

        $legs = $this->bundle(AccumulatorBundle::TYPE_BANKER_2)->legs->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $legs);
    }

    public function test_banker_2_odds_not_built_with_fewer_than_two_qualifying_picks(): void
    {
        $this->pick(['odds' => 2.0]);
        $this->rebuild();
        $this->assertNull($this->bundle(AccumulatorBundle::TYPE_BANKER_2));
    }

    public function test_one_leg_per_fixture(): void
    {
        $this->pick(['home' => 'Arsenal', 'away' => 'Chelsea', 'market' => 'Home win', 'odds' => 1.42, 'predicted_probability' => 0.80]);
        $this->pick(['home' => 'Arsenal', 'away' => 'Chelsea', 'market' => 'Over 1.5 goals', 'odds' => 1.42, 'predicted_probability' => 0.79]);
        $this->pick(['odds' => 1.45, 'predicted_probability' => 0.75]);

        $this->rebuild();

        $fixtures = $this->bundle(AccumulatorBundle::TYPE_BANKER_2)->legs->map(fn ($p) => $p->home . '|' . $p->away)->unique();
        $this->assertCount(2, $fixtures);
    }

    public function test_picks_without_odds_are_never_used(): void
    {
        $this->pick(['odds' => null]);
        $this->pick(['odds' => null]);
        $this->rebuild();
        $this->assertNull($this->bundle(AccumulatorBundle::TYPE_BANKER_2));
    }

    /* ------------------------------------------------ 10 Odds */

    public function test_10_odds_is_built_from_a_short_priced_slate(): void
    {
        // 17 picks at 1.15-1.37 (like the real 22 Nov slate): the old 6-leg cap
        // could never reach 8.0. Now it uses as many legs as needed (up to 12).
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
        $this->pick(['odds' => 1.43, 'kickoff' => now()->addHours(1)]);
        $this->pick(['odds' => 1.43, 'kickoff' => now()->addHours(5)]);
        $this->rebuild();
        $before = $this->bundle(AccumulatorBundle::TYPE_BANKER_2)->legs->pluck('id')->all();

        // first leg kicks off; a "better" pick appears afterwards
        Carbon::setTestNow(now()->addHours(2));
        $this->pick(['odds' => 1.41, 'predicted_probability' => 0.95, 'kickoff' => now()->addHours(3)]);
        $this->rebuild();

        $b = $this->bundle(AccumulatorBundle::TYPE_BANKER_2);
        $this->assertNotNull($b->locked_at);
        $this->assertSame($before, $b->legs->pluck('id')->all());
    }

    public function test_combo_is_graded_won_when_every_leg_lands(): void
    {
        $a = $this->pick(['odds' => 1.43, 'kickoff' => now()->addHours(1)]);
        $b = $this->pick(['odds' => 1.43, 'kickoff' => now()->addHours(2)]);
        $this->rebuild();

        Carbon::setTestNow(now()->addHours(6));
        $a->update(['settled_at' => now(), 'hit' => true, 'home_goals' => 2, 'away_goals' => 0]);
        $b->update(['settled_at' => now(), 'hit' => true, 'home_goals' => 1, 'away_goals' => 0]);
        $this->rebuild();

        $this->assertSame(AccumulatorBundle::STATUS_WON, $this->bundle(AccumulatorBundle::TYPE_BANKER_2)->status);
    }

    public function test_combo_is_graded_lost_as_soon_as_one_leg_fails(): void
    {
        $a = $this->pick(['odds' => 1.43, 'kickoff' => now()->addHours(1)]);
        $this->pick(['odds' => 1.43, 'kickoff' => now()->addHours(5)]);
        $this->rebuild();

        Carbon::setTestNow(now()->addHours(3));  // second leg not played yet
        $a->update(['settled_at' => now(), 'hit' => false, 'home_goals' => 0, 'away_goals' => 1]);
        $this->rebuild();

        $this->assertSame(AccumulatorBundle::STATUS_LOST, $this->bundle(AccumulatorBundle::TYPE_BANKER_2)->status);
    }

    public function test_rebuild_is_idempotent(): void
    {
        $this->pick(['odds' => 1.42]);
        $this->pick(['odds' => 1.43]);  // 2.03: at least 2.00
        $this->rebuild();
        $this->rebuild();
        $this->assertSame(1, AccumulatorBundle::where('type', AccumulatorBundle::TYPE_BANKER_2)->count());
    }

    public function test_combos_page_shows_won_combo_in_the_record(): void
    {
        $a = $this->pick(['odds' => 1.43, 'kickoff' => now()->addHours(1)]);
        $b = $this->pick(['odds' => 1.43, 'kickoff' => now()->addHours(2)]);
        $this->rebuild();
        Carbon::setTestNow(now()->addHours(6));
        $a->update(['settled_at' => now(), 'hit' => true, 'home_goals' => 2, 'away_goals' => 0]);
        $b->update(['settled_at' => now(), 'hit' => true, 'home_goals' => 1, 'away_goals' => 0]);
        $this->rebuild();

        $this->get(route('combos'))->assertOk()
            ->assertSee('Banker 2 Odds')
            ->assertSee('won 1 of 1')
            ->assertSee('Every combo, graded.')
            ->assertSee('2 of 2 landed');
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
