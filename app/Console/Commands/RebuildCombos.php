<?php

namespace App\Console\Commands;

use App\Http\Middleware\CachePublicPage;
use App\Models\AccumulatorBundle;
use App\Services\BundleBuilder;
use Illuminate\Console\Command;

/**
 * Rebuilds today's and tomorrow's Banker 2 Odds / 10 Odds slips and grades
 * finished ones, the same as the end of every sync. Run by deploy.sh so new
 * combo logic applies immediately instead of at the next pipeline sync.
 */
class RebuildCombos extends Command
{
    protected $signature = 'lsa:combos';

    protected $description = "Rebuild today's and tomorrow's combos and grade finished ones";

    public function handle(BundleBuilder $builder): int
    {
        $builder->rebuild();
        CachePublicPage::flush();

        foreach (AccumulatorBundle::with('legs')->upcoming()->whereIn('type', [AccumulatorBundle::TYPE_BANKER_2, AccumulatorBundle::TYPE_ODDS_10])->get() as $b) {
            $this->line(sprintf('%s  %-14s %6.2f odds  %d legs  %s', $b->date->toDateString(), $b->label(), $b->combined_odds, $b->legs->count(), $b->statusLabel()));
        }

        return self::SUCCESS;
    }
}
