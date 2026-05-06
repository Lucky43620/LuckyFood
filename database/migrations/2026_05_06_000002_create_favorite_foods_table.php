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
        Schema::create('favorite_foods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('food_id');
            $table->string('food_name');
            $table->string('serving_description')->nullable();
            $table->unsignedSmallInteger('calories')->default(0);
            $table->decimal('protein', 6, 2)->default(0);
            $table->decimal('carbs', 6, 2)->default(0);
            $table->decimal('fat', 6, 2)->default(0);
            $table->decimal('fiber', 6, 2)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'food_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorite_foods');
    }
};
