<?php

namespace Tests\Feature;

use App\Models\FavoriteFood;
use App\Models\FoodDiaryEntry;
use App\Models\Recipe;
use App\Models\User;
use App\Models\WeightEntry;
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

    public function test_food_diary_entry_can_be_updated_by_owner(): void
    {
        $user = User::factory()->create();
        $entry = FoodDiaryEntry::create([
            'user_id' => $user->id,
            'date' => '2026-05-01',
            'meal_type' => 'lunch',
            'food_id' => '1641',
            'food_name' => 'Poulet',
            'calories' => 165,
        ]);

        $this->actingAs($user)
            ->put("/journal/{$entry->id}", [
                'date' => '2026-05-02',
                'food_id' => '1641',
                'food_name' => 'Poulet grillé',
                'meal_type' => 'dinner',
                'calories' => 200,
                'protein' => 35,
                'carbs' => 1,
                'fat' => 5,
                'fiber' => 0,
                'quantity' => 1.5,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('food_diary_entries', [
            'id' => $entry->id,
            'date' => '2026-05-02',
            'meal_type' => 'dinner',
            'food_name' => 'Poulet grillé',
            'calories' => 200,
        ]);
    }

    public function test_yesterday_entries_can_be_repeated(): void
    {
        $user = User::factory()->create();
        FoodDiaryEntry::create([
            'user_id' => $user->id,
            'date' => '2026-05-01',
            'meal_type' => 'breakfast',
            'food_id' => 'banana',
            'food_name' => 'Banane',
            'calories' => 90,
        ]);

        $this->actingAs($user)
            ->post('/journal/repeter-hier', ['date' => '2026-05-02'])
            ->assertRedirect();

        $this->assertDatabaseHas('food_diary_entries', [
            'user_id' => $user->id,
            'date' => '2026-05-02',
            'food_id' => 'banana',
            'calories' => 90,
        ]);
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

    public function test_weight_update_creates_weight_history_entry(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/progression/poids', ['weight_current' => 74.5])
            ->assertRedirect();

        $this->assertDatabaseHas('weight_entries', [
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'weight' => 74.5,
        ]);

        $this->assertSame('74.5', WeightEntry::where('user_id', $user->id)->first()?->weight);
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

    public function test_recipe_totals_are_recomputed_server_side(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/recettes', [
                'name' => 'Total sécurisé',
                'servings' => 1,
                'prep_time' => 5,
                'is_public' => true,
                'total_calories' => 9999,
                'total_protein' => 999,
                'total_carbs' => 999,
                'total_fat' => 999,
                'ingredients' => [
                    [
                        'food_id' => 'egg',
                        'food_name' => 'Oeuf',
                        'quantity' => 100,
                        'unit' => 'g',
                        'calories' => 155,
                        'protein' => 13,
                        'carbs' => 1,
                        'fat' => 11,
                    ],
                ],
            ])
            ->assertRedirect('/recettes');

        $recipe = Recipe::where('user_id', $user->id)->firstOrFail();

        $this->assertSame(155, $recipe->total_calories);
        $this->assertSame('13.00', $recipe->total_protein);
        $this->assertTrue($recipe->is_public);
    }

    public function test_public_recipe_can_be_viewed_and_added_to_journal(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $recipe = Recipe::create([
            'user_id' => $owner->id,
            'name' => 'Publique',
            'servings' => 2,
            'prep_time' => 10,
            'is_public' => true,
            'tags' => [],
            'instructions' => [],
            'total_calories' => 400,
            'total_protein' => 40,
            'total_carbs' => 30,
            'total_fat' => 10,
        ]);

        $this->actingAs($other)
            ->get("/recettes/{$recipe->id}")
            ->assertOk();

        $this->actingAs($other)
            ->post("/recettes/{$recipe->id}/journal", [
                'date' => '2026-05-03',
                'meal_type' => 'lunch',
                'servings' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('food_diary_entries', [
            'user_id' => $other->id,
            'date' => '2026-05-03',
            'food_id' => 'recipe:'.$recipe->id,
            'calories' => 200,
        ]);
    }

    public function test_favorite_food_can_be_stored_and_removed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/favoris/aliments', [
                'food_id' => '1641',
                'food_name' => 'Poulet',
                'serving_description' => '100 g',
                'calories' => 165,
                'protein' => 31,
                'carbs' => 0,
                'fat' => 3.6,
            ])
            ->assertRedirect();

        $this->assertSame(1, FavoriteFood::where('user_id', $user->id)->count());

        $this->actingAs($user)
            ->delete('/favoris/aliments/1641')
            ->assertRedirect();

        $this->assertDatabaseMissing('favorite_foods', [
            'user_id' => $user->id,
            'food_id' => '1641',
        ]);
    }

    public function test_nutrition_data_can_be_exported_as_json(): void
    {
        $user = User::factory()->create();
        FoodDiaryEntry::create([
            'user_id' => $user->id,
            'date' => '2026-05-01',
            'meal_type' => 'lunch',
            'food_id' => '1641',
            'food_name' => 'Poulet',
            'calories' => 165,
        ]);

        $this->actingAs($user)
            ->get('/export/nutrition.json')
            ->assertOk()
            ->assertJsonPath('diary_entries.0.food_name', 'Poulet');
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
