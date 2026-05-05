<?php

namespace App\Http\Integrations\FatSecret\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class AutocompleteRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $expression,
        private readonly int $maxResults = 6,
        private readonly ?string $region = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/food/autocomplete/v2';
    }

    protected function defaultQuery(): array
    {
        return array_filter([
            'expression' => $this->expression,
            'max_results' => $this->maxResults,
            'region' => $this->region,
            'format' => 'json',
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
