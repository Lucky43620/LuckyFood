<?php

namespace Tests\Unit;

use App\Http\Integrations\FatSecret\Requests\GetFoodRequest;
use App\Http\Integrations\FatSecret\Requests\AutocompleteRequest;
use App\Http\Integrations\FatSecret\Requests\GetFoodPremierRequest;
use App\Http\Integrations\FatSecret\Requests\SearchFoodsPremierRequest;
use App\Http\Integrations\FatSecret\Requests\SearchFoodsRequest;
use App\Services\FatSecretService;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\TestCase;

class FatSecretServiceTest extends TestCase
{
    public function test_search_foods_normalizes_server_api_description_results(): void
    {
        MockClient::global([
            AutocompleteRequest::class => $this->missingPremierResponse(),
            SearchFoodsPremierRequest::class => $this->missingPremierResponse(),
            SearchFoodsRequest::class => MockResponse::make([
                'foods' => [
                    'food' => [
                        'food_id' => '36421',
                        'food_name' => 'Mushrooms',
                        'food_description' => 'Per 100g - Calories: 22kcal | Fat: 0.34g | Carbs: 3.28g | Protein: 3.09g',
                    ],
                ],
            ]),
        ]);

        $service = $this->service();
        $results = $service->searchFoods('mushrooms');

        $this->assertSame('36421', $results[0]['food_id']);
        $this->assertSame(22, $results[0]['calories']);
        $this->assertSame(3.09, $results[0]['protein']);
        $this->assertSame(3.28, $results[0]['carbs']);
        $this->assertSame(0.34, $results[0]['fat']);
        $this->assertSame('Pour 100g', $results[0]['serving_description']);
        $this->assertNull($service->lastError());
    }

    public function test_search_foods_page_returns_pagination_and_effective_query(): void
    {
        MockClient::global([
            SearchFoodsPremierRequest::class => MockResponse::make([
                'foods_search' => [
                    'results' => [
                        'food' => [
                            [
                                'food_id' => '1641',
                                'food_name' => 'Chicken Breast',
                                'food_type' => 'Generic',
                                'food_description' => 'Per 100g - Calories: 165kcal | Fat: 3.60g | Carbs: 0.00g | Protein: 31.00g',
                                'servings' => [
                                    'serving' => [
                                        'serving_id' => '50321',
                                        'serving_description' => '100 g',
                                        'calories' => '195',
                                        'carbohydrate' => '0',
                                        'protein' => '29.55',
                                        'fat' => '7.72',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'max_results' => '20',
                    'page_number' => '0',
                    'total_results' => '1999',
                ],
            ]),
        ]);

        $page = $this->service()->searchFoodsPage('poulet');

        $this->assertSame('Chicken Breast', $page['results'][0]['food_name']);
        $this->assertSame('Chicken Breast', $page['results'][0]['original_food_name']);
        $this->assertSame('', $page['effective_query']);
        $this->assertSame(1999, $page['pagination']['total']);
        $this->assertSame(1, $page['pagination']['from']);
        $this->assertSame(1, $page['pagination']['to']);
        $this->assertTrue($page['pagination']['has_next']);
    }

    public function test_search_foods_falls_back_to_basic_with_effective_query(): void
    {
        MockClient::global([
            SearchFoodsPremierRequest::class => $this->missingPremierResponse(),
            SearchFoodsRequest::class => MockResponse::make([
                'foods' => [
                    'food' => [
                        [
                            'food_id' => '1641',
                            'food_name' => 'Chicken Breast',
                            'food_type' => 'Generic',
                            'food_description' => 'Per 100g - Calories: 165kcal | Fat: 3.60g | Carbs: 0.00g | Protein: 31.00g',
                        ],
                    ],
                    'max_results' => '20',
                    'page_number' => '0',
                    'total_results' => '1999',
                ],
            ]),
        ]);

        $page = $this->service()->searchFoodsPage('poulet');

        $this->assertSame('Blanc de Poulet', $page['results'][0]['food_name']);
        $this->assertSame('Chicken Breast', $page['results'][0]['original_food_name']);
        $this->assertSame('chicken', $page['effective_query']);
        $this->assertSame(1999, $page['pagination']['total']);
        $this->assertSame(1, $page['pagination']['from']);
        $this->assertSame(1, $page['pagination']['to']);
        $this->assertTrue($page['pagination']['has_next']);
    }

    public function test_autocomplete_uses_search_results_as_suggestions(): void
    {
        MockClient::global([
            AutocompleteRequest::class => $this->missingPremierResponse(),
            SearchFoodsRequest::class => MockResponse::make([
                'foods' => [
                    'food' => [
                        [
                            'food_id' => '1',
                            'food_name' => 'Chicken',
                            'food_description' => 'Per 100g - Calories: 165kcal | Fat: 3.60g | Carbs: 0.00g | Protein: 31.00g',
                        ],
                        [
                            'food_id' => '2',
                            'food_name' => 'Chicken Breast',
                            'food_description' => 'Per 100g - Calories: 165kcal | Fat: 3.60g | Carbs: 0.00g | Protein: 31.00g',
                        ],
                    ],
                ],
            ]),
        ]);

        $this->assertSame(
            ['Poulet', 'Blanc de Poulet'],
            $this->service()->autocomplete('chi', maxResults: 2),
        );
    }

    public function test_get_food_normalizes_serving_list(): void
    {
        MockClient::global([
            GetFoodPremierRequest::class => $this->missingPremierResponse(),
            GetFoodRequest::class => MockResponse::make([
                'food' => [
                    'food_id' => '1641',
                    'food_name' => 'Chicken Breast',
                    'servings' => [
                        'serving' => [
                            'serving_id' => '1',
                            'serving_description' => '100 g',
                        ],
                    ],
                ],
            ]),
        ]);

        $food = $this->service()->getFood('1641');

        $this->assertSame('1641', $food['food_id']);
        $this->assertIsList($food['servings']['serving']);
        $this->assertSame('100 g', $food['servings']['serving'][0]['serving_description']);
    }

    public function test_search_foods_returns_empty_array_on_api_failure(): void
    {
        MockClient::global([
            SearchFoodsPremierRequest::class => $this->missingPremierResponse(),
            SearchFoodsRequest::class => MockResponse::make([
                'error' => [
                    'code' => '8',
                    'message' => 'Invalid signature: please refer to the documentation',
                ],
            ]),
        ]);

        $service = $this->service();

        $this->assertSame([], $service->searchFoods('chicken'));
        $this->assertSame('8', $service->lastError()['code']);
        $this->assertSame('Invalid signature: please refer to the documentation', $service->lastError()['message']);
    }

    public function test_missing_oauth1_credentials_are_handled(): void
    {
        $service = new FatSecretService();

        $this->assertSame([], $service->searchFoods('chicken'));
        $this->assertSame('FatSecret OAuth1 credentials are missing.', $service->lastError()['message']);
    }

    private function service(): FatSecretService
    {
        return new FatSecretService(
            consumerKey: 'consumer-key',
            consumerSecret: 'consumer-secret',
        );
    }

    private function missingPremierResponse(): MockResponse
    {
        return MockResponse::make([
            'error' => [
                'code' => '14',
                'message' => 'Missing scope: premier',
            ],
        ]);
    }
}
