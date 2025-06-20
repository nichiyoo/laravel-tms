<?php

use App\Models\Employee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('feedback', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->integer('teamwork')->default(0);
      $table->integer('communication')->default(0);
      $table->integer('initiative')->default(0);
      $table->integer('problem_solving')->default(0);
      $table->integer('adaptability')->default(0);
      $table->integer('talent')->default(0);
      $table->text('description');
      $table->foreignIdFor(Employee::class)->constrained()->cascadeOnDelete()->unique();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('feedback');
  }
};
