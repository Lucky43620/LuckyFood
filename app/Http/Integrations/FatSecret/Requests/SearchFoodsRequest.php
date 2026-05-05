<?php

namespace App\Http\Integrations\FatSecret\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;

class SearchFoodsRequest extends Request implements HasBody
{
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $searchExpression,
        private readonly int $page = 0,
        private readonly int $perPage = 20,
        private readonly ?string $region = null,
        private readonly ?string $language = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/server.api';
    }

    protected function defaultBody(): array
    {
        return array_filter([
            'method' => 'foods.search',
            'search_expression' => $this->searchExpression,
            'page_number' => $this->page,
            'max_results' => $this->perPage,
            'format' => 'json',
            'generic_description' => 'weight',
            'region' => $this->region,
            'language' => $this->language,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
