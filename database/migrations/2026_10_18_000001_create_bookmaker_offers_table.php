<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A trackable affiliate link, not just a raw URL pasted into a template. Every
// click goes through /go/{offer} (see BookmakerOfferController) so clicks get
// counted before the redirect — real data on which placement/page is actually
// driving bookmaker signups, not just "we have links somewhere."
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookmaker_offers', function (Blueprint $table) {
            $table->id();
            $table->string('name');            // e.g. "Bet365", shown on the CTA button
            $table->string('slug')->unique();  // used in the /go/{slug} redirect URL
            $table->text('affiliate_url');     // the real tracked affiliate link from the bookmaker deal
            $table->string('cta_text')->default('Claim Offer');
            $table->string('offer_detail')->nullable(); // e.g. "Bet £10 Get £30 in Free Bets"
            $table->string('logo_path')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('click_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookmaker_offers');
    }
};
