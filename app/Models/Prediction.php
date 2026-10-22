<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'logged_at', 'home', 'away', 'kickoff', 'market', 'predicted_probability', 'odds',
        'verdict', 'conviction', 'skill', 'source',
        'settled_at', 'home_goals', 'away_goals',
        'home_corners', 'away_corners', 'home_cards', 'away_cards', 'hit',
        'league', 'xg_home', 'xg_away', 'home_form', 'away_form', 'h2h_note',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
        'kickoff' => 'datetime',
        'settled_at' => 'datetime',
        'predicted_probability' => 'float',
        'odds' => 'float',
        'conviction' => 'float',
        'skill' => 'float',
        'xg_home' => 'float',
        'xg_away' => 'float',
        'hit' => 'boolean',
        'home_form' => 'array',
        'away_form' => 'array',
    ];

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('settled_at');
    }

    public function scopeSettled(Builder $query): Builder
    {
        return $query->whereNotNull('settled_at')->whereNotNull('hit');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->open()
            ->where(fn (Builder $q) => $q->whereNull('kickoff')->orWhere('kickoff', '>=', now()))
            ->orderBy('kickoff');
    }

    public function verdictLabel(): string
    {
        return match ($this->verdict) {
            'confident_pick' => 'Confident Pick',
            'lean' => 'Lean',
            'no_confident_pick' => 'No Confident Pick',
            default => $this->verdict,
        };
    }

    public function verdictClass(): string
    {
        return match ($this->verdict) {
            'confident_pick' => 'success',
            'lean' => 'warning',
            default => '',
        };
    }

    public function outcomeLabel(): string
    {
        if ($this->hit === null) {
            return 'Not graded';
        }

        return $this->hit ? 'Aligned' : 'Unexpected';
    }

    public function outcomeClass(): string
    {
        if ($this->hit === null) {
            return '';
        }

        return $this->hit ? 'aligned' : 'unexpected';
    }

    /**
     * The real, live track record — mirrors agent/ledger.py's track_record().
     */
    public static function trackRecord(): array
    {
        $settled = static::settled()->get(['predicted_probability', 'hit']);

        if ($settled->isEmpty()) {
            return ['n_settled' => 0];
        }

        $n = $settled->count();
        $hits = $settled->where('hit', true)->count();
        $meanPredicted = $settled->avg('predicted_probability');

        return [
            'n_settled' => $n,
            'hits' => $hits,
            'hit_rate' => round($hits / $n, 3),
            'mean_predicted_probability' => round($meanPredicted, 3),
            'calibration_gap' => round(($hits / $n) - $meanPredicted, 3),
        ];
    }
}
