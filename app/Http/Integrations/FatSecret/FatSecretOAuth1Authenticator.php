<?php

namespace App\Http\Integrations\FatSecret;

use Saloon\Contracts\Authenticator;
use Saloon\Enums\Method;
use Saloon\Http\PendingRequest;

class FatSecretOAuth1Authenticator implements Authenticator
{
    public function __construct(
        private readonly string $consumerKey,
        private readonly string $consumerSecret,
        private readonly FatSecretOAuth1Signature $signature = new FatSecretOAuth1Signature,
    ) {}

    public function set(PendingRequest $pendingRequest): void
    {
        $oauth = [
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_nonce' => bin2hex(random_bytes(16)),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => (string) time(),
            'oauth_version' => '1.0',
        ];

        $url = $pendingRequest->getUrl();
        $signatureParams = array_merge(
            $this->urlQueryParameters($url),
            $pendingRequest->query()->all(),
            $this->bodyParameters($pendingRequest),
            $oauth,
        );

        $oauth['oauth_signature'] = $this->signature->make(
            $pendingRequest->getMethod()->value,
            $url,
            $signatureParams,
            $this->consumerSecret,
        );

        if ($pendingRequest->getMethod() === Method::GET || $pendingRequest->body() === null) {
            $pendingRequest->query()->merge($oauth);

            return;
        }

        foreach ($oauth as $key => $value) {
            $pendingRequest->body()?->add($key, $value);
        }
    }

    private function bodyParameters(PendingRequest $pendingRequest): array
    {
        $body = $pendingRequest->body()?->all();

        return is_array($body) ? $body : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function urlQueryParameters(string $url): array
    {
        $query = parse_url($url, PHP_URL_QUERY);

        if (! is_string($query) || $query === '') {
            return [];
        }

        parse_str($query, $parameters);

        return is_array($parameters) ? $parameters : [];
    }
}
