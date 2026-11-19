<?php

namespace App\Http\Controllers;

use App\Models\Prediction;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /storage/',
            '',
            'Sitemap: ' . url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines) . "\n", 200)
            ->header('Content-Type', 'text/plain');
    }

    public function sitemap(): Response
    {
        $latestPrediction = Prediction::max('updated_at');
        $latestSettled = Prediction::settled()->max('updated_at');

        $urls = [
            ['route' => 'home', 'priority' => '1.0', 'changefreq' => 'daily', 'lastmod' => $latestPrediction],
            ['route' => 'analysis', 'priority' => '0.9', 'changefreq' => 'daily', 'lastmod' => $latestPrediction],
            ['route' => 'combos', 'priority' => '0.8', 'changefreq' => 'daily', 'lastmod' => $latestPrediction],
            ['route' => 'results', 'priority' => '0.8', 'changefreq' => 'daily', 'lastmod' => $latestSettled],
            ['route' => 'about', 'priority' => '0.5', 'changefreq' => 'monthly', 'lastmod' => null],
            ['route' => 'contact', 'priority' => '0.4', 'changefreq' => 'monthly', 'lastmod' => null],
            ['route' => 'disclaimer', 'priority' => '0.3', 'changefreq' => 'yearly', 'lastmod' => null],
            ['route' => 'privacy', 'priority' => '0.3', 'changefreq' => 'yearly', 'lastmod' => null],
        ];

        // One entry per fixture page (several markets for the same fixture share
        // a slug), and only for fixtures that pass the same quality gate the
        // page itself uses for its robots tag — a URL we mark noindex shouldn't
        // also be advertised here. Still well under the 50,000-URL sitemap limit
        // for years at ~45 fixtures/day; split into a sitemap index if that changes.
        $fixtures = ! Prediction::slugsEnabled() ? collect() : Prediction::query()
            ->whereNotNull('slug')
            ->withAnalysisData()
            ->selectRaw('slug, MAX(updated_at) AS lastmod, MAX(settled_at) AS settled')
            ->groupBy('slug')
            ->orderByDesc('lastmod')
            ->get();

        foreach ($fixtures as $f) {
            $urls[] = [
                'loc' => route('analysis.show', $f->slug),
                'priority' => $f->settled ? '0.5' : '0.7',
                'changefreq' => $f->settled ? 'monthly' : 'daily',
                'lastmod' => $f->lastmod,
            ];
        }

        $xml = view('sitemap', compact('urls'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
