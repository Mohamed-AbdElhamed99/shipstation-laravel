<?php

declare(strict_types=1);

namespace Mohamed\ShipStation;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Mohamed\ShipStation\Client\ShipStationClient;
use Mohamed\ShipStation\Webhooks\SignatureVerifier;

class ShipStationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/shipstation.php',
            'shipstation'
        );

        $this->app->singleton(ShipStationClient::class, function ($app) {
            return new ShipStationClient(
                $app->make(\Illuminate\Http\Client\Factory::class),
                $app['config'],
            );
        });

        // String alias so the facade can resolve it
        $this->app->alias(ShipStationClient::class, 'shipstation');

        $this->app->singleton(SignatureVerifier::class, function ($app) {
            return new SignatureVerifier(
                secret: (string) $app['config']->get('shipstation.webhooks.secret', ''),
                toleranceSeconds: (int) $app['config']->get('shipstation.webhooks.tolerance_seconds', 300),
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/shipstation.php' => config_path('shipstation.php'),
            ], 'shipstation-config');
        }
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            ShipStationClient::class,
            SignatureVerifier::class,
            'shipstation',
        ];
    }
}
