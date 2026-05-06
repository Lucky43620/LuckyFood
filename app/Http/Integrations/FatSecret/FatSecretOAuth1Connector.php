<?php

namespace App\Http\Integrations\FatSecret;

use Saloon\Contracts\Authenticator;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Connector;
use Saloon\Http\Request;
use Saloon\Traits\Plugins\HasTimeout;

class FatSecretOAuth1Connector extends Connector
{
    use HasTimeout;

    public ?int $tries = 2;

    public ?int $retryInterval = 150;

    public ?bool $throwOnMaxTries = false;

    public ?bool $useExponentialBackoff = true;

    public function __construct(
        private readonly string $consumerKey,
        private readonly string $consumerSecret,
        private readonly string $baseUrl = 'https://platform.fatsecret.com/rest',
        protected float $connectTimeout = 3.0,
        protected float $requestTimeout = 8.0,
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

    #[\Override]
    public function handleRetry(FatalRequestException|RequestException $exception, Request $request): bool
    {
        if ($exception instanceof FatalRequestException) {
            return true;
        }

        $status = $exception->getResponse()->status();

        return $status === 429 || $status >= 500;
    }
}
