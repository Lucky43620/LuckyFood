<?php

namespace App\Services;

use App\Http\Integrations\FatSecret\FatSecretOAuth1Connector;
use App\Http\Integrations\FatSecret\Requests\AutocompleteRequest;
use App\Http\Integrations\FatSecret\Requests\GetFoodPremierRequest;
use App\Http\Integrations\FatSecret\Requests\GetFoodRequest;
use App\Http\Integrations\FatSecret\Requests\SearchFoodsPremierRequest;
use App\Http\Integrations\FatSecret\Requests\SearchFoodsRequest;
use JsonException;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Throwable;

class FatSecretService
{
    private ?array $lastError = null;

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
    ): array {
        $this->lastError = null;
        $query = trim($query);

        if ($query === '') {
            return $this->emptySearchPage($page, $perPage);
        }

        $page = max(0, $page);
        $perPage = max(1, min(50, $perPage));
        try {
            $response = $this->sendWithLocaleRetry(
                new SearchFoodsPremierRequest($query, $page, $perPage, $region, $language),
                new SearchFoodsPremierRequest($query, $page, $perPage),
                'search.premier',
            );

            if ($this->isUsableResponse($response)) {
                return $this->normalizeSearchPage($this->safeJson($response), $query, $query, true);
            }

            $searchExpression = $this->resolveSearchExpression($query);
            $fallback = $this->sendWithLocaleRetry(
                new SearchFoodsRequest($searchExpression, $page, $perPage, $region, $language),
                new SearchFoodsRequest($searchExpression, $page, $perPage),
                'search.basic',
            );

            if (! $this->isUsableResponse($fallback)) {
                return $this->emptySearchPage($page, $perPage);
            }

            $this->lastError = null;

            return $this->normalizeSearchPage($this->safeJson($fallback), $query, $searchExpression, false);
        } catch (Throwable $exception) {
            $this->lastError = [
                'operation' => 'search.oauth1',
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
            $response = $this->sendWithLocaleRetry(
                new AutocompleteRequest($expression, $maxResults, $region),
                new AutocompleteRequest($expression, $maxResults),
                'autocomplete.premier',
            );

            if ($this->isUsableResponse($response)) {
                return $this->normalizeSuggestions($this->safeJson($response), $maxResults);
            }

            $searchExpression = $this->resolveSearchExpression($expression);
            $fallback = $this->sendWithLocaleRetry(
                new SearchFoodsRequest($searchExpression, 0, $maxResults, $region),
                new SearchFoodsRequest($searchExpression, 0, $maxResults),
                'autocomplete.basic',
            );

            if (! $this->isUsableResponse($fallback)) {
                return [];
            }

            $this->lastError = null;

            return $this->normalizeSuggestionsFromSearch($this->safeJson($fallback), $maxResults);
        } catch (Throwable $exception) {
            $this->lastError = [
                'operation' => 'autocomplete.oauth1',
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
            $response = $this->sendWithLocaleRetry(
                new GetFoodPremierRequest($foodId, $region, $language),
                new GetFoodPremierRequest($foodId),
                'food.premier',
            );

            if ($this->isUsableResponse($response)) {
                return $this->normalizeFoodDetails($this->safeJson($response));
            }

            $fallback = $this->sendWithLocaleRetry(
                new GetFoodRequest($foodId, $region, $language),
                new GetFoodRequest($foodId),
                'food.basic',
            );

            if (! $this->isUsableResponse($fallback)) {
                return null;
            }

            $this->lastError = null;

            return $this->normalizeFoodDetails($this->safeJson($fallback));
        } catch (Throwable $exception) {
            $this->lastError = [
                'operation' => 'food.oauth1',
                'status' => null,
                'code' => null,
                'message' => $exception->getMessage(),
                'ip' => null,
            ];

            return null;
        }
    }

    public function lastError(): ?array
    {
        return $this->lastError;
    }

    private function sendWithLocaleRetry(Request $request, Request $withoutLocale, string $operation): ?Response
    {
        $response = $this->send($request, $operation);

        if ($this->isUsableResponse($response) || ! $this->isLocaleError($response)) {
            return $response;
        }

        $retry = $this->send($withoutLocale, $operation.'.without_locale');

        if ($this->isUsableResponse($retry)) {
            $this->lastError = null;
        }

        return $retry;
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

        $response = (new FatSecretOAuth1Connector($this->consumerKey, $this->consumerSecret, $this->baseUrl))->send($request);

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

    private function normalizeSearchPage(array $payload, string $query, string $searchExpression, bool $premier): array
    {
        $foodsPayload = $premier ? ($payload['foods_search'] ?? []) : ($payload['foods'] ?? []);
        $results = $this->normalizeSearchResults($payload, $premier);
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
            'effective_query' => $searchExpression === $query ? '' : $searchExpression,
        ];
    }

    private function normalizeSearchResults(array $payload, bool $premier): array
    {
        $foods = $premier
            ? $this->asList($payload['foods_search']['results']['food'] ?? [])
            : $this->asList($payload['foods']['food'] ?? []);

        return array_values(array_filter(array_map(
            fn (array $food): array => $premier ? $this->normalizeServingFood($food) : $this->normalizeDescriptionFood($food),
            $foods,
        ), static fn (array $food): bool => $food['food_id'] !== '' && $food['food_name'] !== ''));
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

    private function normalizeDescriptionFood(array $food): array
    {
        $nutrition = $this->parseFoodDescription((string) ($food['food_description'] ?? ''));

        return [
            'food_id' => (string) ($food['food_id'] ?? ''),
            'food_name' => $this->localizeFoodName((string) ($food['food_name'] ?? ''), (string) ($food['food_type'] ?? '')),
            'original_food_name' => (string) ($food['food_name'] ?? ''),
            'brand_name' => $this->nullableString($food['brand_name'] ?? null),
            'food_type' => (string) ($food['food_type'] ?? ''),
            'food_url' => (string) ($food['food_url'] ?? ''),
            'image_url' => $this->extractImageUrl($food),
            'calories' => $nutrition['calories'],
            'protein' => $nutrition['protein'],
            'carbs' => $nutrition['carbs'],
            'fat' => $nutrition['fat'],
            'serving_description' => $nutrition['serving_description'],
        ];
    }

    private function parseFoodDescription(string $description): array
    {
        $description = preg_replace('/\s+/', ' ', trim($description)) ?? '';
        $servingDescription = trim(strtok($description, '-') ?: '');

        if (str_starts_with(strtolower($servingDescription), 'per ')) {
            $servingDescription = 'Pour '.substr($servingDescription, 4);
        }

        return [
            'serving_description' => $servingDescription,
            'calories' => $this->matchInt('/Calories:\s*([0-9]+(?:[.,][0-9]+)?)\s*kcal/i', $description),
            'fat' => $this->matchFloat('/Fat:\s*([0-9]+(?:[.,][0-9]+)?)\s*g/i', $description),
            'carbs' => $this->matchFloat('/Carbs:\s*([0-9]+(?:[.,][0-9]+)?)\s*g/i', $description),
            'protein' => $this->matchFloat('/Protein:\s*([0-9]+(?:[.,][0-9]+)?)\s*g/i', $description),
        ];
    }

    private function normalizeSuggestionsFromSearch(array $payload, int $maxResults): array
    {
        $suggestions = [];

        foreach ($this->asList($payload['foods']['food'] ?? []) as $food) {
            $name = trim($this->localizeFoodName(
                (string) ($food['food_name'] ?? ''),
                (string) ($food['food_type'] ?? ''),
            ));

            if ($name !== '') {
                $suggestions[$name] = $name;
            }
        }

        return array_slice(array_values($suggestions), 0, $maxResults);
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

        $food['food_name'] = $this->localizeFoodName((string) ($food['food_name'] ?? ''), (string) ($food['food_type'] ?? ''));
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
            if ((string) ($serving['is_default'] ?? '') === '1') {
                return $serving;
            }
        }

        foreach ($servings as $serving) {
            $description = strtolower(str_replace(' ', '', (string) ($serving['serving_description'] ?? '')));

            if (in_array($description, ['100g', '100ml'], true)) {
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

    private function isLocaleError(?Response $response): bool
    {
        $error = $this->fatSecretError($response);
        $code = (int) ($error['code'] ?? 0);
        $message = strtolower((string) ($error['message'] ?? ''));

        return $code === 208
            || ($code === 14 && str_contains($message, 'localization'))
            || str_contains($message, 'region')
            || str_contains($message, 'language');
    }

    private function fatSecretError(?Response $response): array
    {
        if (! $response instanceof Response) {
            return [];
        }

        $error = $this->safeJson($response)['error'] ?? [];

        return is_array($error) ? $error : [];
    }

    private function captureApiFailure(string $operation, ?Response $response): void
    {
        if (! $response instanceof Response) {
            return;
        }

        $error = $this->fatSecretError($response);
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

    private function resolveSearchExpression(string $query): string
    {
        $normalized = $this->asciiLower($query);

        $phrases = [
            'blanc de poulet' => 'chicken breast',
            'filet de poulet' => 'chicken breast',
            'escalope de poulet' => 'chicken breast',
            'cuisse de poulet' => 'chicken thigh',
            'pilon de poulet' => 'chicken drumstick',
            'poulet grille' => 'grilled chicken',
            'poulet roti' => 'roasted chicken',
            'poul' => 'chicken',
            'poulet' => 'chicken',
            'dinde' => 'turkey',
            'boeuf' => 'beef',
            'porc' => 'pork',
            'saumon' => 'salmon',
            'thon' => 'tuna',
            'oeuf' => 'egg',
            'oeufs' => 'eggs',
            'riz' => 'rice',
            'pomme de terre' => 'potato',
        ];

        return $phrases[$normalized] ?? $query;
    }

    private function localizeFoodName(string $name, string $foodType = ''): string
    {
        $name = trim($name);

        if (strtolower($foodType) === 'brand') {
            return $name;
        }

        $translations = [
            'Chicken Breast' => 'Blanc de Poulet',
            'Skinless Chicken Breast' => 'Blanc de Poulet (Peau Pas Mangee)',
            'Boneless Skinless Chicken Breast' => 'Blanc de Poulet Sans Os ni Peau',
            'Boneless Skinless Chicken Breasts' => 'Blancs de Poulet Sans Os ni Peau',
            'Chicken Thigh' => 'Cuisse de Poulet',
            'Chicken Drumstick' => 'Pilon de Poulet',
            'Grilled Chicken' => 'Poulet Grille',
            'Rotisserie Chicken' => 'Poulet Roti',
            'Roasted Broiled or Baked Chicken Breast' => 'Blanc de Poulet Roti ou Cuit au Four',
            'Roasted Broiled or Baked Chicken Breast (Skin Not Eaten)' => 'Blanc de Poulet Roti ou Cuit au Four (Peau Pas Mangee)',
            'Chicken' => 'Poulet',
        ];

        return $translations[$name] ?? $name;
    }

    private function asciiLower(string $value): string
    {
        $value = strtr($value, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'å' => 'a',
            'ç' => 'c',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ñ' => 'n',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
            'ý' => 'y', 'ÿ' => 'y',
            'œ' => 'oe', 'æ' => 'ae',
            'À' => 'a', 'Â' => 'a', 'Ä' => 'a', 'Á' => 'a', 'Ã' => 'a', 'Å' => 'a',
            'Ç' => 'c',
            'É' => 'e', 'È' => 'e', 'Ê' => 'e', 'Ë' => 'e',
            'Í' => 'i', 'Ì' => 'i', 'Î' => 'i', 'Ï' => 'i',
            'Ñ' => 'n',
            'Ó' => 'o', 'Ò' => 'o', 'Ô' => 'o', 'Ö' => 'o', 'Õ' => 'o',
            'Ù' => 'u', 'Û' => 'u', 'Ü' => 'u', 'Ú' => 'u',
            'Ý' => 'y',
            'Œ' => 'oe', 'Æ' => 'ae',
        ]);

        return strtolower(trim(preg_replace('/\s+/', ' ', $value) ?? $value));
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

    private function matchInt(string $pattern, string $subject): ?int
    {
        $value = $this->matchFloat($pattern, $subject);

        return $value === null ? null : (int) round($value);
    }

    private function matchFloat(string $pattern, string $subject): ?float
    {
        if (! preg_match($pattern, $subject, $matches)) {
            return null;
        }

        return $this->toFloat($matches[1]);
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
