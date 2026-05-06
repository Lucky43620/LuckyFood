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
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('servings')->default(1);
            $table->unsignedSmallInteger('prep_time')->default(0);
            $table->string('category')->nullable();
            $table->json('tags')->nullable();
            $table->json('instructions')->nullable();
            $table->boolean('is_public')->default(false);
            $table->unsignedSmallInteger('total_calories')->default(0);
            $table->decimal('total_protein', 6, 2)->default(0);
            $table->decimal('total_carbs', 6, 2)->default(0);
            $table->decimal('total_fat', 6, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
