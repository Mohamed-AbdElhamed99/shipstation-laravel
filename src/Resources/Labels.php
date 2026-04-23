<?php

declare(strict_types=1);

namespace Mohamed\ShipStation\Resources;

use Mohamed\ShipStation\Client\ShipStationClient;
use Mohamed\ShipStation\DTO\Shipment;

class Labels
{
    public function __construct(protected ShipStationClient $client)
    {
    }

    /**
     * Create (purchase) a label directly from a shipment.
     *
     * @param  Shipment|array<string, mixed>  $shipment
     * @param  array<string, mixed>  $options  Extra label options (label_format, label_layout, etc.).
     * @return array<string, mixed>
     */
    public function create(Shipment|array $shipment, array $options = []): array
    {
        $payload = array_merge(
            ['shipment' => $shipment instanceof Shipment ? $shipment->toArray() : $shipment],
            $options
        );

        return $this->client->post('/labels', $payload);
    }

    /**
     * Purchase a label using a previously-shopped rate ID.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function createFromRate(string $rateId, array $options = []): array
    {
        return $this->client->post("/labels/rates/{$rateId}", $options);
    }

    /**
     * Create a return label for an existing label.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function createReturn(string $labelId, array $options = []): array
    {
        return $this->client->post("/labels/{$labelId}/return", $options);
    }

    /**
     * Retrieve a label by ID.
     *
     * @return array<string, mixed>
     */
    public function find(string $labelId): array
    {
        return $this->client->get("/labels/{$labelId}");
    }

    /**
     * List labels with filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function list(array $filters = []): array
    {
        return $this->client->get('/labels', $filters);
    }

    /**
     * Void a label.
     *
     * @return array<string, mixed>
     */
    public function void(string $labelId): array
    {
        return $this->client->put("/labels/{$labelId}/void");
    }

    /**
     * Fetch tracking information for a specific label.
     *
     * @return array<string, mixed>
     */
    public function track(string $labelId): array
    {
        return $this->client->get("/labels/{$labelId}/track");
    }
}
