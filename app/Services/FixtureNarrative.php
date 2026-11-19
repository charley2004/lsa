<?php

namespace App\Services;

use App\Models\Prediction;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Turns a fixture's real stored numbers (form, projected goals, the model's
 * reads, the final score) into a short plain-English summary and a meta
 * description for its page.
 *
 * Every sentence is built only from fields the algorithm actually synced —
 * nothing here invents team news, injuries or opinions. A field that is
 * missing simply drops its sentence, so a sparse fixture gets a short summary
 * rather than a padded one.
 */
class FixtureNarrative
{
    /** @return string[] sentences, in reading order */
    public function summary(Prediction $fixture, Collection $reads): array
    {
        $home = $fixture->home;
        $away = $fixture->away;
        $sentences = [];

        $league = $fixture->leagueLabel();
        $where = $league !== 'Football' ? " in the {$league}" : '';
        $when = $fixture->kickoff ? ' on ' . $fixture->localKickoff()->format('l j F Y') : '';
        $verb = $fixture->kickoff && $fixture->kickoff->isPast() ? 'hosted' : 'host';
        $sentences[] = "{$home} {$verb} {$away}{$where}{$when}.";

        $homeForm = $this->formPhrase($fixture->home_form ?? []);
        $awayForm = $this->formPhrase($fixture->away_form ?? []);
        if ($homeForm && $awayForm) {
            $sentences[] = "Going into the match, {$home} had {$homeForm}, while {$away} had {$awayForm}.";
        } elseif ($homeForm) {
            $sentences[] = "Going into the match, {$home} had {$homeForm}.";
        } elseif ($awayForm) {
            $sentences[] = "Going into the match, {$away} had {$awayForm}.";
        }

        if ($fixture->xg_home !== null && $fixture->xg_away !== null) {
            $xh = (float) $fixture->xg_home;
            $xa = (float) $fixture->xg_away;
            $diff = $xh - $xa;
            $lean = match (true) {
                $diff >= 0.3 => "{$home} as the side more likely to create the better chances",
                $diff <= -0.3 => "{$away} as the side more likely to create the better chances",
                default => 'a closely balanced game',
            };
            $sentences[] = sprintf(
                'Our model projects %s expected goals (xG) for %s and %s for %s, pointing to %s.',
                number_format($xh, 2), $home, number_format($xa, 2), $away, $lean
            );
        }

        $top = $reads->first();
        if ($top) {
            $sentences[] = sprintf(
                "The model's strongest read is %s at a %s%% calibrated probability, rated %s.",
                $top->market,
                number_format(($top->predicted_probability ?? 0) * 100, 1),
                Str::lower($top->verdictLabel())
            );
        }

        $settled = $reads->first(fn ($p) => $p->home_goals !== null && $p->away_goals !== null);
        if ($settled) {
            $sentences[] = "Final score: {$home} {$settled->home_goals}–{$settled->away_goals} {$away}.";
        }

        return $sentences;
    }

    public function metaDescription(Prediction $fixture, Collection $reads): string
    {
        $parts = ["{$fixture->home} vs {$fixture->away} preview:"];

        if ($fixture->xg_home !== null && $fixture->xg_away !== null) {
            $parts[] = sprintf('projected goals %s–%s xG, recent form, head-to-head and our model\'s read.',
                number_format((float) $fixture->xg_home, 2), number_format((float) $fixture->xg_away, 2));
        } else {
            $parts[] = "recent form, head-to-head and our model's read.";
        }

        $settled = $reads->first(fn ($p) => $p->home_goals !== null && $p->away_goals !== null);
        if ($settled) {
            $parts[] = "Final score {$settled->home_goals}–{$settled->away_goals}.";
        }

        $parts[] = 'Educational analysis, 18+.';

        return Str::limit(implode(' ', $parts), 160, '…');
    }

    /** ['W','W','D','L','W'] -> "3 wins, 1 draw and 1 loss in their last five" */
    private function formPhrase(array $form): ?string
    {
        if (! $form) {
            return null;
        }

        $counts = array_count_values($form);
        $bits = [];
        foreach (['W' => 'win', 'D' => 'draw', 'L' => 'loss'] as $code => $word) {
            $n = $counts[$code] ?? 0;
            if ($n > 0) {
                $bits[] = $n . ' ' . ($n === 1 ? $word : ($word === 'loss' ? 'losses' : $word . 's'));
            }
        }
        if (! $bits) {
            return null;
        }

        $last = array_pop($bits);
        $list = $bits ? implode(', ', $bits) . ' and ' . $last : $last;
        $numbers = [1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six'];

        return $list . ' in their last ' . ($numbers[count($form)] ?? count($form));
    }
}
