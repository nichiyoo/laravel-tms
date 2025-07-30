<?php

namespace Database\Seeders;

use App\Models\Period;
use App\Enums\SemesterType;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PeriodSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    Period::create([
      'year' => now()->year,
      'semester' => SemesterType::ODD
    ]);

    Period::create([
      'year' => now()->year,
      'semester' => SemesterType::EVEN
    ]);
  }
}
