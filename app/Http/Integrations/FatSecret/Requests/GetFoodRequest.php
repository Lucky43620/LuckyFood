<?php

namespace App\Http\Integrations\FatSecret\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;

class GetFoodRequest extends Request implements HasBody
{
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $foodId,
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
            'method' => 'food.get',
            'food_id' => $this->foodId,
            'format' => 'json',
            'region' => $this->region,
            'language' => $this->language,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
