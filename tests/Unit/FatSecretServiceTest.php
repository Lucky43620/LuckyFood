<?php

namespace Tests\Unit;

use App\Http\Integrations\FatSecret\Requests\AutocompleteRequest;
use App\Http\Integrations\FatSecret\Requests\GetFoodCategoriesRequest;
use App\Http\Integrations\FatSecret\Requests\GetFoodPremierRequest;
use App\Http\Integrations\FatSecret\Requests\SearchByBarcodeRequest;
use App\Http\Integrations\FatSecret\Requests\SearchFoodsPremierRequest;
use App\Services\FatSecretService;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\TestCase;

class FatSecretServiceTest extends TestCase
{
    public function test_search_foods_premier_normalizes_serving_results(): void
    {
        MockClient::global([
            SearchFoodsPremierRequest::class => MockResponse::make([
                'foods_search' => [
                    'results' => [
                        'food' => [
                            [
                                'food_id' => '36421',
                                'food_name' => 'Champignons',
                                'food_type' => 'Generic',
                                'servings' => [
                                    'serving' => [
                                        'serving_description' => '100 g',
                                        'calories' => '22',
                                        'carbohydrate' => '3.28',
                                        'protein' => '3.09',
                                        'fat' => '0.34',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'max_results' => '20',
                    'page_number' => '0',
                    'total_results' => '1',
                ],
            ]),
        ]);

        $service = $this->service();
        $results = $service->searchFoods('champignons');

        $this->assertSame('36421', $results[0]['food_id']);
        $this->assertSame('Champignons', $results[0]['food_name']);
        $this->assertSame(22, $results[0]['calories']);
        $this->assertSame(3.09, $results[0]['protein']);
        $this->assertSame(3.28, $results[0]['carbs']);
        $this->assertSame(0.34, $results[0]['fat']);
        $this->assertSame('100 g', $results[0]['serving_description']);
        $this->assertNull($service->lastError());
    }

    public function test_search_foods_page_returns_pagination(): void
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
        $this->assertSame('', $page['effective_query']);
        $this->assertSame(1999, $page['pagination']['total']);
        $this->assertSame(1, $page['pagination']['from']);
        $this->assertSame(1, $page['pagination']['to']);
        $this->assertTrue($page['pagination']['has_next']);
    }

    public function test_search_foods_returns_empty_on_api_failure(): void
    {
        MockClient::global([
            SearchFoodsPremierRequest::class => MockResponse::make([
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

    public function test_autocomplete_returns_suggestions_from_premier(): void
    {
        MockClient::global([
            AutocompleteRequest::class => MockResponse::make([
                'suggestions' => [
                    'suggestion' => ['poulet', 'poulet grillé', 'poulet rôti'],
                ],
            ]),
        ]);

        $suggestions = $this->service()->autocomplete('pou', maxResults: 3);

        $this->assertSame(['poulet', 'poulet grillé', 'poulet rôti'], $suggestions);
    }

    public function test_autocomplete_returns_empty_on_api_failure(): void
    {
        MockClient::global([
            AutocompleteRequest::class => MockResponse::make([
                'error' => ['code' => '5', 'message' => 'Invalid consumer key'],
            ]),
        ]);

        $this->assertSame([], $this->service()->autocomplete('pou'));
    }

    public function test_get_food_premier_normalizes_serving_list(): void
    {
        MockClient::global([
            GetFoodPremierRequest::class => MockResponse::make([
                'food' => [
                    'food_id' => '1641',
                    'food_name' => 'Chicken Breast',
                    'food_type' => 'Generic',
                    'servings' => [
                        'serving' => [
                            'serving_id' => '1',
                            'serving_description' => '100 g',
                            'calories' => '165',
                            'protein' => '31',
                            'carbohydrate' => '0',
                            'fat' => '3.6',
                        ],
                    ],
                ],
            ]),
        ]);

        $food = $this->service()->getFood('1641');

        $this->assertSame('1641', $food['food_id']);
        $this->assertIsList($food['servings']['serving']);
        $this->assertSame('100 g', $food['servings']['serving'][0]['serving_description']);
        $this->assertSame(165.0, $food['servings']['serving'][0]['calories']);
    }

    public function test_get_food_returns_null_on_api_failure(): void
    {
        MockClient::global([
            GetFoodPremierRequest::class => MockResponse::make([
                'error' => ['code' => '8', 'message' => 'Invalid signature'],
            ]),
        ]);

        $service = $this->service();

        $this->assertNull($service->getFood('1641'));
        $this->assertSame('8', $service->lastError()['code']);
    }

    public function test_search_by_barcode_returns_normalized_food(): void
    {
        MockClient::global([
            SearchByBarcodeRequest::class => MockResponse::make([
                'food' => [
                    'food_id' => '99999',
                    'food_name' => 'Nutella',
                    'food_type' => 'Brand',
                    'brand_name' => 'Ferrero',
                    'servings' => [
                        'serving' => [
                            'serving_id' => '1',
                            'serving_description' => '15 g',
                            'calories' => '81',
                            'protein' => '1',
                            'carbohydrate' => '8.7',
                            'fat' => '4.8',
                        ],
                    ],
                ],
            ]),
        ]);

        $food = $this->service()->searchByBarcode('3017620422003');

        $this->assertSame('99999', $food['food_id']);
        $this->assertSame('Nutella', $food['food_name']);
        $this->assertIsList($food['servings']['serving']);
    }

    public function test_search_by_barcode_returns_null_on_empty_barcode(): void
    {
        $this->assertNull($this->service()->searchByBarcode(''));
        $this->assertNull($this->service()->searchByBarcode('   '));
    }

    public function test_search_by_barcode_returns_null_on_api_failure(): void
    {
        MockClient::global([
            SearchByBarcodeRequest::class => MockResponse::make([
                'error' => ['code' => '106', 'message' => 'Barcode not found'],
            ]),
        ]);

        $service = $this->service();

        $this->assertNull($service->searchByBarcode('0000000000000'));
        $this->assertSame('106', $service->lastError()['code']);
    }

    public function test_get_food_categories_returns_list(): void
    {
        MockClient::global([
            GetFoodCategoriesRequest::class => MockResponse::make([
                'food_categories' => [
                    'food_category' => [
                        ['food_category_id' => '1', 'food_category_name' => 'Baked Products'],
                        ['food_category_id' => '2', 'food_category_name' => 'Beef Products'],
                    ],
                ],
            ]),
        ]);

        $categories = $this->service()->getFoodCategories();

        $this->assertCount(2, $categories);
        $this->assertSame('1', $categories[0]['food_category_id']);
        $this->assertSame('Baked Products', $categories[0]['food_category_name']);
    }

    public function test_get_food_categories_wraps_single_object_in_list(): void
    {
        MockClient::global([
            GetFoodCategoriesRequest::class => MockResponse::make([
                'food_categories' => [
                    'food_category' => ['food_category_id' => '1', 'food_category_name' => 'Baked Products'],
                ],
            ]),
        ]);

        $categories = $this->service()->getFoodCategories();

        $this->assertIsList($categories);
        $this->assertCount(1, $categories);
    }

    public function test_get_food_categories_returns_empty_on_api_failure(): void
    {
        MockClient::global([
            GetFoodCategoriesRequest::class => MockResponse::make([
                'error' => ['code' => '8', 'message' => 'Invalid signature'],
            ]),
        ]);

        $this->assertSame([], $this->service()->getFoodCategories());
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
}
