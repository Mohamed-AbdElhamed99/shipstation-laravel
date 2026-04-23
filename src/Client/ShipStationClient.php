<?php

declare(strict_types=1);

namespace Mohamed\ShipStation\Client;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Traits\Macroable;
use Mohamed\ShipStation\Exceptions\AuthenticationException;
use Mohamed\ShipStation\Exceptions\NotFoundException;
use Mohamed\ShipStation\Exceptions\RateLimitException;
use Mohamed\ShipStation\Exceptions\ShipStationException;
use Mohamed\ShipStation\Exceptions\ValidationException;
use Mohamed\ShipStation\Resources\Addresses;
use Mohamed\ShipStation\Resources\Carriers;
use Mohamed\ShipStation\Resources\Labels;
use Mohamed\ShipStation\Resources\Rates;
use Mohamed\ShipStation\Resources\Tracking;
use Mohamed\ShipStation\Resources\Webhooks;

class ShipStationClient
{
    use Macroable;

    public function __construct(
        protected HttpFactory $http,
        protected Config $config,
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Resource accessors
    |--------------------------------------------------------------------------
    */

    public function addresses(): Addresses
    {
        return new Addresses($this);
    }

    public function rates(): Rates
    {
        return new Rates($this);
    }

    public function labels(): Labels
    {
        return new Labels($this);
    }

    public function tracking(): Tracking
    {
        return new Tracking($this);
    }

    public function carriers(): Carriers
    {
        return new Carriers($this);
    }

    public function webhooks(): Webhooks
    {
        return new Webhooks($this);
    }

    /*
    |--------------------------------------------------------------------------
    | Low-level HTTP methods (usable by Resources or directly)
    |--------------------------------------------------------------------------
    */

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $uri, array $query = []): array
    {
        return $this->send('get', $uri, $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function post(string $uri, array $payload = []): array
    {
        return $this->send('post', $uri, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function put(string $uri, array $payload = []): array
    {
        return $this->send('put', $uri, $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $uri): array
    {
        return $this->send('delete', $uri);
    }

    /*
    |--------------------------------------------------------------------------
    | Request pipeline
    |--------------------------------------------------------------------------
    */

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function send(string $method, string $uri, array $data = []): array
    {
        $request = $this->pendingRequest();

        $response = match (strtolower($method)) {
            'get'    => $request->get($uri, $data),
            'post'   => $request->post($uri, $data),
            'put'    => $request->put($uri, $data),
            'delete' => $request->delete($uri),
            default  => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };

        return $this->handleResponse($response);
    }

    protected function pendingRequest(): PendingRequest
    {
        $env = $this->config->get('shipstation.environment', 'production');
        $baseUrl = $this->config->get("shipstation.base_urls.{$env}");
        $apiKey = $this->config->get('shipstation.api_key');

        if (! $apiKey) {
            throw new AuthenticationException(
                'ShipStation API key is not configured. Set SHIPSTATION_API_KEY in your .env file.'
            );
        }

        $request = $this->http
            ->baseUrl($baseUrl)
            ->withHeaders([
                'API-Key'      => $apiKey,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])
            ->timeout((int) $this->config->get('shipstation.timeout', 30))
            ->connectTimeout((int) $this->config->get('shipstation.connect_timeout', 10))
            ->retry(
                times: (int) $this->config->get('shipstation.retries', 2),
                sleepMilliseconds: (int) $this->config->get('shipstation.retry_delay_ms', 500),
                when: fn (\Throwable $e) => $this->shouldRetry($e),
                throw: false,
            );

        if ($this->config->get('shipstation.log_requests')) {
            $channel = $this->config->get('shipstation.log_channel');
            $logger = $channel ? Log::channel($channel) : Log::getLogger();

            $request->beforeSending(function ($r) use ($logger): void {
                $logger->debug('ShipStation request', [
                    'method' => $r->method(),
                    'url'    => $r->url(),
                ]);
            });
        }

        return $request;
    }

    protected function shouldRetry(\Throwable $e): bool
    {
        // Retry on connection errors and 5xx, but NOT on 4xx (auth, validation).
        if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
            return true;
        }
        if ($e instanceof \Illuminate\Http\Client\RequestException) {
            $status = $e->response->status();
            return $status >= 500 || $status === 429;
        }
        return false;
    }

    /**
     * @return array<string, mixed>
     */
    protected function handleResponse(Response $response): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $body = $response->json() ?? [];
        $status = $response->status();

        throw match (true) {
            $status === 401,
            $status === 403 => AuthenticationException::fromResponse($body, $status),

            $status === 404 => NotFoundException::fromResponse($body, $status),

            $status === 422,
            $status === 400 => ValidationException::fromResponse($body, $status),

            $status === 429 => new RateLimitException(
                message: ($body['errors'][0]['message'] ?? null) ?: 'Rate limit exceeded',
                code: 429,
                requestId: $body['request_id'] ?? null,
                errors: $body['errors'] ?? [],
                statusCode: 429,
                retryAfterSeconds: (int) ($response->header('X-Rate-Limit-Reset') ?: 0) ?: null,
            ),

            default => ShipStationException::fromResponse($body, $status),
        };
    }
}
