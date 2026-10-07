<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Mirrors agent/ledger.py's `predictions` table (the algorithm's own track
// record) plus a handful of denormalized presentation fields — league name,
// recent-form pills, projected xG, the H2H note — so this app only ever reads
// pre-computed data and never has to call back into the Python/LightGBM side
// per request. The algorithm pipeline is the one writing those extra columns
// at prediction-log time; this app is a pure read/display + admin layer on
// top of the same row.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->timestamp('logged_at');
            $table->string('home');
            $table->string('away');
            $table->timestamp('kickoff')->nullable();
            $table->string('market');
            $table->float('predicted_probability');
            $table->enum('verdict', ['confident_pick', 'lean', 'no_confident_pick']);
            $table->float('conviction')->nullable();
            $table->float('skill')->nullable();
            $table->string('source')->default('scan_today'); // e.g. 'scan_today', 'chat_agent'

            // Settlement (filled in once the real result is known)
            $table->timestamp('settled_at')->nullable();
            $table->integer('home_goals')->nullable();
            $table->integer('away_goals')->nullable();
            $table->integer('home_corners')->nullable();
            $table->integer('away_corners')->nullable();
            $table->integer('home_cards')->nullable();
            $table->integer('away_cards')->nullable();
            $table->boolean('hit')->nullable(); // null until settled

            // Denormalized presentation data for the analysis card — written once
            // at log time by the algorithm, never recomputed here.
            $table->string('league')->nullable();
            $table->float('xg_home')->nullable();
            $table->float('xg_away')->nullable();
            $table->json('home_form')->nullable(); // e.g. ["W","W","D","W","L"]
            $table->json('away_form')->nullable();
            $table->text('h2h_note')->nullable();

            $table->timestamps();

            $table->index('settled_at');
            $table->index('kickoff');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};
