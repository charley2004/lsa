<?php

use App\Models\Prediction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// A permanent, human-readable URL key per fixture — /analysis/{slug}, e.g.
// "man-city-vs-fulham-2026-11-21". Every market row for the same fixture
// shares one slug, so one fixture is one page (never one page per market).
// Set once when the row is created and never rewritten afterwards, so an
// indexed URL keeps working for good; see Prediction::booted().
return new class extends Migration
{
    public function up(): void
    {
        // Guarded so a half-finished earlier run can't block every later deploy.
        if (! Schema::hasColumn('predictions', 'slug')) {
            Schema::table('predictions', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('away')->index();
            });
        }

        // Backfill existing rows with the same rule new rows get.
        DB::table('predictions')->whereNull('slug')->orderBy('id')->select('id', 'home', 'away', 'kickoff')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('predictions')->where('id', $row->id)->update([
                        'slug' => Prediction::makeSlug($row->home, $row->away, $row->kickoff),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropColumn('slug');
        });
    }
};
