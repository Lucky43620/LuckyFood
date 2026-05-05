<?php

namespace App\Http\Integrations\FatSecret;

use Saloon\Contracts\Authenticator;
use Saloon\Http\Connector;

class FatSecretOAuth1Connector extends Connector
{
    public function __construct(
        private readonly string $consumerKey,
        private readonly string $consumerSecret,
        private readonly string $baseUrl = 'https://platform.fatsecret.com/rest',
    ) {}

    public function resolveBaseUrl(): string
    {
        return rtrim($this->baseUrl, '/');
    }

    protected function defaultAuth(): Authenticator
    {
        return new FatSecretOAuth1Authenticator($this->consumerKey, $this->consumerSecret);
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
        ];
    }
}
