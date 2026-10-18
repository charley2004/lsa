<?php

namespace App\Filament\Widgets;

use App\Models\PageView;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TrafficOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected function getStats(): array
    {
        $since = now()->subDays(7);
        $views = PageView::where('created_at', '>=', $since);

        $totalViews = (clone $views)->count();
        $uniqueVisitors = (clone $views)->distinct('session_id')->count('session_id');
        $avgDuration = (int) round((clone $views)->where('duration_seconds', '>', 0)->avg('duration_seconds') ?? 0);

        return [
            Stat::make('Page views (7d)', number_format($totalViews)),
            Stat::make('Unique visitors (7d)', number_format($uniqueVisitors)),
            Stat::make('Avg. time on page', $this->formatDuration($avgDuration)),
        ];
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }

        return sprintf('%dm %ds', intdiv($seconds, 60), $seconds % 60);
    }
}
