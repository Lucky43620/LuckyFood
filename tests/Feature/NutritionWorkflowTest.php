<?php

namespace Tests\Feature;

use App\Models\FoodDiaryEntry;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NutritionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_food_can_be_added_to_a_past_journal_date(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/journal', [
                'date' => '2026-05-01',
                'food_id' => '1641',
                'food_name' => 'Poulet',
                'meal_type' => 'lunch',
                'calories' => 165,
                'protein' => 31,
                'carbs' => 0,
                'fat' => 3.6,
                'serving_description' => '100 g',
                'quantity' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('food_diary_entries', [
            'user_id' => $user->id,
            'date' => '2026-05-01',
            'food_id' => '1641',
        ]);
    }

    public function test_food_diary_entries_cannot_be_deleted_by_another_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $entry = FoodDiaryEntry::create([
            'user_id' => $owner->id,
            'date' => '2026-05-01',
            'meal_type' => 'lunch',
            'food_id' => '1641',
            'food_name' => 'Poulet',
            'calories' => 165,
        ]);

        $this->actingAs($other)
            ->delete("/journal/{$entry->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('food_diary_entries', ['id' => $entry->id]);
    }

    public function test_water_goal_can_be_updated_from_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/tableau-de-bord', ['water' => 6])
            ->assertOk();

        $this->assertDatabaseHas('water_tracking', [
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'glasses' => 6,
        ]);
    }

    public function test_invalid_goals_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/objectifs')
            ->put('/objectifs', [
                'calories_goal' => 100,
                'protein_goal' => 150,
                'carbs_goal' => 250,
                'fat_goal' => 65,
                'activity_level' => 'invalid',
                'goal_type' => 'maintain',
            ])
            ->assertRedirect('/objectifs')
            ->assertSessionHasErrors(['calories_goal', 'activity_level']);
    }

    public function test_recipe_can_be_created_and_deleted_by_owner(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/recettes', [
                'name' => 'Poulet riz',
                'servings' => 2,
                'prep_time' => 20,
                'tags' => ['Haut en protéines'],
                'instructions' => ['Cuire le riz.', 'Griller le poulet.'],
                'total_calories' => 600,
                'total_protein' => 70,
                'total_carbs' => 60,
                'total_fat' => 12,
                'ingredients' => [
                    [
                        'food_id' => '1641',
                        'food_name' => 'Poulet',
                        'quantity' => 200,
                        'unit' => 'g',
                        'calories' => 330,
                        'protein' => 62,
                        'carbs' => 0,
                        'fat' => 7,
                    ],
                ],
            ])
            ->assertRedirect('/recettes');

        $recipe = Recipe::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(['Cuire le riz.', 'Griller le poulet.'], $recipe->instructions);
        $this->assertDatabaseHas('recipe_ingredients', [
            'recipe_id' => $recipe->id,
            'food_id' => '1641',
        ]);

        $this->actingAs($user)
            ->delete("/recettes/{$recipe->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('recipes', ['id' => $recipe->id]);
    }

    public function test_recipe_cannot_be_deleted_by_another_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $recipe = Recipe::create([
            'user_id' => $owner->id,
            'name' => 'Privée',
            'servings' => 1,
            'prep_time' => 10,
            'tags' => [],
            'instructions' => [],
            'total_calories' => 100,
            'total_protein' => 10,
            'total_carbs' => 10,
            'total_fat' => 2,
        ]);

        $this->actingAs($other)
            ->delete("/recettes/{$recipe->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('recipes', ['id' => $recipe->id]);
    }
}
