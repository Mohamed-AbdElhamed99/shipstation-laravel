<?php

declare(strict_types=1);

namespace Mohamed\ShipStation\DTO;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
final class Shipment implements Arrayable
{
    /**
     * @param  array<int, Package>  $packages
     */
    public function __construct(
        public readonly Address $shipTo,
        public readonly Address $shipFrom,
        public readonly array $packages,
        public readonly ?string $serviceCode = null,
        public readonly ?string $carrierId = null,
        public readonly ?string $confirmation = null, // "none" | "delivery" | "signature" | "adult_signature"
        public readonly ?string $externalShipmentId = null,
        public readonly ?string $validateAddress = null, // "no_validation" | "validate_only" | "validate_and_clean"
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'service_code' => $this->serviceCode,
            'carrier_id' => $this->carrierId,
            'ship_to' => $this->shipTo->toArray(),
            'ship_from' => $this->shipFrom->toArray(),
            'packages' => array_map(fn (Package $p) => $p->toArray(), $this->packages),
            'confirmation' => $this->confirmation,
            'external_shipment_id' => $this->externalShipmentId,
            'validate_address' => $this->validateAddress,
        ], fn ($v) => $v !== null);
    }
}
