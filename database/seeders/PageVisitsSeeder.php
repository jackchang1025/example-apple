<?php

namespace Database\Seeders;

use App\Models\PageVisits;
use Illuminate\Database\Seeder;

class PageVisitsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PageVisits::query()->delete();

        PageVisits::factory()->count(1000)->create();

        PageVisits::factory()->count(1000)->chineseCity()->create();
    }
}
