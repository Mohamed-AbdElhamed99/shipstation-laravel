<?php

declare(strict_types=1);

use Mohamed\ShipStation\Exceptions\InvalidWebhookSignatureException;
use Mohamed\ShipStation\Webhooks\SignatureVerifier;

it('verifies a valid V1 HMAC signature', function () {
    $secret = 'test_webhook_secret';
    $body = '{"resource_type":"TRACK","resource_url":"https://example.com"}';
    $expected = base64_encode(hash_hmac('sha256', $body, $secret, true));

    $verifier = new SignatureVerifier($secret);

    expect($verifier->verifyV1Hmac(['X-SS-Signature' => $expected], $body))->toBeTrue();
});

it('rejects a tampered V1 HMAC signature', function () {
    $verifier = new SignatureVerifier('test_webhook_secret');

    $verifier->verifyV1Hmac(
        ['X-SS-Signature' => 'not-a-real-signature'],
        '{"foo":"bar"}'
    );
})->throws(InvalidWebhookSignatureException::class);

it('rejects missing signature headers on V1', function () {
    $verifier = new SignatureVerifier('test_webhook_secret');

    $verifier->verifyV1Hmac([], '{}');
})->throws(InvalidWebhookSignatureException::class);

it('rejects V2 webhook when timestamp is stale', function () {
    $verifier = new SignatureVerifier('', toleranceSeconds: 60);

    $verifier->verifyV2([
        'X-ShipEngine-Timestamp' => gmdate('Y-m-d\TH:i:s\Z', time() - 3600),
        'X-ShipEngine-Signature' => 'abc',
        'X-ShipEngine-JWKS-URL'  => 'https://example.com/jwks',
    ], '{}');
})->throws(InvalidWebhookSignatureException::class, 'outside tolerance');
