<?php

namespace App\Services;

use App\Http\Integrations\FatSecret\FatSecretOAuth1Connector;
use App\Http\Integrations\FatSecret\Requests\AutocompleteRequest;
use App\Http\Integrations\FatSecret\Requests\GetFoodCategoriesRequest;
use App\Http\Integrations\FatSecret\Requests\GetFoodPremierRequest;
use App\Http\Integrations\FatSecret\Requests\SearchByBarcodeRequest;
use App\Http\Integrations\FatSecret\Requests\SearchFoodsPremierRequest;
use JsonException;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Throwable;

class FatSecretService
{
    private ?array $lastError = null;

    private ?FatSecretOAuth1Connector $connector = null;

    public function __construct(
        private readonly string $consumerKey = '',
        private readonly string $consumerSecret = '',
        private readonly string $baseUrl = 'https://platform.fatsecret.com/rest',
    ) {}

    public function searchFoods(
        string $query,
        int $page = 0,
        int $perPage = 20,
        ?string $region = null,
        ?string $language = null,
    ): array {
        return $this->searchFoodsPage($query, $page, $perPage, $region, $language)['results'];
    }

    public function searchFoodsPage(
        string $query,
        int $page = 0,
        int $perPage = 20,
        ?string $region = null,
        ?string $language = null,
        ?int $foodCategoryId = null,
    ): array {
        $this->lastError = null;
        $query = trim($query);

        if ($query === '') {
            return $this->emptySearchPage($page, $perPage);
        }

        $page = max(0, $page);
        $perPage = max(1, min(50, $perPage));

        try {
            $response = $this->send(
                new SearchFoodsPremierRequest($query, $page, $perPage, $region, $language, foodCategoryId: $foodCategoryId),
                'search.premier',
            );

            if ($this->isUsableResponse($response)) {
                return $this->normalizeSearchPage($this->safeJson($response), $query);
            }

            return $this->emptySearchPage($page, $perPage);
        } catch (Throwable $exception) {
            $this->lastError = [
                'operation' => 'search.premier',
                'status' => null,
                'code' => null,
                'message' => $exception->getMessage(),
                'ip' => null,
            ];

            return $this->emptySearchPage($page, $perPage);
        }
    }

    public function autocomplete(
        string $expression,
        int $maxResults = 6,
        ?string $region = null,
        ?string $language = null,
    ): array {
        $this->lastError = null;
        unset($language);

        $expression = trim($expression);

        if ($expression === '') {
            return [];
        }

        $maxResults = max(1, min(10, $maxResults));

        try {
            $response = $this->send(
                new AutocompleteRequest($expression, $maxResults, $region),
                'autocomplete.premier',
            );

            if ($this->isUsableResponse($response)) {
                return $this->normalizeSuggestions($this->safeJson($response), $maxResults);
            }

            return [];
        } catch (Throwable $exception) {
            $this->lastError = [
                'operation' => 'autocomplete.premier',
                'status' => null,
                'code' => null,
                'message' => $exception->getMessage(),
                'ip' => null,
            ];

            return [];
        }
    }

    public function getFood(string $foodId, ?string $region = null, ?string $language = null): ?array
    {
        $this->lastError = null;
        $foodId = trim($foodId);

        if ($foodId === '') {
            return null;
        }

        try {
            $response = $this->send(
                new GetFoodPremierRequest($foodId, $region, $language),
                'food.premier',
            );

            if ($this->isUsableResponse($response)) {
                return $this->normalizeFoodDetails($this->safeJson($response));
            }

            return null;
        } catch (Throwable $exception) {
            $this->lastError = [
                'operation' => 'food.premier',
                'status' => null,
                'code' => null,
                'message' => $exception->getMessage(),
                'ip' => null,
            ];

            return null;
        }
    }

    public function searchByBarcode(
        string $barcode,
        ?string $region = null,
        ?string $language = null,
    ): ?array {
        $this->lastError = null;
        $barcode = trim($barcode);

        if ($barcode === '') {
            return null;
        }

        try {
            $response = $this->send(
                new SearchByBarcodeRequest($barcode, $region, $language),
                'barcode.premier',
            );

            if ($this->isUsableResponse($response)) {
                return $this->normalizeFoodDetails($this->safeJson($response));
            }

            return null;
        } catch (Throwable $exception) {
            $this->lastError = [
                'operation' => 'barcode.premier',
                'status' => null,
                'code' => null,
                'message' => $exception->getMessage(),
                'ip' => null,
            ];

            return null;
        }
    }

