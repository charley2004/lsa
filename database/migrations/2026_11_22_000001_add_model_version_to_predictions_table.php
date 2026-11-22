<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Which algorithm model produced a pick ("v2-..." for the current engine, NULL for
// the previous model), so /results can show the current model's record as the
// headline and archive the previous one. prob_market is the bookmaker's fair
// (margin-removed) probability for the picked market, sent by engine v2.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('predictions', 'model_version')) {
            Schema::table('predictions', function (Blueprint $table) {
                $table->string('model_version', 64)->nullable()->after('source')->index();
            });
        }
        if (! Schema::hasColumn('predictions', 'prob_market')) {
            Schema::table('predictions', function (Blueprint $table) {
                $table->float('prob_market')->nullable()->after('predicted_probability');
            });
        }
    }

    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->dropIndex(['model_version']);
            $table->dropColumn(['model_version', 'prob_market']);
        });
    }
};
