<?php

namespace Tests\Feature;

use App\Models\Prediction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FixturePageTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(array $attrs = []): Prediction
    {
        return Prediction::factory()->create(array_merge([
            'home' => 'Man City',
            'away' => 'Fulham',
            'kickoff' => Carbon::parse('2030-11-21 15:00:00'),
            'xg_home' => 2.1,
            'xg_away' => 0.9,
            'home_form' => ['W', 'W', 'D', 'L', 'W'],
            'away_form' => ['L', 'D', 'L', 'W', 'L'],
            'verdict' => 'confident_pick',
            'league' => 'E0',
        ], $attrs));
    }

    public function test_slug_is_generated_from_teams_and_kickoff_date(): void
    {
        $this->assertSame('man-city-vs-fulham-2030-11-21', $this->fixture()->slug);
    }

    public function test_slug_never_changes_on_update(): void
    {
        $p = $this->fixture();
        $p->update(['kickoff' => Carbon::parse('2030-11-22 15:00:00'), 'home' => 'Manchester City']);

        $this->assertSame('man-city-vs-fulham-2030-11-21', $p->fresh()->slug);
    }

    public function test_fixture_page_renders_with_seo_elements(): void
    {
        $this->fixture();

        $this->get('/analysis/man-city-vs-fulham-2030-11-21')
            ->assertOk()
            ->assertSee('<title>Man City vs Fulham Prediction &amp; Analysis (21 Nov 2030) | LSA</title>', false)
            ->assertSee('<h1 class="page-head__title">Man City vs Fulham: Match Analysis &amp; Preview</h1>', false)
            ->assertSee('<meta name="robots" content="index, follow">', false)
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('"@type":"SportsEvent"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('Premier League (England)')
            ->assertSee('3 wins, 1 draw and 1 loss in their last five');
    }

    public function test_markets_for_the_same_fixture_share_one_page(): void
    {
        $this->fixture(['market' => 'Over 2.5 goals']);
        $this->fixture(['market' => 'Home win']);

        $this->assertSame(1, Prediction::distinct()->count('slug'));
        $this->get('/analysis/man-city-vs-fulham-2030-11-21')
            ->assertOk()
            ->assertSee('Over 2.5 goals')
            ->assertSee('Home win');
    }

    public function test_settled_fixture_page_stays_live_and_shows_result(): void
    {
        $this->fixture([
            'kickoff' => Carbon::parse('2020-11-21 15:00:00'),
            'settled_at' => now(), 'home_goals' => 3, 'away_goals' => 1, 'hit' => true,
        ]);

        $this->get('/analysis/man-city-vs-fulham-2020-11-21')
            ->assertOk()
            ->assertSee('Final score: Man City 3–1 Fulham.')
            ->assertSee('Aligned');
    }

    public function test_fixture_without_analysis_data_is_noindex_and_not_in_sitemap(): void
    {
        $this->fixture(['xg_home' => null, 'xg_away' => null, 'home_form' => null, 'away_form' => null]);

        $this->get('/analysis/man-city-vs-fulham-2030-11-21')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, follow">', false);

        $this->get('/sitemap.xml')->assertDontSee('man-city-vs-fulham-2030-11-21', false);
    }

    public function test_unknown_fixture_returns_404(): void
    {
        $this->get('/analysis/nobody-vs-nobody-2030-01-01')->assertNotFound();
    }

    public function test_sitemap_lists_fixture_pages_and_starts_with_xml_declaration(): void
    {
        $this->fixture();

        $response = $this->get('/sitemap.xml')->assertOk();

        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', ltrim($response->getContent()));
        $response->assertSee(route('analysis.show', 'man-city-vs-fulham-2030-11-21'), false);
    }

    public function test_hub_links_to_fixture_pages_with_correct_heading_structure(): void
    {
        $this->fixture(['kickoff' => now()->addDay()]);

        $html = $this->get(route('analysis'))
            ->assertOk()
            ->assertSee('<title>Football Analysis Today: Match Previews &amp; Stats | LSA</title>', false)
            ->assertSee('<h1 class="page-head__title">Football Analysis Today</h1>', false)
            ->assertSee("<h2>Tomorrow&#039;s matches", false)
            ->assertSee('"@type":"ItemList"', false)
            ->assertSee(Prediction::first()->fixtureUrl(), false)
            ->assertSee('Join LSA on Telegram')
            ->getContent();

        // Team names are no longer H2s.
        $this->assertStringNotContainsString('<h2>Man City</h2>', $html);
    }

    public function test_kickoff_is_shown_in_wat_and_late_utc_kickoffs_count_as_next_local_day(): void
    {
        // 23:30 UTC on the 21st is 00:30 WAT on the 22nd.
        $p = $this->fixture(['kickoff' => Carbon::parse('2030-11-21 23:30:00', 'UTC')]);

        $this->assertSame('man-city-vs-fulham-2030-11-22', $p->slug);
        $this->assertSame('00:30 WAT', $p->kickoffTime());

        $this->get('/analysis/man-city-vs-fulham-2030-11-22')
            ->assertOk()
            ->assertSee('Fri 22 Nov 2030, 00:30 WAT');
    }

    public function test_results_rows_link_to_fixture_pages(): void
    {
        $p = $this->fixture([
            'kickoff' => Carbon::parse('2020-11-21 15:00:00'),
            'settled_at' => now(), 'home_goals' => 3, 'away_goals' => 1, 'hit' => true,
        ]);

        $this->get(route('results'))->assertOk()->assertSee($p->fixtureUrl(), false);
    }

    public function test_homepage_uses_real_fixtures_and_covered_leagues_only(): void
    {
        $p = $this->fixture(['kickoff' => now()->addDay()]);

        $html = $this->get(route('home'))->assertOk()
            ->assertSee('<title>LSA: Data-Driven Football Analysis &amp; Match Previews</title>', false)
            ->assertSee('id="console-data"', false)
            ->assertSee('Premier League (England)')
            ->assertSee($p->fixtureUrl(), false)
            ->assertSee(config('lsa.telegram_url'), false)
            ->getContent();

        // No mock fixture, no claims the model can't back up, no dead links.
        foreach (['Sat 18 Jan', 'Possession', 'Champions League', 'Team news', 'href="#"', 'App Store'] as $bad) {
            $this->assertStringNotContainsString($bad, $html, "homepage still contains: {$bad}");
        }
    }
}
