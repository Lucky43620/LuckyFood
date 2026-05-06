<?php

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
        Schema::create('user_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('calories_goal')->default(2000);
            $table->unsignedSmallInteger('protein_goal')->default(150);
            $table->unsignedSmallInteger('carbs_goal')->default(250);
            $table->unsignedSmallInteger('fat_goal')->default(65);
            $table->unsignedSmallInteger('fiber_goal')->default(30);
            $table->unsignedTinyInteger('water_goal')->default(8);
            $table->decimal('weight_current', 5, 1)->nullable();
            $table->decimal('weight_goal', 5, 1)->nullable();
            $table->enum('activity_level', ['sedentary', 'light', 'moderate', 'active', 'very_active'])->default('moderate');
            $table->enum('goal_type', ['lose', 'maintain', 'gain'])->default('maintain');
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_goals');
    }
};