    public function getFoodCategories(?string $region = null, ?string $language = null): array
    {
        $this->lastError = null;

        try {
            $response = $this->send(
                new GetFoodCategoriesRequest($region, $language),
                'food_categories.premier',
            );

            if (! $this->isUsableResponse($response)) {
                return [];
            }

            $payload = $this->safeJson($response);

            return $this->asList($payload['food_categories']['food_category'] ?? []);
        } catch (Throwable $exception) {
            $this->lastError = [
                'operation' => 'food_categories.premier',
                'status' => null,
                'code' => null,
                'message' => $exception->getMessage(),
                'ip' => null,
            ];

            return [];
        }
    }

    public function lastError(): ?array
    {
        return $this->lastError;
    }

    private function send(Request $request, string $operation): ?Response
    {
        if ($this->consumerKey === '' || $this->consumerSecret === '') {
            $this->lastError = [
                'operation' => $operation,
                'status' => null,
                'code' => null,
                'message' => 'FatSecret OAuth1 credentials are missing.',
                'ip' => null,
            ];

            return null;
        }

        $this->connector ??= new FatSecretOAuth1Connector($this->consumerKey, $this->consumerSecret, $this->baseUrl);
        $response = $this->connector->send($request);

        if (! $this->isUsableResponse($response)) {
            $this->captureApiFailure($operation, $response);
        }

        return $response;
    }

