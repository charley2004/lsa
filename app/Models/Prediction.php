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

    protected static function booted(): void
    {
        // The slug is fixed at creation and deliberately NOT recomputed on
        // update: once a fixture URL is live (and possibly indexed), it must
        // keep resolving even if a later sync edits the row.
        static::creating(function (Prediction $p) {
            if (empty($p->slug) && static::slugsEnabled()) {
                $p->slug = static::makeSlug($p->home, $p->away, $p->kickoff);
            }
        });
    }

    /**
     * Deploy safety net: if new code reaches a server before
     * `php artisan migrate` has added the slug column, writing a slug would
     * make every sync INSERT fail. Until the column exists, slugs are simply
     * skipped (no fixture pages, no links) and everything else keeps working.
     * Checked once per request.
     */
    public static function slugsEnabled(): bool
    {
        static $enabled = null;

        return $enabled ??= \Illuminate\Support\Facades\Schema::hasColumn('predictions', 'slug');
    }

    /**
     * "Man City", "Fulham", 2026-11-21 15:00 -> "man-city-vs-fulham-2026-11-21".
     * The date keeps repeat meetings between the same two sides (league + cup,
     * or next season) on separate pages instead of colliding.
     */
    public static function makeSlug(?string $home, ?string $away, $kickoff): string
    {
        // The date in the URL is the local (display-timezone) match date, so
        // it always agrees with the date printed on the page.
        $date = $kickoff
            ? \Illuminate\Support\Carbon::parse($kickoff, 'UTC')->setTimezone(static::displayTimezone())->format('Y-m-d')
            : 'tbc';

        return \Illuminate\Support\Str::slug("{$home} vs {$away}") . '-' . $date;
    }

    public static function displayTimezone(): string
    {
        return config('lsa.display_timezone', 'Africa/Lagos');
    }

    /** Kickoff converted to the visitor-facing timezone (storage stays UTC). */
    public function localKickoff(): ?\Illuminate\Support\Carbon
    {
        return $this->kickoff?->copy()->setTimezone(static::displayTimezone());
    }

    /** "19:45 WAT" */
    public function kickoffTime(): ?string
    {
        return $this->localKickoff()?->format('H:i T');
    }

    public function fixtureUrl(): ?string
    {
        return $this->slug ? route('analysis.show', $this->slug) : null;
    }

    /**
     * Quality gate for indexing a fixture page: it must carry real pre-match
     * data (projected goals or recent form), not just a market name and a
     * number. Rows synced only after settlement carry neither, and a page made
     * of nothing but a bare probability is thin content — those pages still
     * render for visitors but are marked noindex and left out of the sitemap.
     */
    public function scopeWithAnalysisData(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereNotNull('xg_home')
            ->orWhere(fn (Builder $f) => $f->whereNotNull('home_form')->where('home_form', '!=', '[]')));
    }

    public function hasAnalysisData(): bool
    {
        return $this->xg_home !== null || ! empty($this->home_form) || ! empty($this->away_form);
    }

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
     * Profitability and calibration, computed from every settled read.
     *
     * A hit rate on its own misleads: 66% at average odds of 1.50 loses money,
     * because those odds need a 67% hit rate just to break even. So this
     * also reports the return at the odds we showed (flat 1-unit stakes, for
     * priced reads only), the break-even hit rate those odds imply, a
     * per-market split, and calibration (did reads we called "70%" land
     * about 70% of the time?). Nothing is filtered or cherry-picked.
     */
    public static function performance(): array
    {
        $settled = static::settled()->whereNotNull('hit')
            ->get(['market', 'predicted_probability', 'odds', 'hit']);

        $priced = $settled->filter(fn ($p) => $p->odds !== null && $p->odds > 1);
        $units = $priced->sum(fn ($p) => $p->hit ? $p->odds - 1 : -1);

        $roiOf = function ($rows) {
            $rows = $rows->filter(fn ($p) => $p->odds !== null && $p->odds > 1);
            return $rows->count() ? $rows->sum(fn ($p) => $p->hit ? $p->odds - 1 : -1) / $rows->count() : null;
        };

        $markets = $settled->groupBy('market')
            ->map(fn ($rows, $market) => [
                'market' => $market,
                'n' => $rows->count(),
                'hit_rate' => $rows->where('hit', true)->count() / $rows->count(),
                'predicted' => $rows->avg('predicted_probability'),
                'priced' => $rows->filter(fn ($p) => $p->odds !== null)->count(),
                'roi' => $roiOf($rows),
            ])
            ->filter(fn ($m) => $m['n'] >= 5)
            ->sortByDesc('n')
            ->values();

        $buckets = collect([[0.5, 0.6], [0.6, 0.7], [0.7, 0.8], [0.8, 0.9], [0.9, 1.01]])
            ->map(function ($b) use ($settled) {
                $rows = $settled->filter(fn ($p) => $p->predicted_probability >= $b[0] && $p->predicted_probability < $b[1]);
                return [
                    'label' => (int) round($b[0] * 100) . '–' . min(100, (int) round($b[1] * 100)) . '%',
                    'n' => $rows->count(),
                    'predicted' => $rows->count() ? $rows->avg('predicted_probability') : null,
                    'actual' => $rows->count() ? $rows->where('hit', true)->count() / $rows->count() : null,
                ];
            })
            ->filter(fn ($b) => $b['n'] > 0)
            ->values();

        return [
            'n' => $settled->count(),
            'hit_rate' => $settled->count() ? $settled->where('hit', true)->count() / $settled->count() : null,
            'n_priced' => $priced->count(),
            'priced_hit_rate' => $priced->count() ? $priced->where('hit', true)->count() / $priced->count() : null,
            'avg_odds' => $priced->count() ? $priced->avg('odds') : null,
            'breakeven' => $priced->count() ? $priced->avg(fn ($p) => 1 / $p->odds) : null,
            'units' => $units,
            'roi' => $priced->count() ? $units / $priced->count() : null,
            'markets' => $markets,
            'calibration' => $buckets,
        ];
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
