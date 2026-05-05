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

        $signatureParams = array_merge(
            $pendingRequest->query()->all(),
            $this->bodyParameters($pendingRequest),
            $oauth,
        );

        $oauth['oauth_signature'] = $this->signature(
            $pendingRequest->getMethod()->value,
            $pendingRequest->getUrl(),
            $signatureParams,
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

    private function signature(string $method, string $url, array $parameters): string
    {
        $pairs = [];

        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $nestedValue) {
                    $pairs[] = [$this->encode((string) $key), $this->encode((string) $nestedValue)];
                }

                continue;
            }

            $pairs[] = [$this->encode((string) $key), $this->encode((string) $value)];
        }

        usort($pairs, static fn (array $left, array $right): int => $left[0] === $right[0]
            ? strcmp($left[1], $right[1])
            : strcmp($left[0], $right[0]));

        $normalized = implode('&', array_map(
            static fn (array $pair): string => $pair[0].'='.$pair[1],
            $pairs,
        ));

        $base = strtoupper($method).'&'.$this->encode($url).'&'.$this->encode($normalized);
        $key = $this->encode($this->consumerSecret).'&';

        return base64_encode(hash_hmac('sha1', $base, $key, true));
    }

    private function encode(string $value): string
    {
        return str_replace('%7E', '~', rawurlencode($value));
    }
}
