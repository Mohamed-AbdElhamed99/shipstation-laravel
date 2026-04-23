# ShipStation Laravel

A Laravel-first PHP client for the **ShipStation API v2** (formerly ShipEngine). Built for modern Laravel (10/11/12) and PHP 8.2+, with zero dependency conflicts.

## Why this package?

The official `shipengine/shipengine` SDK is locked to `psr/http-message ^1.0` and `symfony/event-dispatcher ^5.2`, making it **impossible to install in any modern Laravel app**. This package uses Laravel's own `Http` facade under the hood — no conflicts, ever.

## Features

- ✅ Address validation
- ✅ Rate shopping across carriers
- ✅ Label creation (from shipment or from rate)
- ✅ Label voiding & return labels
- ✅ Package tracking (pull & push)
- ✅ Webhook signature verification (V2 RSA/JWKS **and** V1 HMAC)
- ✅ Typed DTOs for addresses, packages, shipments
- ✅ Typed exceptions (`AuthenticationException`, `RateLimitException`, `ValidationException`, `NotFoundException`)
- ✅ Automatic retries on 5xx / 429 / connection errors
- ✅ Sandbox & production environment support
- ✅ Fully testable with `Http::fake()`

## Requirements

- PHP ^8.2
- Laravel ^10.0 | ^11.0 | ^12.0

## Installation

Because this is a private package, add the repository first:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:yourname/shipstation-laravel.git"
        }
    ]
}
```

Then:

```bash
composer require mohamed/shipstation-laravel:^0.1
php artisan vendor:publish --tag=shipstation-config
```

Set your API key in `.env`:

```env
SHIPSTATION_API_KEY=your_api_key_here
SHIPSTATION_ENV=production
SHIPSTATION_WEBHOOK_SECRET=your_webhook_secret
```

## Usage

### Address validation

```php
use Mohamed\ShipStation\Facades\ShipStation;
use Mohamed\ShipStation\DTO\Address;

$address = new Address(
    name: 'Jane Doe',
    addressLine1: '123 Main St',
    cityLocality: 'Austin',
    stateProvince: 'TX',
    postalCode: '78701',
    countryCode: 'US',
);

$result = ShipStation::addresses()->validateOne($address);
// $result['status'] === 'verified' | 'unverified' | 'warning' | 'error'
```

### Rate shopping

```php
use Mohamed\ShipStation\DTO\{Address, Package, Shipment};

$shipment = new Shipment(
    shipTo:   new Address('Customer', '123 Main', 'Austin', 'TX', '78701', 'US'),
    shipFrom: new Address('Warehouse', '500 Industrial', 'Miami', 'FL', '33101', 'US'),
    packages: [
        new Package(weightValue: 2.5, weightUnit: 'pound', length: 12, width: 8, height: 6),
    ],
);

$rates = ShipStation::rates()->get($shipment, [
    'carrier_ids' => ['se-123456'],
]);

foreach ($rates['rate_response']['rates'] as $rate) {
    // $rate['rate_id'], $rate['shipping_amount'], $rate['carrier_friendly_name']
}
```

### Create a label

```php
// From a rate you already shopped for:
$label = ShipStation::labels()->createFromRate('se-rate-abc');

// Or directly from a shipment:
$label = ShipStation::labels()->create($shipment);

// PDF URL:
$pdfUrl = $label['label_download']['pdf'];
```

### Void a label

```php
$result = ShipStation::labels()->void('se-label-xxx');
// $result['approved'] === true
```

### Tracking

```php
$tracking = ShipStation::tracking()->get('stamps_com', '9400111899560000000000');
// Push notifications:
ShipStation::tracking()->startTracking('stamps_com', '9400...');
```

### Webhooks

Route:

```php
use Mohamed\ShipStation\Webhooks\WebhookController;

Route::post('/webhooks/shipstation', WebhookController::class)
    ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
```

Listen to events:

```php
use Mohamed\ShipStation\Webhooks\ShipStationWebhookReceived;

Event::listen(function (ShipStationWebhookReceived $event) {
    if ($event->isTrackingEvent()) {
        // $event->resourceUrl — fetch details with ShipStation::get($event->resourceUrl)
        // $event->payload
    }
});
```

The controller automatically verifies signatures (V2 RSA/JWKS preferred, V1 HMAC fallback), returns `401` on failure, and fires the event only on success.

### Error handling

```php
use Mohamed\ShipStation\Exceptions\{
    AuthenticationException, RateLimitException,
    ValidationException, NotFoundException, ShipStationException
};

try {
    $label = ShipStation::labels()->createFromRate($rateId);
} catch (RateLimitException $e) {
    retry_after_seconds($e->retryAfterSeconds ?? 60);
} catch (ValidationException $e) {
    foreach ($e->errors as $error) {
        // $error['message'], $error['error_code'], $error['field_name']
    }
} catch (AuthenticationException $e) {
    // Invalid API key
} catch (ShipStationException $e) {
    // Anything else from ShipStation
}
```

### Lower-level HTTP access

For endpoints not yet wrapped by a resource class:

```php
$data = ShipStation::get('/warehouses');
$data = ShipStation::post('/manifests', [...]);
```

## Testing

The package ships with Pest tests. Run them with:

```bash
composer test
```

In your own app, you can mock ShipStation responses using Laravel's `Http::fake()`:

```php
Http::fake([
    '*/v2/labels' => Http::response(['label_id' => 'se-fake-1'], 200),
]);

$label = ShipStation::labels()->create($shipment);
```

## Configuration reference

See `config/shipstation.php` after publishing. Key options:

| Key | Default | Purpose |
|-----|---------|---------|
| `api_key` | `env('SHIPSTATION_API_KEY')` | Your ShipStation API key |
| `environment` | `production` | `production` or `sandbox` |
| `timeout` | `30` | Request timeout (seconds) |
| `retries` | `2` | Auto-retries on 5xx/429 |
| `retry_delay_ms` | `500` | Base delay between retries |
| `webhooks.secret` | `env('SHIPSTATION_WEBHOOK_SECRET')` | HMAC secret for V1 webhooks |
| `webhooks.tolerance_seconds` | `300` | Max age of V2 timestamp |

## License

MIT
