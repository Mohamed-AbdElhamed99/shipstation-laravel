<?php

declare(strict_types=1);

namespace Mohamed\ShipStation\Exceptions;

use Throwable;

class RateLimitException extends ShipStationException
{
    public function __construct(
        string $message = 'Rate limit exceeded',
        int $code = 429,
        ?Throwable $previous = null,
        ?string $requestId = null,
        array $errors = [],
        ?int $statusCode = 429,
        public readonly ?int $retryAfterSeconds = null,
    ) {
        parent::__construct($message, $code, $previous, $requestId, $errors, $statusCode);
    }
}
