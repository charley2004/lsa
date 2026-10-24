<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// One row per published bundle (Banker 2, the 10-Odds Combo, Top Weekend
// Picks). Rebuilt from scratch on every sync (see BundleBuilder) rather than
// edited in place, so `date` + `type` together identify "today's Banker 2"
// uniquely and a rebuild just replaces whatever was there.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accumulator_bundles', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // banker_2 | odds_10 | weekend_picks
            $table->date('date'); // the day this bundle is "for"
            $table->float('combined_odds')->nullable(); // null for weekend_picks (a list, not a combo)
            $table->timestamps();

            $table->unique(['type', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accumulator_bundles');
    }
};
