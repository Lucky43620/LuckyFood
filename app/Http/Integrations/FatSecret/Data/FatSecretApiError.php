<?php

namespace App\Http\Integrations\FatSecret\Data;

final readonly class FatSecretApiError
{
    public function __construct(
        public string $operation,
        public ?int $status,
        public string|int|null $code,
        public string $message,
        public ?string $ip = null,
    ) {}

    /**
     * @return array{operation: string, status: int|null, code: string|int|null, message: string, ip: string|null}
     */
    public function toArray(): array
    {
        return [
            'operation' => $this->operation,
            'status' => $this->status,
            'code' => $this->code,
            'message' => $this->message,
            'ip' => $this->ip,
        ];
    }
}
