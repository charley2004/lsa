<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prediction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// The one write path from the algorithm into this app. Deliberately a single
// upsert endpoint for BOTH posting new predictions and pushing settlement
// updates — the algorithm just calls this again with the same fixture once a
// result is known, and whichever row already exists gets its hit/goals/etc.
// filled in live rather than a duplicate appearing. That's what makes
// "results update live" work without a separate settlement endpoint.
//
// Match key is (home, away, market, kickoff) — not an id from either side,
// since the two systems don't share one. Good enough: the algorithm never
// logs the same fixture+market twice for the same kickoff (see
// agent/ledger.py's dedupe in scan_today.py).
class PredictionSyncController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'predictions' => 'required|array',
            'predictions.*.home' => 'required|string',
            'predictions.*.away' => 'required|string',
            'predictions.*.kickoff' => 'nullable|date',
            'predictions.*.market' => 'required|string',
            'predictions.*.predicted_probability' => 'required|numeric',
            'predictions.*.verdict' => 'required|in:confident_pick,lean,no_confident_pick',
            'predictions.*.conviction' => 'nullable|numeric',
            'predictions.*.skill' => 'nullable|numeric',
            'predictions.*.source' => 'nullable|string',
            'predictions.*.logged_at' => 'nullable|date',
            'predictions.*.settled_at' => 'nullable|date',
            'predictions.*.home_goals' => 'nullable|integer',
            'predictions.*.away_goals' => 'nullable|integer',
            'predictions.*.home_corners' => 'nullable|integer',
            'predictions.*.away_corners' => 'nullable|integer',
            'predictions.*.home_cards' => 'nullable|integer',
            'predictions.*.away_cards' => 'nullable|integer',
            'predictions.*.hit' => 'nullable|boolean',
            'predictions.*.league' => 'nullable|string',
            'predictions.*.xg_home' => 'nullable|numeric',
            'predictions.*.xg_away' => 'nullable|numeric',
            'predictions.*.home_form' => 'nullable|array',
            'predictions.*.away_form' => 'nullable|array',
            'predictions.*.h2h_note' => 'nullable|string',
        ]);

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($data, &$created, &$updated) {
            foreach ($data['predictions'] as $row) {
                unset($row['id']); // the algorithm's own ledger id — not ours, not stored

                $query = Prediction::where('home', $row['home'])
                    ->where('away', $row['away'])
                    ->where('market', $row['market']);

                if (! empty($row['kickoff'])) {
                    // Eloquent's 'datetime' cast on Prediction::$casts only normalizes
                    // values going THROUGH the model (create/update) — a raw
                    // query-builder where() does not, so comparing the incoming
                    // "2026-09-20T15:15" against the stored "2026-09-20 15:15:00"
                    // as plain strings never matched. Every sync run was silently
                    // creating a new row instead of updating the existing one —
                    // found via real duplicate rows (3x each) after 3 sync attempts.
                    $query->where('kickoff', Carbon::parse($row['kickoff'])->format('Y-m-d H:i:s'));
                }

                $prediction = $query->first();

                if ($prediction) {
                    $prediction->update($row);
                    $updated++;
                } else {
                    Prediction::create($row + ['logged_at' => $row['logged_at'] ?? now()]);
                    $created++;
                }
            }
        });

        return response()->json(['created' => $created, 'updated' => $updated]);
    }
}
