<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The real bookmaker price for the picked market at log time. Only ever
// populated by the algorithm for "H", "A", and "over_2.5" markets — the only
// ones it currently has a scraped odds source for (see algorithm/scan_today.py
// _odds_for_market). NULL for every other market, never a derived/estimated
// number — a displayed odds must be a real price or nothing.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->float('odds')->nullable()->after('predicted_probability');
        });
    }

    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->dropColumn('odds');
        });
    }
};