    private function emptySearchPage(int $page, int $perPage): array
    {
        $page = max(0, $page);
        $perPage = max(1, min(50, $perPage));

        return [
            'results' => [],
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => 0,
                'total_pages' => 0,
                'from' => 0,
                'to' => 0,
                'has_previous' => false,
                'has_next' => false,
            ],
            'effective_query' => '',
        ];
    }

    private function normalizeSearchPage(array $payload, string $query): array
    {
        $foodsPayload = $payload['foods_search'] ?? [];
        $results = $this->normalizeSearchResults($payload);
        $total = max(0, (int) ($foodsPayload['total_results'] ?? count($results)));
        $page = max(0, (int) ($foodsPayload['page_number'] ?? 0));
        $perPage = max(1, (int) ($foodsPayload['max_results'] ?? count($results) ?: 20));
        $from = $total === 0 ? 0 : ($page * $perPage) + 1;
        $to = $total === 0 ? 0 : min($total, $from + count($results) - 1);

        return [
            'results' => $results,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $total === 0 ? 0 : (int) ceil($total / $perPage),
                'from' => $from,
                'to' => $to,
                'has_previous' => $page > 0,
                'has_next' => $to < $total,
            ],
            'effective_query' => '',
        ];
    }

    private function normalizeSearchResults(array $payload): array
    {
        $foods = $this->asList($payload['foods_search']['results']['food'] ?? []);

        return array_values(array_filter(
            array_map(fn (array $food): array => $this->normalizeServingFood($food), $foods),
            static fn (array $food): bool => $food['food_id'] !== '' && $food['food_name'] !== '',
        ));
    }

    private function normalizeServingFood(array $food): array
    {
        $serving = $this->chooseServing($food['servings']['serving'] ?? []);

        return [
            'food_id' => (string) ($food['food_id'] ?? ''),
            'food_name' => (string) ($food['food_name'] ?? ''),
            'original_food_name' => (string) ($food['food_name'] ?? ''),
            'brand_name' => $this->nullableString($food['brand_name'] ?? null),
            'food_type' => (string) ($food['food_type'] ?? ''),
            'food_url' => (string) ($food['food_url'] ?? ''),
            'image_url' => $this->extractImageUrl($food),
            'calories' => $this->toInt($serving['calories'] ?? null),
            'protein' => $this->toFloat($serving['protein'] ?? null),
            'carbs' => $this->toFloat($serving['carbohydrate'] ?? null),
            'fat' => $this->toFloat($serving['fat'] ?? null),
            'serving_description' => (string) ($serving['serving_description'] ?? ''),
        ];
    }

    private function normalizeSuggestions(array $payload, int $maxResults): array
    {
        $suggestions = $this->asList($payload['suggestions']['suggestion'] ?? []);

        $suggestions = array_values(array_filter(array_map(
            static fn (mixed $suggestion): string => trim((string) $suggestion),
            $suggestions,
        )));

        return array_slice($suggestions, 0, $maxResults);
    }

    private function normalizeFoodDetails(array $payload): ?array
    {
        $food = $payload['food'] ?? null;

        if (! is_array($food)) {
            return null;
        }

        if (isset($food['servings']['serving'])) {
            $food['servings']['serving'] = $this->asList($food['servings']['serving']);
        }

        $food['original_food_name'] = (string) ($payload['food']['food_name'] ?? $food['food_name'] ?? '');
        $food['brand_name'] = $this->nullableString($food['brand_name'] ?? null);
        $food['image_url'] = $this->extractImageUrl($food);
        $food['food_sub_categories'] = $this->normalizeSubCategories($food['food_sub_categories']['food_sub_category'] ?? []);
        $food['food_attributes'] = $this->normalizeFoodAttributes($food['food_attributes'] ?? []);

        foreach ($food['servings']['serving'] ?? [] as $index => $serving) {
            $food['servings']['serving'][$index] = $this->normalizeServing($serving);
        }

        return $food;
    }

    private function chooseServing(mixed $servings): array
    {
        $servings = $this->asList($servings);

        foreach ($servings as $serving) {
            $description = strtolower(str_replace(' ', '', (string) ($serving['serving_description'] ?? '')));

            if (in_array($description, ['100g', '100ml'], true)) {
                return $serving;
            }
        }

        foreach ($servings as $serving) {
            if ((string) ($serving['is_default'] ?? '') === '1') {
                return $serving;
            }
        }

        return $servings[0] ?? [];
    }

    private function normalizeServing(array $serving): array
    {
        foreach ([
            'calories', 'carbohydrate', 'protein', 'fat', 'saturated_fat', 'polyunsaturated_fat',
            'monounsaturated_fat', 'trans_fat', 'cholesterol', 'sodium', 'potassium', 'fiber',
            'sugar', 'vitamin_a', 'vitamin_c', 'calcium', 'iron', 'metric_serving_amount',
            'number_of_units',
        ] as $key) {
            if (array_key_exists($key, $serving)) {
                $serving[$key] = $this->toFloat($serving[$key]);
            }
        }

        return $serving;
    }

    private function normalizeFoodAttributes(mixed $attributes): array
    {
        if (! is_array($attributes)) {
            return ['allergens' => [], 'preferences' => []];
        }

        return [
            'allergens' => $this->normalizeAttributeList($attributes['allergens']['allergen'] ?? []),
            'preferences' => $this->normalizeAttributeList($attributes['preferences']['preference'] ?? []),
        ];
    }

    private function normalizeAttributeList(mixed $value): array
    {
        return array_values(array_map(static fn (array $item): array => [
            'id' => (string) ($item['id'] ?? ''),
            'name' => (string) ($item['name'] ?? ''),
            'value' => (int) ($item['value'] ?? -1),
        ], array_filter($this->asList($value), static fn (mixed $item): bool => is_array($item))));
    }

    private function normalizeSubCategories(mixed $value): array
    {
        return array_values(array_filter(array_map(
            static fn (mixed $category): string => trim((string) $category),
            $this->asList($value),
        )));
    }

    private function extractImageUrl(array $food): ?string
    {
        $image = $food['image_url']
            ?? $food['food_image']
            ?? $food['food_images']['food_image']
            ?? null;

        if (is_array($image)) {
            $image = $this->asList($image)[0] ?? null;
        }

        if (is_array($image)) {
            $image = $image['image_url'] ?? $image['food_image_url'] ?? $image['url'] ?? null;
        }

        $image = trim((string) $image);

        return $image === '' ? null : $image;
    }

    private function isUsableResponse(?Response $response): bool
    {
        if (! $response instanceof Response || $response->failed()) {
            return false;
        }

        return ! isset($this->safeJson($response)['error']);
    }

    private function captureApiFailure(string $operation, ?Response $response): void
    {
        if (! $response instanceof Response) {
            return;
        }

        $error = $this->safeJson($response)['error'] ?? [];
        $error = is_array($error) ? $error : [];
        $message = (string) ($error['message'] ?? 'FatSecret request failed.');

        $this->lastError = [
            'operation' => $operation,
            'status' => $response->status(),
            'code' => $error['code'] ?? null,
            'message' => $message,
            'ip' => $this->extractIpAddress($message),
        ];
    }

    private function extractIpAddress(string $message): ?string
    {
        if (! preg_match('/\b(?:\d{1,3}\.){3}\d{1,3}\b/', $message, $matches)) {
            return null;
        }

        return $matches[0];
    }

    private function safeJson(?Response $response): array
    {
        if (! $response instanceof Response) {
            return [];
        }

        try {
            $json = $response->json();

            return is_array($json) ? $json : [];
        } catch (JsonException) {
            return [];
        }
    }

    private function asList(mixed $value): array
    {
        if (! is_array($value) || $value === []) {
            return [];
        }

        return array_is_list($value) ? $value : [$value];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function toInt(mixed $value): ?int
    {
        $float = $this->toFloat($value);

        return $float === null ? null : (int) round($float);
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) str_replace(',', '.', (string) $value);
    }
}
