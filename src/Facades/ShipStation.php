<?php

declare(strict_types=1);

namespace Mohamed\ShipStation\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Mohamed\ShipStation\Resources\Addresses addresses()
 * @method static \Mohamed\ShipStation\Resources\Rates rates()
 * @method static \Mohamed\ShipStation\Resources\Labels labels()
 * @method static \Mohamed\ShipStation\Resources\Tracking tracking()
 * @method static \Mohamed\ShipStation\Resources\Carriers carriers()
 * @method static \Mohamed\ShipStation\Resources\Webhooks webhooks()
 * @method static array get(string $uri, array $query = [])
 * @method static array post(string $uri, array $payload = [])
 * @method static array put(string $uri, array $payload = [])
 * @method static array delete(string $uri)
 *
 * @see \Mohamed\ShipStation\Client\ShipStationClient
 */
class ShipStation extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'shipstation';
    }
}
