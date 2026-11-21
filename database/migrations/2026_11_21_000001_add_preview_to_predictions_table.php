<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Short match preview written by the algorithm's LLM step (algorithm/agent/llm.py)
// from the model's own numbers plus the latest team news. Presentation only:
// it never changes the pick, the probability or the odds. NULL when the LLM
// step was off or failed.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->text('preview')->nullable()->after('h2h_note');
        });
    }

    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->dropColumn('preview');
        });
    }
};
