<?php

namespace App\Filament\Resources\BookmakerOfferResource\Pages;

use App\Filament\Resources\BookmakerOfferResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBookmakerOffers extends ListRecords
{
    protected static string $resource = BookmakerOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
