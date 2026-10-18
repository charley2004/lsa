<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// One row per page load. session_id is a random id generated client-side
// (public/js/analytics.js) and never tied to an identity — no IP address or
// raw user-agent is ever stored, just enough to answer "which pages get
// visited, by roughly how many distinct visitors, on what device."
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 64)->index();
            $table->string('path', 255)->index();
            $table->string('referrer', 512)->nullable();
            $table->string('device', 16)->nullable(); // mobile|tablet|desktop
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
