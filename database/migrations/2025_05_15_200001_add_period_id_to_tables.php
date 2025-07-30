<?php

use App\Models\Period;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
  public function up()
  {
    Schema::table('employee_evaluations', function (Blueprint $table) {
      $table->foreignIdFor(Period::class)->constrained()->cascadeOnDelete();

      $table->dropUnique([
        'employee_id',
        'evaluation_id'
      ]);

      $table->unique([
        'employee_id',
        'evaluation_id',
        'period_id'
      ]);
    });

    Schema::table('employee_trainings', function (Blueprint $table) {
      $table->foreignIdFor(Period::class)->constrained()->cascadeOnDelete();

      $table->dropUnique([
        'employee_id',
        'training_id'
      ]);

      $table->unique([
        'employee_id',
        'training_id',
        'period_id'
      ]);
    });

    Schema::table('feedback', function (Blueprint $table) {
      $table->foreignIdFor(Period::class)->constrained()->cascadeOnDelete();

      $table->dropUnique([
        'employee_id'
      ]);

      $table->unique([
        'employee_id',
        'period_id'
      ]);
    });
  }

  public function down()
  {
    Schema::table('employee_evaluations', function (Blueprint $table) {
      $table->dropUnique([
        'employee_id',
        'evaluation_id',
        'period_id'
      ]);

      $table->unique([
        'employee_id',
        'evaluation_id'
      ]);

      $table->dropForeign(['period_id']);
      $table->dropColumn('period_id');
    });

    Schema::table('employee_trainings', function (Blueprint $table) {
      $table->dropUnique([
        'employee_id',
        'training_id',
        'period_id'
      ]);

      $table->unique([
        'employee_id',
        'training_id'
      ]);

      $table->dropForeign(['period_id']);
      $table->dropColumn('period_id');
    });

    Schema::table('feedback', function (Blueprint $table) {
      $table->dropUnique([
        'employee_id',
        'period_id'
      ]);

      $table->unique([
        'employee_id'
      ]);

      $table->dropForeign(['period_id']);
      $table->dropColumn('period_id');
    });
  }
};
