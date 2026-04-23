<?php

declare(strict_types=1);

namespace Mohamed\ShipStation\DTO;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
final class Package implements Arrayable
{
    /**
     * @param  string  $weightUnit  "ounce" | "pound" | "gram" | "kilogram"
     * @param  string  $dimensionUnit  "inch" | "centimeter"
     */
    public function __construct(
        public readonly float $weightValue,
        public readonly string $weightUnit = 'pound',
        public readonly ?float $length = null,
        public readonly ?float $width = null,
        public readonly ?float $height = null,
        public readonly string $dimensionUnit = 'inch',
        public readonly ?string $packageCode = null,
        public readonly ?string $insuredValueCurrency = null,
        public readonly ?float $insuredValueAmount = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'weight' => [
                'value' => $this->weightValue,
                'unit' => $this->weightUnit,
            ],
        ];

        if ($this->length !== null && $this->width !== null && $this->height !== null) {
            $data['dimensions'] = [
                'length' => $this->length,
                'width' => $this->width,
                'height' => $this->height,
                'unit' => $this->dimensionUnit,
            ];
        }

        if ($this->packageCode !== null) {
            $data['package_code'] = $this->packageCode;
        }

        if ($this->insuredValueAmount !== null && $this->insuredValueCurrency !== null) {
            $data['insured_value'] = [
                'currency' => $this->insuredValueCurrency,
                'amount' => $this->insuredValueAmount,
            ];
        }

        return $data;
    }
}
