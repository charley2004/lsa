<?php

namespace App\Filament\Resources\AccumulatorBundleResource\Pages;

use App\Filament\Resources\AccumulatorBundleResource;
use Filament\Resources\Pages\ListRecords;

class ListAccumulatorBundles extends ListRecords
{
    protected static string $resource = AccumulatorBundleResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
