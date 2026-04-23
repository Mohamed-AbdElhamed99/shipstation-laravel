<?php

declare(strict_types=1);

namespace Mohamed\ShipStation\Resources;

use Mohamed\ShipStation\Client\ShipStationClient;

class Tracking
{
    public function __construct(protected ShipStationClient $client)
    {
    }

    /**
     * Track by carrier code + tracking number (does not require a label).
     *
     * @return array<string, mixed>
     */
    public function get(string $carrierCode, string $trackingNumber): array
    {
        return $this->client->get('/tracking', [
            'carrier_code'    => $carrierCode,
            'tracking_number' => $trackingNumber,
        ]);
    }

    /**
     * Subscribe to push notifications for a tracking number.
     *
     * @return array<string, mixed>
     */
    public function startTracking(string $carrierCode, string $trackingNumber): array
    {
        return $this->client->post('/tracking/start', [
            'carrier_code'    => $carrierCode,
            'tracking_number' => $trackingNumber,
        ]);
    }

    /**
     * Stop push notifications for a tracking number.
     *
     * @return array<string, mixed>
     */
    public function stopTracking(string $carrierCode, string $trackingNumber): array
    {
        return $this->client->post('/tracking/stop', [
            'carrier_code'    => $carrierCode,
            'tracking_number' => $trackingNumber,
        ]);
    }
}
