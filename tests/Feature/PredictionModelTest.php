<?php

namespace Tests\Feature;

use App\Models\Prediction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_scope_excludes_settled_predictions(): void
    {
        Prediction::factory()->create(); // open
        Prediction::factory()->settled(true)->create();

        $this->assertSame(1, Prediction::open()->count());
    }

    public function test_upcoming_scope_excludes_past_kickoffs(): void
    {
        Prediction::factory()->create(['kickoff' => now()->addDay()]);
        Prediction::factory()->create(['kickoff' => now()->subHour()]); // already kicked off, still "open" but not upcoming

        $this->assertSame(1, Prediction::upcoming()->count());
    }

    public function test_upcoming_scope_includes_predictions_with_no_kickoff(): void
    {
        Prediction::factory()->create(['kickoff' => null]);

        $this->assertSame(1, Prediction::upcoming()->count());
    }

    public function test_track_record_calculates_hit_rate_correctly(): void
    {
        Prediction::factory()->count(3)->settled(true)->create();
        Prediction::factory()->count(1)->settled(false)->create();

        $track = Prediction::trackRecord();

        $this->assertSame(4, $track['n_settled']);
        $this->assertSame(3, $track['hits']);
        $this->assertSame(0.75, $track['hit_rate']);
    }

    public function test_track_record_handles_no_settled_predictions(): void
    {
        Prediction::factory()->create(); // open, not settled

        $track = Prediction::trackRecord();

        $this->assertSame(0, $track['n_settled']);
    }

    public function test_verdict_label_and_class_map_correctly(): void
    {
        $confident = Prediction::factory()->make(['verdict' => 'confident_pick']);
        $lean = Prediction::factory()->make(['verdict' => 'lean']);
        $none = Prediction::factory()->make(['verdict' => 'no_confident_pick']);

        $this->assertSame('Confident Pick', $confident->verdictLabel());
        $this->assertSame('success', $confident->verdictClass());
        $this->assertSame('Lean', $lean->verdictLabel());
        $this->assertSame('warning', $lean->verdictClass());
        $this->assertSame('No Confident Pick', $none->verdictLabel());
        $this->assertSame('', $none->verdictClass());
    }

    public function test_outcome_label_and_class_reflect_hit_status(): void
    {
        $aligned = Prediction::factory()->settled(true)->make();
        $unexpected = Prediction::factory()->settled(false)->make();
        $ungraded = Prediction::factory()->make(['hit' => null]);

        $this->assertSame('Aligned', $aligned->outcomeLabel());
        $this->assertSame('aligned', $aligned->outcomeClass());
        $this->assertSame('Unexpected', $unexpected->outcomeLabel());
        $this->assertSame('unexpected', $unexpected->outcomeClass());
        $this->assertSame('Not graded', $ungraded->outcomeLabel());
        $this->assertSame('', $ungraded->outcomeClass());
    }

    public function test_home_form_and_away_form_cast_to_array(): void
    {
        $prediction = Prediction::factory()->create([
            'home_form' => ['W', 'W', 'D', 'L', 'W'],
        ]);

        $this->assertIsArray($prediction->fresh()->home_form);
        $this->assertSame(['W', 'W', 'D', 'L', 'W'], $prediction->fresh()->home_form);
    }
}
