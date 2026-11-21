<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A prediction's membership in a bundle. Many-to-many on purpose: the same
// prediction can legitimately appear in more than one bundle on the same day
// (e.g. a fixture's best pick can be both a Banker 2 leg and part of the
// 10-Odds Combo).
return new class extends Migration
{
    public function up(): void
    {
        // Production (MySQL) has this table from an earlier run that created it and
        // then failed before Laravel recorded the migration, so every deploy since
        // stopped here (and never reached the later migrations or `artisan optimize`).
        // Skip when it already exists; the app only needs the table and its columns.
        if (Schema::hasTable('accumulator_bundle_legs')) {
            return;
        }

        Schema::create('accumulator_bundle_legs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accumulator_bundle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prediction_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['accumulator_bundle_id', 'prediction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accumulator_bundle_legs');
    }
};
