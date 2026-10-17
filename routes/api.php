<?php

use App\Http\Controllers\Api\PredictionSyncController;
use Illuminate\Support\Facades\Route;

// The only inbound write path from the algorithm side. Everything else this
// app does is read-only display (see routes/web.php). Guarded by a shared
// secret, not a user session — see app/Http/Middleware/VerifySyncKey.php.
Route::post('/sync/predictions', [PredictionSyncController::class, 'store'])
    ->middleware('verify.sync-key')
    ->name('api.sync.predictions');

// One-time maintenance action — see PredictionSyncController::fixKickoffTimezone.
Route::post('/sync/fix-kickoff-timezone', [PredictionSyncController::class, 'fixKickoffTimezone'])
    ->middleware('verify.sync-key')
    ->name('api.sync.fix-kickoff-timezone');
