<?php

namespace App\Http\Integrations\FatSecret\Data;

/**
 * @template TValue
 */
final readonly class FatSecretApiResult
{
    /**
     * @param  TValue  $data
     */
    private function __construct(
        private mixed $data,
        private ?FatSecretApiError $error = null,
    ) {}

    /**
     * @template TData
     *
     * @param  TData  $data
     * @return self<TData>
     */
    public static function success(mixed $data): self
    {
        return new self($data);
    }

    /**
     * @template TData
     *
     * @param  TData  $fallback
     * @return self<TData>
     */
    public static function failure(mixed $fallback, FatSecretApiError $error): self
    {
        return new self($fallback, $error);
    }

    public function ok(): bool
    {
        return $this->error === null;
    }

    /**
     * @return TValue
     */
    public function data(): mixed
    {
        return $this->data;
    }

    public function error(): ?FatSecretApiError
    {
        return $this->error;
    }
}
