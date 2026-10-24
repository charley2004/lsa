<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AccumulatorBundle extends Model
{
    protected $fillable = ['type', 'date', 'combined_odds'];

    protected $casts = [
        'date' => 'date',
        'combined_odds' => 'float',
    ];

    public const TYPE_BANKER_2 = 'banker_2';

    public const TYPE_ODDS_10 = 'odds_10';

    public const TYPE_WEEKEND_PICKS = 'weekend_picks';

    public function legs(): BelongsToMany
    {
        return $this->belongsToMany(Prediction::class, 'accumulator_bundle_legs')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('date', '>=', now()->toDateString())->orderBy('date');
    }

    public function label(): string
    {
        return match ($this->type) {
            self::TYPE_BANKER_2 => 'Banker 2',
            self::TYPE_ODDS_10 => '10-Odds Combo',
            self::TYPE_WEEKEND_PICKS => 'Top Weekend Picks',
            default => $this->type,
        };
    }

    public function strapline(): string
    {
        return match ($this->type) {
            self::TYPE_BANKER_2 => 'Our two most confident reads today, picked from different matches.',
            self::TYPE_ODDS_10 => 'A combined-odds accumulator built from real bookmaker prices, targeting around 10.0.',
            self::TYPE_WEEKEND_PICKS => "The strongest individual reads across this weekend's fixtures.",
            default => '',
        };
    }
}
