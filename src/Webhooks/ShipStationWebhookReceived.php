<?php

declare(strict_types=1);

namespace Mohamed\ShipStation\Webhooks;

use Illuminate\Foundation\Events\Dispatchable;

class ShipStationWebhookReceived
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public readonly string $resourceType,
        public readonly ?string $resourceUrl,
        public readonly array $payload,
        public readonly array $headers,
    ) {
    }

    public function isTrackingEvent(): bool
    {
        return $this->resourceType === 'track' || str_starts_with($this->resourceType, 'TRACK');
    }

    public function isLabelEvent(): bool
    {
        return str_contains(strtolower($this->resourceType), 'label');
    }
}
