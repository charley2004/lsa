<?php

namespace App\Filament\Widgets;

use App\Models\PageSectionView;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TopSectionsWidget extends TableWidget
{
    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PageSectionView::query()
                    ->join('page_views', 'page_views.id', '=', 'page_section_views.page_view_id')
                    ->select('page_section_views.section')
                    ->selectRaw('MIN(page_section_views.id) as id')
                    ->selectRaw('COUNT(*) as views')
                    ->selectRaw('ROUND(AVG(page_section_views.seconds)) as avg_seconds')
                    ->where('page_views.created_at', '>=', now()->subDays(30))
                    ->groupBy('page_section_views.section')
            )
            ->heading('Section engagement (last 30 days)')
            ->columns([
                Tables\Columns\TextColumn::make('section')->label('Section'),
                Tables\Columns\TextColumn::make('views')->label('Times seen')->sortable(),
                Tables\Columns\TextColumn::make('avg_seconds')
                    ->label('Avg. time in view')
                    ->formatStateUsing(fn (?string $state): string => $state ? gmdate('i\m s\s', (int) $state) : '—')
                    ->sortable(),
            ])
            ->defaultSort('avg_seconds', 'desc')
            ->paginated(false);
    }
}
