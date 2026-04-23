<?php

declare(strict_types=1);

namespace Mohamed\ShipStation\Resources;

use Mohamed\ShipStation\Client\ShipStationClient;
use Mohamed\ShipStation\DTO\Shipment;

class Rates
{
    public function __construct(protected ShipStationClient $client)
    {
    }

    /**
     * Get rates for a shipment (a.k.a. "shop for rates").
     *
     * @param  Shipment|array<string, mixed>  $shipment
     * @param  array<string, mixed>  $rateOptions  Must include `carrier_ids`.
     * @return array<string, mixed>
     */
    public function get(Shipment|array $shipment, array $rateOptions): array
    {
        return $this->client->post('/rates', [
            'shipment' => $shipment instanceof Shipment ? $shipment->toArray() : $shipment,
            'rate_options' => $rateOptions,
        ]);
    }

    /**
     * Retrieve an estimate for a shipment without creating a rate resource.
     *
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    public function estimate(array $payload): array
    {
        return $this->client->post('/rates/estimate', $payload);
    }

    /**
     * Retrieve a previously-created rate by ID.
     *
     * @return array<string, mixed>
     */
    public function find(string $rateId): array
    {
        return $this->client->get("/rates/{$rateId}");
    }
}
