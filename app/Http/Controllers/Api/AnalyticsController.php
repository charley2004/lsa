<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PageSectionView;
use App\Models\PageView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// First-party traffic tracking: no cookies, no third party, no IP or raw
// user-agent ever stored — just a random client-generated session id, the
// path, and (via flush) how long each [data-track-section] element actually
// stayed visible. See public/js/analytics.js for the client half.
class AnalyticsController extends Controller
{
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|bingpreview|headless/i';

    public function view(Request $request): JsonResponse
    {
        $userAgent = (string) $request->userAgent();

        if ($userAgent === '' || preg_match(self::BOT_PATTERN, $userAgent) === 1) {
            return response()->json(['id' => null]);
        }

        $data = $request->validate([
            'session_id' => ['required', 'string', 'max:64'],
            'path' => ['required', 'string', 'max:255'],
            'referrer' => ['nullable', 'string', 'max:512'],
        ]);

        $pageView = PageView::create([
            'session_id' => $data['session_id'],
            'path' => parse_url($data['path'], PHP_URL_PATH) ?: $data['path'],
            'referrer' => $data['referrer'] ?? null,
            'device' => $this->deviceFromUserAgent($userAgent),
        ]);

        return response()->json(['id' => $pageView->id]);
    }

    public function flush(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'duration_delta' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'sections' => ['nullable', 'array', 'max:30'],
            'sections.*.name' => ['required_with:sections', 'string', 'max:64'],
            'sections.*.seconds' => ['required_with:sections', 'integer', 'min:0', 'max:3600'],
        ]);

        $pageView = PageView::find($data['id']);

        if (! $pageView) {
            return response()->json(['ok' => true]);
        }

        if (! empty($data['duration_delta'])) {
            $pageView->increment('duration_seconds', $data['duration_delta']);
        }

        foreach ($data['sections'] ?? [] as $section) {
            $row = PageSectionView::firstOrCreate(
                ['page_view_id' => $pageView->id, 'section' => $section['name']],
                ['seconds' => 0],
            );
            $row->increment('seconds', $section['seconds']);
        }

        return response()->json(['ok' => true]);
    }

    private function deviceFromUserAgent(string $userAgent): string
    {
        if (preg_match('/iPad|Tablet/i', $userAgent) === 1) {
            return 'tablet';
        }

        if (preg_match('/Mobile|iPhone|Android/i', $userAgent) === 1) {
            return 'mobile';
        }

        return 'desktop';
    }
}
