<?php

namespace App\Http\Integrations\FatSecret\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class SearchByBarcodeRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $barcode,
        private readonly ?string $region = null,
        private readonly ?string $language = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/food/barcode/v2';
    }

    protected function defaultQuery(): array
    {
        return array_filter([
            'barcode' => $this->barcode,
            'include_food_images' => 'true',
            'include_food_attributes' => 'true',
            'flag_default_serving' => 'true',
            'region' => $this->region,
            'language' => $this->language,
            'format' => 'json',
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
