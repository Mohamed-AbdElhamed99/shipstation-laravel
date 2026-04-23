<?php

declare(strict_types=1);

namespace Mohamed\ShipStation\Resources;

use Mohamed\ShipStation\Client\ShipStationClient;

class Webhooks
{
    public function __construct(protected ShipStationClient $client)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function list(): array
    {
        return $this->client->get('/environment/webhooks');
    }

    /**
     * Subscribe to a webhook event.
     *
     * Events include: "track", "carrier_connected", "order_notify",
     * "item_order_notify", "ship_notify", "batch", "rate" (varies by plan).
     *
     * @return array<string, mixed>
     */
    public function create(string $event, string $url, array $headers = []): array
    {
        return $this->client->post('/environment/webhooks', [
            'event'   => $event,
            'url'     => $url,
            'headers' => $headers,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function find(string $webhookId): array
    {
        return $this->client->get("/environment/webhooks/{$webhookId}");
    }

    /**
     * @return array<string, mixed>
     */
    public function update(string $webhookId, string $url): array
    {
        return $this->client->put("/environment/webhooks/{$webhookId}", [
            'url' => $url,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $webhookId): array
    {
        return $this->client->delete("/environment/webhooks/{$webhookId}");
    }
}
