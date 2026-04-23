<?php

declare(strict_types=1);

namespace Mohamed\ShipStation\DTO;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
final class Address implements Arrayable
{
    /**
     * @param  array<int, string>|null  $addressLine2  Secondary address lines.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $addressLine1,
        public readonly string $cityLocality,
        public readonly string $stateProvince,
        public readonly string $postalCode,
        public readonly string $countryCode,
        public readonly ?string $addressLine2 = null,
        public readonly ?string $addressLine3 = null,
        public readonly ?string $companyName = null,
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        public readonly ?string $addressResidentialIndicator = null, // "yes" | "no" | "unknown"
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'company_name' => $this->companyName,
            'phone' => $this->phone,
            'email' => $this->email,
            'address_line1' => $this->addressLine1,
            'address_line2' => $this->addressLine2,
            'address_line3' => $this->addressLine3,
            'city_locality' => $this->cityLocality,
            'state_province' => $this->stateProvince,
            'postal_code' => $this->postalCode,
            'country_code' => $this->countryCode,
            'address_residential_indicator' => $this->addressResidentialIndicator,
        ], fn ($v) => $v !== null);
    }
}
