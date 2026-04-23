<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Mohamed\ShipStation\DTO\Address;
use Mohamed\ShipStation\DTO\Package;
use Mohamed\ShipStation\DTO\Shipment;
use Mohamed\ShipStation\Exceptions\AuthenticationException;
use Mohamed\ShipStation\Exceptions\RateLimitException;
use Mohamed\ShipStation\Exceptions\ValidationException;
use Mohamed\ShipStation\Facades\ShipStation;

beforeEach(function () {
    Http::preventStrayRequests();
});

it('validates an address and sends the correct payload', function () {
    Http::fake([
        '*/v2/addresses/validate' => Http::response([
            [
                'status' => 'verified',
                'matched_address' => ['city_locality' => 'AUSTIN'],
            ],
        ], 200),
    ]);

    $address = new Address(
        name: 'Jane Doe',
        addressLine1: '123 Main St',
        cityLocality: 'Austin',
        stateProvince: 'TX',
        postalCode: '78701',
        countryCode: 'US',
    );

    $result = ShipStation::addresses()->validate($address);

    expect($result[0]['status'])->toBe('verified');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api-stage.shipstation.com/v2/addresses/validate'
            && $request->hasHeader('API-Key', 'TEST_api_key_xxx')
            && $request['0']['name'] === 'Jane Doe';
    });
});

it('shops for rates with the correct payload', function () {
    Http::fake([
        '*/v2/rates' => Http::response([
            'rate_response' => [
                'rates' => [
                    ['rate_id' => 'se-rate-1', 'shipping_amount' => ['amount' => 12.50, 'currency' => 'usd']],
                ],
            ],
        ], 200),
    ]);

    $ship = new Shipment(
        shipTo: new Address('To', '1 A', 'Austin', 'TX', '78701', 'US'),
        shipFrom: new Address('From', '1 B', 'Miami', 'FL', '33101', 'US'),
        packages: [new Package(weightValue: 2.0)],
    );

    $response = ShipStation::rates()->get($ship, ['carrier_ids' => ['se-123']]);

    expect($response['rate_response']['rates'][0]['rate_id'])->toBe('se-rate-1');
});

it('creates a label from a rate id', function () {
    Http::fake([
        '*/v2/labels/rates/se-rate-1' => Http::response([
            'label_id' => 'se-label-1',
            'status' => 'completed',
        ], 200),
    ]);

    $result = ShipStation::labels()->createFromRate('se-rate-1');

    expect($result['label_id'])->toBe('se-label-1');
});

it('voids a label', function () {
    Http::fake([
        '*/v2/labels/se-label-1/void' => Http::response([
            'approved' => true,
        ], 200),
    ]);

    $result = ShipStation::labels()->void('se-label-1');

    expect($result['approved'])->toBeTrue();
});

it('throws AuthenticationException on 401', function () {
    Http::fake([
        '*/v2/carriers' => Http::response(['errors' => [['message' => 'Unauthorized']]], 401),
    ]);

    ShipStation::carriers()->list();
})->throws(AuthenticationException::class);

it('throws ValidationException on 400', function () {
    Http::fake([
        '*/v2/addresses/validate' => Http::response(
            ['errors' => [['message' => 'Invalid address']]],
            400
        ),
    ]);

    ShipStation::addresses()->validate([
        'name' => '',
        'address_line1' => '',
        'city_locality' => '',
        'state_province' => '',
        'postal_code' => '',
        'country_code' => '',
    ]);
})->throws(ValidationException::class);

it('throws RateLimitException with retry-after info on 429', function () {
    Http::fake([
        '*/v2/carriers' => Http::response(
            ['errors' => [['message' => 'Slow down']]],
            429,
            ['X-Rate-Limit-Reset' => '30']
        ),
    ]);

    try {
        ShipStation::carriers()->list();
        $this->fail('Expected RateLimitException');
    } catch (RateLimitException $e) {
        expect($e->retryAfterSeconds)->toBe(30)
            ->and($e->statusCode)->toBe(429);
    }
});

it('tracks a package by carrier and number', function () {
    Http::fake([
        '*/v2/tracking*' => Http::response([
            'tracking_number' => '1Z999',
            'status_code' => 'DE',
        ], 200),
    ]);

    $result = ShipStation::tracking()->get('stamps_com', '1Z999');

    expect($result['tracking_number'])->toBe('1Z999');
});
