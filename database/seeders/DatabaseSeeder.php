<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // PredictionSeeder used to seed fabricated verdict/probability/skill values
        // (every row hardcoded to 'confident_pick') on top of real scores — removed
        // after it was found live on the site, contradicting the real analysis.
        // Real predictions now only ever arrive via sync_to_website.py -> api/sync/predictions.
    }
}
