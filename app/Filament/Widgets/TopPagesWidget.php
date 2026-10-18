<?php

namespace App\Filament\Widgets;

use App\Models\PageView;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TopPagesWidget extends TableWidget
{
    protected static ?int $sort = -1;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PageView::query()
                    ->select('path')
                    ->selectRaw('MIN(id) as id')
                    ->selectRaw('COUNT(*) as views')
                    ->selectRaw('COUNT(DISTINCT session_id) as visitors')
                    ->selectRaw('ROUND(AVG(NULLIF(duration_seconds, 0))) as avg_duration')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->groupBy('path')
            )
            ->heading('Top pages (last 30 days)')
            ->columns([
                Tables\Columns\TextColumn::make('path')->label('Page'),
                Tables\Columns\TextColumn::make('views')->label('Views')->sortable(),
                Tables\Columns\TextColumn::make('visitors')->label('Unique visitors')->sortable(),
                Tables\Columns\TextColumn::make('avg_duration')
                    ->label('Avg. time')
                    ->formatStateUsing(fn (?string $state): string => $state ? gmdate('i\m s\s', (int) $state) : '—')
                    ->sortable(),
            ])
            ->defaultSort('views', 'desc')
            ->paginated([5, 10, 25]);
    }
}
