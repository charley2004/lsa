<?php

namespace Tests\Feature;

use App\Models\Prediction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictionSyncTest extends TestCase
{
    use RefreshDatabase;

    private function samplePrediction(array $overrides = []): array
    {
        return array_merge([
            'home' => 'Arsenal',
            'away' => 'Chelsea',
            'kickoff' => '2026-11-01T15:00:00',
            'market' => 'Over 2.5 goals',
            'predicted_probability' => 0.64,
            'verdict' => 'lean',
        ], $overrides);
    }

    public function test_sync_rejects_request_with_no_secret_configured(): void
    {
        config(['services.sync_shared_secret' => null]);

        $response = $this->postJson('/api/sync/predictions', [
            'predictions' => [$this->samplePrediction()],
        ], ['X-Sync-Key' => 'anything']);

        $response->assertStatus(500);
        $this->assertDatabaseCount('predictions', 0);
    }

    public function test_sync_rejects_wrong_key(): void
    {
        config(['services.sync_shared_secret' => 'the-real-secret']);

        $response = $this->postJson('/api/sync/predictions', [
            'predictions' => [$this->samplePrediction()],
        ], ['X-Sync-Key' => 'wrong-key']);

        $response->assertStatus(401);
        $this->assertDatabaseCount('predictions', 0);
    }

    public function test_sync_rejects_missing_key_header(): void
    {
        config(['services.sync_shared_secret' => 'the-real-secret']);

        $response = $this->postJson('/api/sync/predictions', [
            'predictions' => [$this->samplePrediction()],
        ]);

        $response->assertStatus(401);
    }

    public function test_sync_accepts_correct_key_and_creates_prediction(): void
    {
        config(['services.sync_shared_secret' => 'the-real-secret']);

        $response = $this->postJson('/api/sync/predictions', [
            'predictions' => [$this->samplePrediction()],
        ], ['X-Sync-Key' => 'the-real-secret']);

        $response->assertOk();
        $response->assertJson(['created' => 1, 'updated' => 0]);
        $this->assertDatabaseHas('predictions', ['home' => 'Arsenal', 'away' => 'Chelsea']);
    }

    public function test_sync_updates_existing_fixture_instead_of_duplicating(): void
    {
        config(['services.sync_shared_secret' => 'the-real-secret']);
        $headers = ['X-Sync-Key' => 'the-real-secret'];

        // First post: logs the open prediction.
        $this->postJson('/api/sync/predictions', ['predictions' => [$this->samplePrediction()]], $headers)
            ->assertJson(['created' => 1, 'updated' => 0]);

        // Second post: same fixture/market/kickoff, now with a result — must
        // update the same row, not create a duplicate.
        $this->postJson('/api/sync/predictions', [
            'predictions' => [$this->samplePrediction([
                'settled_at' => '2026-11-01T17:00:00',
                'home_goals' => 2,
                'away_goals' => 1,
                'hit' => true,
            ])],
        ], $headers)->assertJson(['created' => 0, 'updated' => 1]);

        $this->assertSame(1, Prediction::count());
        $this->assertSame(1, Prediction::settled()->count());
    }

    public function test_sync_validates_required_fields(): void
    {
        config(['services.sync_shared_secret' => 'the-real-secret']);

        $response = $this->postJson('/api/sync/predictions', [
            'predictions' => [['home' => 'Arsenal']], // missing away, market, etc.
        ], ['X-Sync-Key' => 'the-real-secret']);

        $response->assertStatus(422);
    }

    public function test_sync_endpoint_only_accepts_post(): void
    {
        config(['services.sync_shared_secret' => 'the-real-secret']);

        $this->getJson('/api/sync/predictions', ['X-Sync-Key' => 'the-real-secret'])
            ->assertStatus(405);
    }
}
