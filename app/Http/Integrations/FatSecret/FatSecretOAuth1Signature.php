<?php

namespace App\Http\Integrations\FatSecret;

final class FatSecretOAuth1Signature
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public function make(string $method, string $url, array $parameters, string $consumerSecret): string
    {
        $normalized = implode('&', array_map(
            static fn (array $pair): string => $pair[0].'='.$pair[1],
            $this->normalizedPairs($parameters),
        ));

        $base = strtoupper($method).'&'.$this->encode($this->signatureUrl($url)).'&'.$this->encode($normalized);
        $key = $this->encode($consumerSecret).'&';

        return base64_encode(hash_hmac('sha1', $base, $key, true));
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return list<array{0: string, 1: string}>
     */
    public function normalizedPairs(array $parameters): array
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

        return $pairs;
    }

    public function signatureUrl(string $url): string
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return strtok($url, '?') ?: $url;
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '';

        if (($scheme === 'http' && $port === ':80') || ($scheme === 'https' && $port === ':443')) {
            $port = '';
        }

        return $scheme.'://'.$host.$port.$path;
    }

    private function encode(string $value): string
    {
        return str_replace('%7E', '~', rawurlencode($value));
    }
}
