<?php

namespace Tests\Feature;

use App\Http\Middleware\CachePublicPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PageCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['lsa.page_cache' => true, 'lsa.page_cache_store' => 'array']);
        Cache::store('array')->flush();
    }

    public function test_public_page_is_served_from_cache_on_second_request(): void
    {
        $this->get('/analysis')->assertOk()->assertHeader('X-Page-Cache', 'MISS');
        $this->get('/analysis')->assertOk()->assertHeader('X-Page-Cache', 'HIT');
    }

    public function test_flush_makes_the_next_request_render_fresh(): void
    {
        $this->get('/results')->assertHeader('X-Page-Cache', 'MISS');
        $this->get('/results')->assertHeader('X-Page-Cache', 'HIT');

        CachePublicPage::flush();

        $this->get('/results')->assertHeader('X-Page-Cache', 'MISS');
    }

    public function test_sitemap_keeps_its_xml_content_type_when_cached(): void
    {
        $this->get('/sitemap.xml')->assertOk();
        $hit = $this->get('/sitemap.xml')->assertHeader('X-Page-Cache', 'HIT');
        $this->assertStringContainsString('xml', $hit->headers->get('Content-Type'));
    }

    public function test_contact_page_and_query_strings_are_never_cached(): void
    {
        $this->get('/contact')->assertOk()->assertHeaderMissing('X-Page-Cache');
        $this->get('/analysis?utm_source=telegram')->assertOk()->assertHeaderMissing('X-Page-Cache');
    }

    public function test_missing_fixture_404_is_not_cached(): void
    {
        $this->get('/analysis/no-such-team-vs-nobody-2030-01-01')->assertNotFound();
        $this->get('/analysis/no-such-team-vs-nobody-2030-01-01')->assertNotFound()->assertHeader('X-Page-Cache', 'MISS');
    }

    public function test_cache_can_be_switched_off(): void
    {
        config(['lsa.page_cache' => false]);
        $this->get('/analysis')->assertOk()->assertHeaderMissing('X-Page-Cache');
    }
}
