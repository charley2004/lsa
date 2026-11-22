<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Combos are frozen once their first leg kicks off (locked_at) and graded as
// their legs settle (status: pending | won | lost | void, settled_at), so a
// combo that landed can be shown as won instead of being rebuilt away.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accumulator_bundles', function (Blueprint $table) {
            if (! Schema::hasColumn('accumulator_bundles', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('combined_odds');
            }
            if (! Schema::hasColumn('accumulator_bundles', 'status')) {
                $table->string('status')->default('pending')->after('locked_at');
            }
            if (! Schema::hasColumn('accumulator_bundles', 'settled_at')) {
                $table->timestamp('settled_at')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('accumulator_bundles', function (Blueprint $table) {
            $table->dropColumn(['locked_at', 'status', 'settled_at']);
        });
    }
};
