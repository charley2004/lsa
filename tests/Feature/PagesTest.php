<?php

namespace Tests\Feature;

use App\Models\Prediction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    public static function publicRouteProvider(): array
    {
        return [
            'home' => ['home'],
            'about' => ['about'],
            'analysis' => ['analysis'],
            'combos' => ['combos'],
            'results' => ['results'],
            'contact' => ['contact'],
            'disclaimer' => ['disclaimer'],
            'privacy' => ['privacy'],
        ];
    }

    #[DataProvider('publicRouteProvider')]
    public function test_public_page_loads(string $routeName): void
    {
        $this->get(route($routeName))->assertOk();
    }

    public function test_analysis_page_shows_empty_state_with_no_predictions(): void
    {
        $this->get(route('analysis'))
            ->assertOk()
            ->assertSee('No upcoming analysis right now');
    }

    public function test_analysis_page_groups_fixtures_by_day_and_shows_day_tabs(): void
    {
        Prediction::factory()->count(2)->create(['kickoff' => now()->addDay()]);
        Prediction::factory()->count(3)->create(['kickoff' => now()->addDays(2)]);

        $response = $this->get(route('analysis'));

        $response->assertOk();
        $response->assertSee('data-fixture-filters', false);
        $response->assertSeeInOrder(['data-day-group', 'data-day-group'], false);
    }

    public function test_analysis_page_hides_day_tabs_when_only_one_day_present(): void
    {
        Prediction::factory()->count(3)->create(['kickoff' => now()->addDay()]);

        $this->get(route('analysis'))
            ->assertOk()
            ->assertDontSee('data-fixture-filters', false);
    }

    public function test_results_page_labels_model_retest_as_hindsight(): void
    {
        $response = $this->get(route('results'));

        $response->assertOk();
        $response->assertSee('a re-test in hindsight, not picks we published', false);
        $response->assertDontSee('173', false);
    }

    public function test_results_headline_is_current_model_and_previous_is_archived(): void
    {
        $base = ['market' => 'Home win', 'predicted_probability' => 0.75, 'odds' => 1.3, 'verdict' => 'confident_pick',
                 'settled_at' => now(), 'home_goals' => 2, 'away_goals' => 0, 'logged_at' => now()->subDays(10)];
        \App\Models\Prediction::create($base + ['home' => 'NewA', 'away' => 'NewB', 'kickoff' => now()->subDay(),
            'hit' => true, 'model_version' => 'v2-20261121-abc']);
        \App\Models\Prediction::create($base + ['home' => 'OldA', 'away' => 'OldB', 'kickoff' => now()->subDays(9),
            'hit' => false]);

        $this->assertSame(1, \App\Models\Prediction::trackRecord('current')['n_settled']);
        $this->assertSame(1, \App\Models\Prediction::trackRecord('previous')['n_settled']);

        $html = $this->get(route('results'))->assertOk()->getContent();
        $archive = strpos($html, 'id="previous-model"');
        $this->assertNotFalse($archive, 'previous model archive missing');
        $this->assertLessThan($archive, strpos($html, 'NewA'), 'current pick must be in the headline ledger');
        $this->assertGreaterThan($archive, strpos($html, 'OldA'), 'previous pick must be in the archive');
    }

    public function test_results_page_shows_real_track_record(): void
    {
        Prediction::factory()->count(3)->settled(true)->create();
        Prediction::factory()->count(1)->settled(false)->create();

        $response = $this->get(route('results'));

        $response->assertOk();
        $response->assertSee('4'); // total settled
    }

    public function test_robots_txt_disallows_admin(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /admin');
    }

    public function test_sitemap_lists_all_public_pages(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        foreach (['home', 'about', 'analysis', 'combos', 'results', 'contact', 'disclaimer', 'privacy'] as $name) {
            $response->assertSee(route($name), false);
        }
    }

    public function test_manifest_is_valid_json_with_icons(): void
    {
        // manifest.json is a static file served directly by the web server,
        // not a Laravel route — the test HTTP kernel doesn't serve public/
        // static assets, so this reads it straight off disk instead.
        $data = json_decode(file_get_contents(public_path('manifest.json')), true);

        $this->assertIsArray($data);
        $this->assertNotEmpty($data['icons']);
    }

    public function test_admin_panel_requires_authentication(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }
}
