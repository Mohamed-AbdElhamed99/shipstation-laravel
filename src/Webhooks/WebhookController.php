<?php

declare(strict_types=1);

namespace Mohamed\ShipStation\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mohamed\ShipStation\Exceptions\InvalidWebhookSignatureException;

class WebhookController
{
    public function __construct(protected SignatureVerifier $verifier)
    {
    }

    public function __invoke(Request $request): Response
    {
        $rawBody = $request->getContent();
        $headers = [];
        foreach ($request->headers->all() as $k => $v) {
            $headers[$k] = is_array($v) ? ($v[0] ?? '') : (string) $v;
        }

        try {
            // Prefer V2 RSA verification. Fall back to V1 HMAC if V2 headers absent.
            if ($request->headers->has('x-shipengine-signature')) {
                $this->verifier->verifyV2($headers, $rawBody);
            } elseif ($request->headers->has('x-ss-signature')) {
                $this->verifier->verifyV1Hmac($headers, $rawBody);
            } else {
                // No signature headers at all — reject unless explicitly allowed
                if (config('shipstation.webhooks.secret')) {
                    return new Response('Missing signature headers', 401);
                }
            }
        } catch (InvalidWebhookSignatureException $e) {
            Log::warning('ShipStation webhook signature invalid', [
                'error' => $e->getMessage(),
            ]);
            return new Response('Invalid signature', 401);
        }

        $payload = $request->json()->all();

        Event::dispatch(new ShipStationWebhookReceived(
            resourceType: (string) ($payload['resource_type'] ?? $payload['event'] ?? 'unknown'),
            resourceUrl: $payload['resource_url'] ?? null,
            payload: $payload,
            headers: $headers,
        ));

        return new Response('', 200);
    }
}
