<?php

declare(strict_types=1);

namespace Mohamed\ShipStation\Resources;

use Mohamed\ShipStation\Client\ShipStationClient;
use Mohamed\ShipStation\DTO\Address;

class Addresses
{
    public function __construct(protected ShipStationClient $client)
    {
    }

    /**
     * Validate one or more addresses.
     *
     * Accepts a single Address DTO, an array of them, or a raw associative array.
     * Returns the raw API response (array keyed by index with validation results).
     *
     * @param  Address|array<Address>|array<string, mixed>|array<int, array<string, mixed>>  $addresses
     * @return array<int, array<string, mixed>>
     */
    public function validate(Address|array $addresses): array
    {
        $payload = $this->normalize($addresses);

        return $this->client->post('/addresses/validate', $payload);
    }

    /**
     * Validate a single address and return a simplified result.
     *
     * @return array<string, mixed>
     */
    public function validateOne(Address|array $address): array
    {
        $result = $this->validate($address);

        return $result[0] ?? [];
    }

    /**
     * @param  Address|array<Address>|array<string, mixed>|array<int, array<string, mixed>>  $addresses
     * @return array<int, array<string, mixed>>
     */
    protected function normalize(Address|array $addresses): array
    {
        if ($addresses instanceof Address) {
            return [$addresses->toArray()];
        }

        // Single associative array → wrap it
        if (array_keys($addresses) !== range(0, count($addresses) - 1)) {
            return [$addresses];
        }

        return array_map(
            fn ($a) => $a instanceof Address ? $a->toArray() : $a,
            $addresses
        );
    }
}
