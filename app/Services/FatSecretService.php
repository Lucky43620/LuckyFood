<?php

namespace App\Services;

use App\Http\Integrations\FatSecret\Data\FatSecretApiError;
use App\Http\Integrations\FatSecret\Data\FatSecretApiResult;
use App\Http\Integrations\FatSecret\FatSecretNormalizer;
use App\Http\Integrations\FatSecret\FatSecretOAuth1Connector;
use App\Http\Integrations\FatSecret\Requests\AutocompleteRequest;
use App\Http\Integrations\FatSecret\Requests\GetFoodCategoriesRequest;
use App\Http\Integrations\FatSecret\Requests\GetFoodPremierRequest;
use App\Http\Integrations\FatSecret\Requests\SearchByBarcodeRequest;
use App\Http\Integrations\FatSecret\Requests\SearchFoodsPremierRequest;
use Illuminate\Support\Facades\Cache;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Throwable;

class FatSecretService
{
    public function __construct(
        private readonly FatSecretNormalizer $normalizer = new FatSecretNormalizer,
        private readonly string $consumerKey = '',
        private readonly string $consumerSecret = '',
        private readonly string $baseUrl = 'https://platform.fatsecret.com/rest',
        private readonly float $connectTimeout = 3.0,
        private readonly float $requestTimeout = 8.0,
        private readonly int $categoryCacheTtl = 86400,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function searchFoods(
        string $query,
        int $page = 0,
        int $perPage = 20,
        ?string $region = null,
        ?string $language = null,
    ): array {
        return $this->searchFoodsPageResult($query, $page, $perPage, $region, $language)->data()['results'];
    }

    /**
     * @return array{results: list<array<string, mixed>>, pagination: array<string, mixed>, effective_query: string}
     */
    public function searchFoodsPage(
        string $query,
        int $page = 0,
        int $perPage = 20,
        ?string $region = null,
        ?string $language = null,
        ?int $foodCategoryId = null,
    ): array {
        return $this->searchFoodsPageResult($query, $page, $perPage, $region, $language, $foodCategoryId)->data();
    }

    /**
     * @return FatSecretApiResult<array{results: list<array<string, mixed>>, pagination: array<string, mixed>, effective_query: string}>
     */
    public function searchFoodsPageResult(
        string $query,
        int $page = 0,
        int $perPage = 20,
        ?string $region = null,
        ?string $language = null,
        ?int $foodCategoryId = null,
    ): FatSecretApiResult {
        $query = trim($query);
        $page = max(0, $page);
        $perPage = max(1, min(50, $perPage));

        if ($query === '') {
            return FatSecretApiResult::success($this->normalizer->emptySearchPage($page, $perPage));
        }

        $result = $this->send(
            new SearchFoodsPremierRequest($query, $page, $perPage, $region, $language, foodCategoryId: $foodCategoryId),
            'search.premier',
        );

        if (! $result->ok()) {
            return FatSecretApiResult::failure(
                $this->normalizer->emptySearchPage($page, $perPage),
                $result->error(),
            );
        }

        return FatSecretApiResult::success($this->normalizer->searchPage($result->data()));
    }

    /**
     * @return list<string>
     */
    public function autocomplete(
        string $expression,
        int $maxResults = 6,
        ?string $region = null,
        ?string $language = null,
    ): array {
        return $this->autocompleteResult($expression, $maxResults, $region, $language)->data();
    }

    /**
     * @return FatSecretApiResult<list<string>>
     */
    public function autocompleteResult(
        string $expression,
        int $maxResults = 6,
        ?string $region = null,
        ?string $language = null,
    ): FatSecretApiResult {
        unset($language);

        $expression = trim($expression);
        $maxResults = max(1, min(10, $maxResults));

        if ($expression === '') {
            return FatSecretApiResult::success([]);
        }

        $result = $this->send(
            new AutocompleteRequest($expression, $maxResults, $region),
            'autocomplete.premier',
        );

        if (! $result->ok()) {
            return FatSecretApiResult::failure([], $result->error());
        }

        return FatSecretApiResult::success($this->normalizer->suggestions($result->data(), $maxResults));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getFood(string $foodId, ?string $region = null, ?string $language = null): ?array
    {
        return $this->getFoodResult($foodId, $region, $language)->data();
    }

    /**
     * @return FatSecretApiResult<array<string, mixed>|null>
     */
    public function getFoodResult(string $foodId, ?string $region = null, ?string $language = null): FatSecretApiResult
    {
        $foodId = trim($foodId);

        if ($foodId === '') {
            return FatSecretApiResult::success(null);
        }

        $result = $this->send(
            new GetFoodPremierRequest($foodId, $region, $language),
            'food.premier',
        );

        if (! $result->ok()) {
            return FatSecretApiResult::failure(null, $result->error());
        }

        return FatSecretApiResult::success($this->normalizer->foodDetails($result->data()));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function searchByBarcode(string $barcode, ?string $region = null, ?string $language = null): ?array
    {
        return $this->searchByBarcodeResult($barcode, $region, $language)->data();
    }

    /**
     * @return FatSecretApiResult<array<string, mixed>|null>
     */
    public function searchByBarcodeResult(
        string $barcode,
        ?string $region = null,
        ?string $language = null,
    ): FatSecretApiResult {
        $barcode = trim($barcode);

        if ($barcode === '') {
            return FatSecretApiResult::success(null);
        }

        $result = $this->send(
            new SearchByBarcodeRequest($barcode, $region, $language),
            'barcode.premier',
        );

        if (! $result->ok()) {
            return FatSecretApiResult::failure(null, $result->error());
        }

        return FatSecretApiResult::success($this->normalizer->foodDetails($result->data()));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getFoodCategories(?string $region = null, ?string $language = null): array
    {
        return $this->getFoodCategoriesResult($region, $language)->data();
    }

    /**
     * @return FatSecretApiResult<list<array<string, mixed>>>
     */
    public function getFoodCategoriesResult(?string $region = null, ?string $language = null): FatSecretApiResult
    {
        $cacheKey = sprintf('fatsecret.categories.%s.%s', $region ?: 'default', $language ?: 'default');

        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return FatSecretApiResult::success($cached);
        }

        $result = $this->send(
            new GetFoodCategoriesRequest($region, $language),
            'food_categories.premier',
        );

        if (! $result->ok()) {
            return FatSecretApiResult::failure([], $result->error());
        }

        $categories = $this->normalizer->categories($result->data());
        Cache::put($cacheKey, $categories, $this->categoryCacheTtl);

        return FatSecretApiResult::success($categories);
    }

    /**
     * @return FatSecretApiResult<array<string, mixed>>
     */
    private function send(Request $request, string $operation): FatSecretApiResult
    {
        if ($this->consumerKey === '' || $this->consumerSecret === '') {
            return FatSecretApiResult::failure([], new FatSecretApiError(
                operation: $operation,
                status: null,
                code: null,
                message: 'FatSecret OAuth1 credentials are missing.',
            ));
        }

        try {
            $response = $this->connector()->send($request);
            $payload = $this->safeJson($response);

            if (! $this->isUsableResponse($response, $payload)) {
                return FatSecretApiResult::failure([], $this->apiFailure($operation, $response, $payload));
            }

            return FatSecretApiResult::success($payload);
        } catch (Throwable $exception) {
            return FatSecretApiResult::failure([], new FatSecretApiError(
                operation: $operation,
                status: null,
                code: null,
                message: $exception->getMessage(),
            ));
        }
    }

    private function connector(): FatSecretOAuth1Connector
    {
        return new FatSecretOAuth1Connector(
            consumerKey: $this->consumerKey,
            consumerSecret: $this->consumerSecret,
            baseUrl: $this->baseUrl,
            connectTimeout: $this->connectTimeout,
            requestTimeout: $this->requestTimeout,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isUsableResponse(Response $response, array $payload): bool
    {
        return ! $response->failed() && ! isset($payload['error']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function apiFailure(string $operation, Response $response, array $payload): FatSecretApiError
    {
        $error = $payload['error'] ?? [];
        $error = is_array($error) ? $error : [];
        $message = (string) ($error['message'] ?? 'FatSecret request failed.');

        return new FatSecretApiError(
            operation: $operation,
            status: $response->status(),
            code: $error['code'] ?? null,
            message: $message,
            ip: $this->extractIpAddress($message),
        );
    }

    private function extractIpAddress(string $message): ?string
    {
        if (! preg_match('/\b(?:\d{1,3}\.){3}\d{1,3}\b/', $message, $matches)) {
            return null;
        }

        return $matches[0];
    }

    /**
     * @return array<string, mixed>
     */
    private function safeJson(Response $response): array
    {
        try {
            $json = $response->json();

            return is_array($json) ? $json : [];
        } catch (Throwable) {
            return [];
        }
    }
}
