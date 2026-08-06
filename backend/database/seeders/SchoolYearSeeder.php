<?php

namespace Database\Seeders;

use App\Models\SchoolYear;
use Illuminate\Database\Seeder;

class SchoolYearSeeder extends Seeder
{
    public function run(): void
    {
        SchoolYear::updateOrCreate(
            ['year' => 2026],
            ['starts_at' => '2026-02-02', 'ends_at' => '2026-12-18', 'status' => 'active']
        );
    }
}
