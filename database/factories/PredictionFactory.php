<?php

namespace Database\Factories;

use App\Models\Prediction;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Prediction> */
class PredictionFactory extends Factory
{
    protected $model = Prediction::class;

    public function definition(): array
    {
        return [
            'logged_at' => now(),
            'home' => $this->faker->unique()->city(),
            'away' => $this->faker->unique()->city(),
            'kickoff' => now()->addDay(),
            'market' => 'Over 2.5 goals',
            'predicted_probability' => 0.64,
            'verdict' => 'lean',
            'conviction' => 0.5,
            'skill' => 0.03,
            'source' => 'factory',
            'league' => 'Test League',
        ];
    }

    public function settled(bool $hit = true): static
    {
        return $this->state(fn () => [
            'kickoff' => now()->subDay(),
            'settled_at' => now(),
            'home_goals' => $hit ? 3 : 0,
            'away_goals' => $hit ? 1 : 0,
            'hit' => $hit,
        ]);
    }
}
