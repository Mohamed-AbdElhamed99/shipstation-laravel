<?php

declare(strict_types=1);

namespace Mohamed\ShipStation\Resources;

use Mohamed\ShipStation\Client\ShipStationClient;

class Carriers
{
    public function __construct(protected ShipStationClient $client)
    {
    }

    /**
     * List all connected carriers on the account.
     *
     * @return array<string, mixed>
     */
    public function list(): array
    {
        return $this->client->get('/carriers');
    }

    /**
     * Retrieve a single carrier by its ID.
     *
     * @return array<string, mixed>
     */
    public function find(string $carrierId): array
    {
        return $this->client->get("/carriers/{$carrierId}");
    }

    /**
     * List services available for a carrier.
     *
     * @return array<string, mixed>
     */
    public function services(string $carrierId): array
    {
        return $this->client->get("/carriers/{$carrierId}/services");
    }

    /**
     * List package types available for a carrier.
     *
     * @return array<string, mixed>
     */
    public function packageTypes(string $carrierId): array
    {
        return $this->client->get("/carriers/{$carrierId}/packages");
    }
}
