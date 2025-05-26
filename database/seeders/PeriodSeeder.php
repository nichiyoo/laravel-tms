<?php

namespace Database\Seeders;

use App\Enums\SemesterType;
use App\Models\Period;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PeriodSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    Period::create([
      'year' => 2025,
      'semester' => SemesterType::ODD
    ]);

    Period::create([
      'year' => 2025,
      'semester' => SemesterType::EVEN
    ]);
  }
}
