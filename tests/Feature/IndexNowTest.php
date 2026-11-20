<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IndexNowTest extends TestCase
{
    use RefreshDatabase;

    private function row(array $o = []): array
    {
        return array_merge([
            'home' => 'Man City', 'away' => 'Fulham', 'kickoff' => '2030-11-21T15:00:00+00:00',
            'market' => 'Home win', 'predicted_probability' => 0.7, 'verdict' => 'confident_pick',
            'xg_home' => 2.1, 'xg_away' => 0.9, 'home_form' => ['W'], 'away_form' => ['L'],
        ], $o);
    }

    private function sync(array $rows)
    {
        return $this->postJson('/api/sync/predictions', ['predictions' => $rows], ['X-Sync-Key' => 'k']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.sync_shared_secret' => 'k', 'lsa.indexnow_enabled' => true, 'lsa.indexnow_key' => 'testkey123']);
        Http::fake(['api.indexnow.org/*' => Http::response('', 200)]);
    }

    public function test_key_file_is_served_for_ownership_proof(): void
    {
        $key = config('lsa.indexnow_key');
        // the route is registered at boot with the configured key
        $this->get('/' . $key . '.txt')->assertOk()->assertSeeText($key);
    }

    public function test_new_fixture_pages_are_submitted_on_sync(): void
    {
        $this->sync([$this->row()])->assertOk()->assertJson(['created' => 1, 'indexnow' => 4]);

        Http::assertSent(fn ($req) => $req->url() === 'https://api.indexnow.org/indexnow'
            && $req['key'] === 'testkey123'
            && in_array(route('analysis.show', 'man-city-vs-fulham-2030-11-21'), $req['urlList'], true));
    }

    public function test_unchanged_resync_submits_nothing(): void
    {
        $this->sync([$this->row()]);
        Http::fake(['api.indexnow.org/*' => Http::response('', 200)]);  // reset recorded requests

        $this->sync([$this->row()])->assertOk()->assertJson(['updated' => 1, 'indexnow' => 0]);
        Http::assertNothingSent();
    }

    public function test_changed_fixture_is_resubmitted(): void
    {
        $this->sync([$this->row()]);
        $this->sync([$this->row(['xg_home' => 2.4])])->assertOk()->assertJson(['indexnow' => 4]);
    }

    public function test_disabled_outside_production_by_default(): void
    {
        config(['lsa.indexnow_enabled' => false]);
        $this->sync([$this->row()])->assertOk()->assertJson(['indexnow' => 0]);
        Http::assertNothingSent();
    }
}
