<?php

namespace App\Http\Integrations\FatSecret\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class SearchFoodsPremierRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $searchExpression,
        private readonly int $page = 0,
        private readonly int $perPage = 20,
        private readonly ?string $region = null,
        private readonly ?string $language = null,
        private readonly string $foodType = 'none',
    ) {}

    public function resolveEndpoint(): string
    {
        return '/foods/search/v5';
    }

    protected function defaultQuery(): array
    {
        return array_filter([
            'search_expression' => $this->searchExpression,
            'page_number' => $this->page,
            'max_results' => $this->perPage,
            'include_sub_categories' => 'true',
            'include_food_images' => 'true',
            'include_food_attributes' => 'true',
            'flag_default_serving' => 'true',
            'food_type' => $this->foodType,
            'region' => $this->region,
            'language' => $this->language,
            'format' => 'json',
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
