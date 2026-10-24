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

    // h2h_note comes straight from the algorithm's internal
    // agent/h2h_consistency.py, written for a developer reading logs, not a
    // site visitor — e.g. "H2H Rangers win: 13/15 meetings — notable — 15
    // meetings is a small sample; treat as a fixture-specific lean, not a
    // calibrated pick like the global-skill markets." The market/hits/
    // meetings numbers are real and worth keeping; "calibrated pick",
    // "global-skill markets" etc. are internal engineering vocabulary that
    // reads as a glitch to a visitor, not a reason. Reconstructed here into
    // one plain sentence instead of just trimming the raw string.
    public function h2hNoteLabel(): ?string
    {
        if (! $this->h2h_note) {
            return null;
        }

        $note = preg_replace('/\s*\(p=[0-9.]+\)/', '', $this->h2h_note);

        if (preg_match('/^(?:H2H\s+)?(.+?):\s*(\d+)\/(\d+)\s*meetings\b/iu', $note, $m)) {
            $market = ucfirst(trim($m[1]));
            $hits = (int) $m[2];
            $meetings = (int) $m[3];

            $label = "{$market} — {$hits} of the last {$meetings} meetings between these two sides.";

            if ($meetings < 10) {
                $label .= ' A real pattern, but a small sample — a lean worth knowing, not a calibrated probability.';
            }

            return $label;
        }

        // Unrecognized shape — still show the (p-value-stripped) original
        // rather than hiding it outright.
        return trim((string) $note);
    }

    // football-data.co.uk's own league codes (country-prefix + division digit
    // for the "core" leagues, bare 3-letter country code for the single-division
    // "world leagues" set) — see algorithm/scan_today.py's team_country_prefixes()
    // for how the Python side uses the same codes. This is every code actually
    // present in the training data as of 2026-10-22; an unmapped future code
    // falls back to showing itself rather than hiding the league entirely.
    private const LEAGUE_NAMES = [
        'E0' => 'Premier League (England)', 'E1' => 'Championship (England)',
        'E2' => 'League One (England)', 'E3' => 'League Two (England)',
        'EC' => 'National League (England)',
        'SC0' => 'Premiership (Scotland)', 'SC1' => 'Championship (Scotland)',
        'SC2' => 'League One (Scotland)', 'SC3' => 'League Two (Scotland)',
        'D1' => 'Bundesliga (Germany)', 'D2' => '2. Bundesliga (Germany)',
        'SP1' => 'La Liga (Spain)', 'SP2' => 'La Liga 2 (Spain)',
        'I1' => 'Serie A (Italy)', 'I2' => 'Serie B (Italy)',
        'F1' => 'Ligue 1 (France)', 'F2' => 'Ligue 2 (France)',
        'N1' => 'Eredivisie (Netherlands)',
        'B1' => 'Pro League (Belgium)',
        'P1' => 'Primeira Liga (Portugal)',
        'T1' => 'Super Lig (Turkey)',
        'G1' => 'Super League (Greece)',
        'ARG' => 'Primera Division (Argentina)',
        'AUT' => 'Bundesliga (Austria)',
        'BRA' => 'Serie A (Brazil)',
        'CHN' => 'Super League (China)',
        'DNK' => 'Superliga (Denmark)',
        'FIN' => 'Veikkausliiga (Finland)',
        'IRL' => 'Premier Division (Ireland)',
        'JPN' => 'J1 League (Japan)',
        'MEX' => 'Liga MX (Mexico)',
        'NOR' => 'Eliteserien (Norway)',
        'POL' => 'Ekstraklasa (Poland)',
    ];

    public function leagueLabel(): string
    {
        if (! $this->league) {
            return 'Football';
        }

        return self::LEAGUE_NAMES[$this->league] ?? $this->league;
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
