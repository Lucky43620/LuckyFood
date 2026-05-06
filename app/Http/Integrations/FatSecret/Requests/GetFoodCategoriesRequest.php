<?php

namespace App\Http\Integrations\FatSecret\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetFoodCategoriesRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly ?string $region = null,
        private readonly ?string $language = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/food_categories/v2';
    }

    protected function defaultQuery(): array
    {
        return array_filter([
            'region' => $this->region,
            'language' => $this->language,
            'format' => 'json',
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
