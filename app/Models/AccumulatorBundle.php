<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AccumulatorBundle extends Model
{
    protected $fillable = ['type', 'date', 'combined_odds', 'locked_at', 'status', 'settled_at'];

    protected $casts = [
        'date' => 'date',
        'combined_odds' => 'float',
        'locked_at' => 'datetime',
        'settled_at' => 'datetime',
    ];

    public const TYPE_BANKER_2 = 'banker_2';

    public const TYPE_ODDS_10 = 'odds_10';

    public const TYPE_WEEKEND_PICKS = 'weekend_picks';

    public const STATUS_PENDING = 'pending';

    public const STATUS_WON = 'won';

    public const STATUS_LOST = 'lost';

    public const STATUS_VOID = 'void';

    public function legs(): BelongsToMany
    {
        return $this->belongsToMany(Prediction::class, 'accumulator_bundle_legs')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('date', '>=', now(Prediction::displayTimezone())->toDateString())->orderBy('date');
    }

    /** Graded combos (won/lost), newest first — the public combo record. */
    public function scopeGraded(Builder $query): Builder
    {
        return $query->whereIn('type', [self::TYPE_BANKER_2, self::TYPE_ODDS_10])
            ->whereIn('status', [self::STATUS_WON, self::STATUS_LOST])
            ->orderByDesc('date');
    }

    public function label(): string
    {
        return match ($this->type) {
            self::TYPE_BANKER_2 => 'Banker 2 Odds',
            self::TYPE_ODDS_10 => '10 Odds',
            self::TYPE_WEEKEND_PICKS => 'Top Weekend Picks',
            default => $this->type,
        };
    }

    public function strapline(): string
    {
        return match ($this->type) {
            self::TYPE_BANKER_2 => "Today's confident picks combined to at least 2.00 odds, choosing the combination most likely to land together.",
            self::TYPE_ODDS_10 => "Today's confident picks combined to at least 10.00 odds, choosing the combination most likely to land together.",
            self::TYPE_WEEKEND_PICKS => "The strongest individual reads across this weekend's fixtures.",
            default => '',
        };
    }

    /** The model's estimate that every leg lands (product of leg probabilities). */
    public function modelChance(): ?float
    {
        if ($this->type === self::TYPE_WEEKEND_PICKS || $this->legs->isEmpty()) {
            return null;
        }

        return $this->legs->reduce(fn ($c, Prediction $p) => $c * (float) $p->predicted_probability, 1.0);
    }

    public function isLive(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->legs->contains(fn (Prediction $p) => $p->kickoff && $p->kickoff->isPast());
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_WON => 'Won',
            self::STATUS_LOST => 'Lost',
            self::STATUS_VOID => 'Void',
            default => $this->isLive() ? 'In play' : 'Upcoming',
        };
    }

    /** won | lost | void | live | upcoming — used as a CSS modifier. */
    public function statusClass(): string
    {
        return $this->status === self::STATUS_PENDING ? ($this->isLive() ? 'live' : 'upcoming') : $this->status;
    }

    /** Per-leg state for the slip: won | lost | void | pending. */
    public static function legState(Prediction $p): string
    {
        if (! $p->settled_at) {
            return 'pending';
        }

        return $p->hit === null ? 'void' : ($p->hit ? 'won' : 'lost');
    }
}
