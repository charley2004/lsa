<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Accumulated visible-seconds per [data-track-section] element per page view
// — how long each section actually held attention, not just that the page
// was open. One row per (page_view, section); seconds accumulate across the
// periodic flushes a single page view sends (see AnalyticsController::flush).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_section_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_view_id')->constrained()->cascadeOnDelete();
            $table->string('section', 64);
            $table->unsignedInteger('seconds')->default(0);
            $table->timestamps();

            $table->unique(['page_view_id', 'section']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_section_views');
    }
};
