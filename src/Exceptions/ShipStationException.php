<?php

declare(strict_types=1);

namespace Mohamed\ShipStation\Exceptions;

use Exception;
use Throwable;

class ShipStationException extends Exception
{
    /**
     * @param  array<int, array<string, mixed>>  $errors
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        public readonly ?string $requestId = null,
        public readonly array $errors = [],
        public readonly ?int $statusCode = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Build an exception from a raw ShipStation error response body.
     *
     * @param  array<string, mixed>  $body
     */
    public static function fromResponse(array $body, int $statusCode): static
    {
        $errors = $body['errors'] ?? [];
        $first = $errors[0] ?? [];
        $message = $first['message'] ?? ($body['message'] ?? 'ShipStation API error');
        $requestId = $body['request_id'] ?? null;

        return new static(
            message: $message,
            code: $statusCode,
            requestId: $requestId,
            errors: is_array($errors) ? $errors : [],
            statusCode: $statusCode,
        );
    }
}
