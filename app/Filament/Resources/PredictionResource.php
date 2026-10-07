<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PredictionResource\Pages;
use App\Filament\Resources\PredictionResource\RelationManagers;
use App\Models\Prediction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PredictionResource extends Resource
{
    protected static ?string $model = Prediction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('logged_at')
                    ->required(),
                Forms\Components\TextInput::make('home')
                    ->required(),
                Forms\Components\TextInput::make('away')
                    ->required(),
                Forms\Components\DateTimePicker::make('kickoff'),
                Forms\Components\TextInput::make('market')
                    ->required(),
                Forms\Components\TextInput::make('predicted_probability')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('verdict')
                    ->options([
                        'confident_pick' => 'Confident Pick',
                        'lean' => 'Lean',
                        'no_confident_pick' => 'No Confident Pick',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('conviction')
                    ->numeric(),
                Forms\Components\TextInput::make('skill')
                    ->numeric(),
                Forms\Components\TextInput::make('source')
                    ->required(),
                Forms\Components\DateTimePicker::make('settled_at'),
                Forms\Components\TextInput::make('home_goals')
                    ->numeric(),
                Forms\Components\TextInput::make('away_goals')
                    ->numeric(),
                Forms\Components\TextInput::make('home_corners')
                    ->numeric(),
                Forms\Components\TextInput::make('away_corners')
                    ->numeric(),
                Forms\Components\TextInput::make('home_cards')
                    ->numeric(),
                Forms\Components\TextInput::make('away_cards')
                    ->numeric(),
                Forms\Components\Toggle::make('hit'),
                Forms\Components\TextInput::make('league'),
                Forms\Components\TextInput::make('xg_home')
                    ->numeric(),
                Forms\Components\TextInput::make('xg_away')
                    ->numeric(),
                Forms\Components\Textarea::make('home_form')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('away_form')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('h2h_note')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('logged_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('home')
                    ->searchable(),
                Tables\Columns\TextColumn::make('away')
                    ->searchable(),
                Tables\Columns\TextColumn::make('kickoff')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('market')
                    ->searchable(),
                Tables\Columns\TextColumn::make('predicted_probability')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('verdict')
                    ->searchable(),
                Tables\Columns\TextColumn::make('conviction')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('skill')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('source')
                    ->searchable(),
                Tables\Columns\TextColumn::make('settled_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('home_goals')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('away_goals')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('home_corners')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('away_corners')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('home_cards')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('away_cards')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('hit')
                    ->boolean(),
                Tables\Columns\TextColumn::make('league')
                    ->searchable(),
                Tables\Columns\TextColumn::make('xg_home')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('xg_away')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPredictions::route('/'),
            'create' => Pages\CreatePrediction::route('/create'),
            'edit' => Pages\EditPrediction::route('/{record}/edit'),
        ];
    }
}
