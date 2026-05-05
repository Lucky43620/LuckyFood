<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FatSecretService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Tests\TestCase;

class FoodSearchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_uses_user_fatsecret_locale_preferences(): void
    {
        $user = User::factory()->create([
            'fatsecret_region' => 'BE',
            'fatsecret_language' => 'fr',
        ]);

        $fatSecret = Mockery::mock(FatSecretService::class);
        $fatSecret
            ->shouldReceive('searchFoodsPage')
            ->once()
            ->with('poulet', 0, 20, 'BE', 'fr')
            ->andReturn([
                'results' => [
                    [
                        'food_id' => '1',
                        'food_name' => 'Poulet',
                        'brand_name' => null,
                        'calories' => 165,
                        'protein' => 31.0,
                        'carbs' => 0.0,
                        'fat' => 3.6,
                        'serving_description' => '100 g',
                    ],
                ],
                'pagination' => $this->pagination(total: 1),
                'effective_query' => 'chicken',
            ]);
        $fatSecret
            ->shouldReceive('lastError')
            ->once()
            ->andReturn(null);

        $this->app->instance(FatSecretService::class, $fatSecret);

        $this->actingAs($user)
            ->get('/recherche?q=poulet&meal=lunch')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Search')
                ->where('query', 'poulet')
                ->where('meal', 'lunch')
                ->where('pagination.total', 1)
                ->where('effectiveQuery', 'chicken')
                ->where('searchError', null)
                ->has('results', 1));
    }

    public function test_search_shows_fatsecret_api_error_without_crashing(): void
    {
        $user = User::factory()->create([
            'fatsecret_region' => 'FR',
            'fatsecret_language' => 'fr',
        ]);

        $fatSecret = Mockery::mock(FatSecretService::class);
        $fatSecret
            ->shouldReceive('searchFoodsPage')
            ->once()
            ->with('poulet', 0, 20, 'FR', 'fr')
            ->andReturn([
                'results' => [],
                'pagination' => $this->pagination(total: 0),
                'effective_query' => '',
            ]);
        $fatSecret
            ->shouldReceive('lastError')
            ->once()
            ->andReturn([
                'operation' => 'search.oauth1',
                'status' => 200,
                'code' => '8',
                'message' => 'Invalid signature: please refer to the documentation',
                'ip' => null,
            ]);

        $this->app->instance(FatSecretService::class, $fatSecret);

        $this->actingAs($user)
            ->get('/recherche?q=poulet')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Search')
                ->where('query', 'poulet')
                ->where('meal', 'breakfast')
                ->where('searchError.code', 8)
                ->where('searchError.ip', null)
                ->where('searchError.message', 'FatSecret refuse la signature OAuth1. Verifiez le Consumer Secret OAuth1.')
                ->has('results', 0));
    }

    public function test_food_detail_uses_user_fatsecret_locale_preferences(): void
    {
        $user = User::factory()->create([
            'fatsecret_region' => 'FR',
            'fatsecret_language' => 'fr',
        ]);

        $fatSecret = Mockery::mock(FatSecretService::class);
        $fatSecret
            ->shouldReceive('getFood')
            ->once()
            ->with('1641', 'FR', 'fr')
            ->andReturn([
                'food_id' => '1641',
                'food_name' => 'Blanc de Poulet',
                'servings' => [
                    'serving' => [
                        [
                            'serving_id' => '1',
                            'serving_description' => '100 g',
                            'calories' => 165,
                        ],
                    ],
                ],
            ]);
        $fatSecret
            ->shouldReceive('lastError')
            ->once()
            ->andReturn(null);

        $this->app->instance(FatSecretService::class, $fatSecret);

        $this->actingAs($user)
            ->get('/recherche/aliment/1641?q=poulet&meal=lunch')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Search/Show')
                ->where('food.food_id', '1641')
                ->where('meal', 'lunch')
                ->where('query', 'poulet')
                ->where('searchError', null));
    }

    private function pagination(int $total): array
    {
        return [
            'page' => 0,
            'per_page' => 20,
            'total' => $total,
            'total_pages' => $total > 0 ? 1 : 0,
            'from' => $total > 0 ? 1 : 0,
            'to' => $total,
            'has_previous' => false,
            'has_next' => false,
        ];
    }
}
