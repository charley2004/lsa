<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookmakerOfferResource\Pages;
use App\Models\BookmakerOffer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BookmakerOfferResource extends Resource
{
    protected static ?string $model = BookmakerOffer::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-pound';

    protected static ?string $navigationLabel = 'Bookmaker Offers';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->helperText('e.g. "Bet365" — shown on the CTA button.'),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->helperText('Used in the public link: /go/{slug}'),
                Forms\Components\Textarea::make('affiliate_url')
                    ->required()
                    ->columnSpanFull()
                    ->helperText('The real tracked affiliate link from the bookmaker deal — not the bookmaker\'s plain homepage.'),
                Forms\Components\TextInput::make('cta_text')
                    ->default('Claim Offer')
                    ->required(),
                Forms\Components\TextInput::make('offer_detail')
                    ->helperText('e.g. "Bet £10 Get £30 in Free Bets"'),
                Forms\Components\TextInput::make('logo_path')
                    ->helperText('Path under public/ — e.g. images/bookmakers/bet365.png'),
                Forms\Components\TextInput::make('display_order')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('display_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug'),
                Tables\Columns\TextColumn::make('offer_detail')
                    ->limit(40),
                Tables\Columns\TextColumn::make('click_count')
                    ->label('Clicks')
                    ->sortable(),
                Tables\Columns\IconColumn::make('active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('display_order')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookmakerOffers::route('/'),
            'create' => Pages\CreateBookmakerOffer::route('/create'),
            'edit' => Pages\EditBookmakerOffer::route('/{record}/edit'),
        ];
    }
}
