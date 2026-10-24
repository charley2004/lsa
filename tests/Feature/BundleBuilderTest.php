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

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function pick(array $overrides = []): Prediction
    {
        return Prediction::factory()->create(array_merge([
            'verdict' => 'confident_pick',
            'kickoff' => now()->addHours(12),
            'odds' => 1.9,
        ], $overrides));
    }

    public function test_banker_2_not_built_with_fewer_than_two_qualifying_picks(): void
    {
        $this->pick(['predicted_probability' => 0.9]);

        app(BundleBuilder::class)->rebuild();

        $this->assertNull(AccumulatorBundle::where('type', AccumulatorBundle::TYPE_BANKER_2)->first());
    }

    public function test_banker_2_picks_the_two_highest_probability_distinct_fixtures(): void
    {
        $best = $this->pick(['predicted_probability' => 0.92, 'odds' => 1.4]);
        $second = $this->pick(['predicted_probability' => 0.85, 'odds' => 1.6]);
        $this->pick(['predicted_probability' => 0.70, 'odds' => 2.0]); // weaker, should be excluded

        app(BundleBuilder::class)->rebuild();

        $bundle = AccumulatorBundle::where('type', AccumulatorBundle::TYPE_BANKER_2)->firstOrFail();
        $legIds = $bundle->legs->pluck('id')->sort()->values();

        $this->assertEqualsCanonicalizing([$best->id, $second->id], $legIds->all());
        $this->assertEqualsWithDelta(1.4 * 1.6, $bundle->combined_odds, 0.01);
    }

    public function test_banker_2_never_takes_two_legs_from_the_same_fixture(): void
    {
        $home = 'Arsenal';
        $away = 'Chelsea';
        $this->pick(['home' => $home, 'away' => $away, 'market' => 'Home win', 'predicted_probability' => 0.95]);
        $this->pick(['home' => $home, 'away' => $away, 'market' => 'Over 2.5 goals', 'predicted_probability' => 0.93]);
        $other = $this->pick(['predicted_probability' => 0.80]);

        app(BundleBuilder::class)->rebuild();

        // Only one fixture (Arsenal v Chelsea) plus the other one qualifies as
        // distinct — so Banker 2 should fall back to just these two, not two
        // markets off the same match.
        $bundle = AccumulatorBundle::where('type', AccumulatorBundle::TYPE_BANKER_2)->firstOrFail();
        $fixtures = $bundle->legs->map(fn (Prediction $p) => $p->home.'|'.$p->away)->unique();

        $this->assertCount(2, $fixtures);
        $this->assertTrue($bundle->legs->pluck('id')->contains($other->id));
    }

    public function test_odds_10_combo_lands_near_target_and_excludes_weaker_alternative(): void
    {
        // 2.0 * 2.0 * 2.5 = 10.0 exactly
        $this->pick(['predicted_probability' => 0.9, 'odds' => 2.0]);
        $this->pick(['predicted_probability' => 0.85, 'odds' => 2.0]);
        $this->pick(['predicted_probability' => 0.8, 'odds' => 2.5]);
        // An unrelated low-probability pick that would blow the combo out of band if included
        $this->pick(['predicted_probability' => 0.3, 'odds' => 8.0]);

        app(BundleBuilder::class)->rebuild();

        $bundle = AccumulatorBundle::where('type', AccumulatorBundle::TYPE_ODDS_10)->firstOrFail();

        $this->assertCount(3, $bundle->legs);
        $this->assertEqualsWithDelta(10.0, $bundle->combined_odds, 0.01);
    }

    public function test_odds_10_combo_not_built_when_nothing_lands_in_band(): void
    {
        // Every pick is odds-on favourite-ish; no subset of 3-6 gets anywhere near 8-13.
        $this->pick(['odds' => 1.2]);
        $this->pick(['odds' => 1.3]);
        $this->pick(['odds' => 1.1]);

        app(BundleBuilder::class)->rebuild();

        $this->assertNull(AccumulatorBundle::where('type', AccumulatorBundle::TYPE_ODDS_10)->first());
    }

    public function test_weekend_picks_only_builds_on_friday_saturday_or_sunday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-27 10:00:00')); // a Tuesday
        $this->pick(['verdict' => 'lean', 'kickoff' => Carbon::parse('2026-10-31 15:00:00')]); // the coming Saturday

        app(BundleBuilder::class)->rebuild();

        $this->assertNull(AccumulatorBundle::where('type', AccumulatorBundle::TYPE_WEEKEND_PICKS)->first());
    }

    public function test_weekend_picks_builds_on_friday_for_the_coming_weekend(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-30 10:00:00')); // a Friday
        $saturdayPick = $this->pick(['verdict' => 'confident_pick', 'kickoff' => Carbon::parse('2026-10-31 15:00:00')]);
        $sundayPick = $this->pick(['verdict' => 'lean', 'kickoff' => Carbon::parse('2026-11-01 15:00:00')]);
        $this->pick(['verdict' => 'confident_pick', 'kickoff' => Carbon::parse('2026-11-03 15:00:00')]); // next Tuesday, excluded

        app(BundleBuilder::class)->rebuild();

        $bundle = AccumulatorBundle::where('type', AccumulatorBundle::TYPE_WEEKEND_PICKS)->firstOrFail();
        $this->assertEquals('2026-10-31', $bundle->date->toDateString());
        $this->assertEqualsCanonicalizing(
            [$saturdayPick->id, $sundayPick->id],
            $bundle->legs->pluck('id')->sort()->values()->all()
        );
    }

    public function test_rebuild_is_idempotent_and_does_not_duplicate_rows(): void
    {
        $this->pick(['predicted_probability' => 0.92]);
        $this->pick(['predicted_probability' => 0.85]);

        app(BundleBuilder::class)->rebuild();
        app(BundleBuilder::class)->rebuild();

        $this->assertSame(1, AccumulatorBundle::where('type', AccumulatorBundle::TYPE_BANKER_2)->count());
    }

    public function test_bundles_exclude_predictions_without_odds(): void
    {
        $this->pick(['predicted_probability' => 0.95, 'odds' => null]);
        $this->pick(['predicted_probability' => 0.90, 'odds' => null]);

        app(BundleBuilder::class)->rebuild();

        $this->assertNull(AccumulatorBundle::where('type', AccumulatorBundle::TYPE_BANKER_2)->first());
    }
}
