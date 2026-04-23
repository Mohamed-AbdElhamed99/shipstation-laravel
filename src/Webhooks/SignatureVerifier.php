<?php

declare(strict_types=1);

namespace Mohamed\ShipStation\Webhooks;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mohamed\ShipStation\Exceptions\InvalidWebhookSignatureException;

/**
 * Verifies incoming ShipStation / ShipEngine webhook signatures.
 *
 * V2 / ShipEngine-style webhooks use RSA-SHA256 with a JWKS endpoint
 * (headers: x-shipengine-timestamp, x-shipengine-signature, x-shipengine-jwks-url).
 *
 * V1 ShipStation-style webhooks use HMAC-SHA256 with a shared secret and
 * the X-SS-Signature header. The shared "secret" here is the API secret.
 */
class SignatureVerifier
{
    public const JWKS_CACHE_KEY = 'shipstation.webhook.jwks';
    public const JWKS_CACHE_TTL = 3600; // 1 hour

    public function __construct(
        protected string $secret = '',
        protected int $toleranceSeconds = 300,
    ) {
    }

    /**
     * Verify a V2/ShipEngine RSA-SHA256 signed webhook.
     *
     * @param  array<string, string>  $headers  Case-insensitive. Pass request headers.
     * @param  string  $rawBody  The *raw* request body string (not json-decoded).
     * @throws InvalidWebhookSignatureException
     */
    public function verifyV2(array $headers, string $rawBody): bool
    {
        $headers = $this->normalizeHeaders($headers);

        $timestamp = $headers['x-shipengine-timestamp'] ?? null;
        $signature = $headers['x-shipengine-signature'] ?? null;
        $jwksUrl   = $headers['x-shipengine-jwks-url'] ?? null;

        if (! $timestamp || ! $signature || ! $jwksUrl) {
            throw new InvalidWebhookSignatureException(
                'Missing required ShipEngine webhook signature headers.'
            );
        }

        $this->assertFreshTimestamp($timestamp);

        $publicKey = $this->fetchPublicKey($jwksUrl);
        $signedPayload = $timestamp . '.' . $rawBody;

        $decodedSignature = base64_decode($signature, true);
        if ($decodedSignature === false) {
            throw new InvalidWebhookSignatureException('Signature is not valid base64.');
        }

        $result = openssl_verify(
            data: $signedPayload,
            signature: $decodedSignature,
            public_key: $publicKey,
            algorithm: OPENSSL_ALGO_SHA256,
        );

        if ($result === 1) {
            return true;
        }

        throw new InvalidWebhookSignatureException(
            $result === 0
                ? 'Webhook signature verification failed.'
                : 'Error during webhook signature verification: ' . openssl_error_string()
        );
    }

    /**
     * Verify a V1 HMAC-SHA256 signed webhook (X-SS-Signature header).
     * The secret used is your ShipStation API secret.
     *
     * @param  array<string, string>  $headers
     * @throws InvalidWebhookSignatureException
     */
    public function verifyV1Hmac(array $headers, string $rawBody): bool
    {
        if ($this->secret === '') {
            throw new InvalidWebhookSignatureException(
                'Webhook secret is not configured. Set SHIPSTATION_WEBHOOK_SECRET.'
            );
        }

        $headers = $this->normalizeHeaders($headers);
        $provided = $headers['x-ss-signature'] ?? null;

        if (! $provided) {
            throw new InvalidWebhookSignatureException('Missing X-SS-Signature header.');
        }

        $expected = base64_encode(hash_hmac('sha256', $rawBody, $this->secret, true));

        if (! hash_equals($expected, $provided)) {
            throw new InvalidWebhookSignatureException('HMAC signature mismatch.');
        }

        return true;
    }

    /**
     * @return \OpenSSLAsymmetricKey
     */
    protected function fetchPublicKey(string $jwksUrl): \OpenSSLAsymmetricKey
    {
        $cacheKey = self::JWKS_CACHE_KEY . ':' . md5($jwksUrl);

        $pem = Cache::remember($cacheKey, self::JWKS_CACHE_TTL, function () use ($jwksUrl) {
            $response = Http::timeout(10)->get($jwksUrl);

            if (! $response->successful()) {
                throw new InvalidWebhookSignatureException(
                    "Unable to fetch JWKS from {$jwksUrl}"
                );
            }

            $jwks = $response->json('keys', []);
            if (empty($jwks) || ! isset($jwks[0]['n'], $jwks[0]['e'])) {
                throw new InvalidWebhookSignatureException(
                    'JWKS response is malformed or has no usable keys.'
                );
            }

            return $this->jwkToPem($jwks[0]);
        });

        $key = openssl_pkey_get_public($pem);
        if ($key === false) {
            throw new InvalidWebhookSignatureException('Could not parse public key from JWKS.');
        }

        return $key;
    }

    /**
     * Convert a JWK (RSA) to a PEM-encoded public key.
     *
     * @param  array{n: string, e: string}  $jwk
     */
    protected function jwkToPem(array $jwk): string
    {
        $modulus = $this->base64UrlDecode($jwk['n']);
        $exponent = $this->base64UrlDecode($jwk['e']);

        // Build an ASN.1 RSAPublicKey sequence, then wrap in SubjectPublicKeyInfo.
        $modulusHex = '00' . bin2hex($modulus);
        $exponentHex = bin2hex($exponent);

        $modulusSeq = $this->asn1Integer($modulusHex);
        $exponentSeq = $this->asn1Integer($exponentHex);

        $rsaPublicKey = $this->asn1Sequence($modulusSeq . $exponentSeq);

        // OID 1.2.840.113549.1.1.1 rsaEncryption, NULL, BIT STRING(rsaPublicKey)
        $algorithmIdentifier = $this->asn1Sequence(
            '06092a864886f70d010101' . '0500'
        );
        $bitString = '03' . $this->asn1Length(strlen($rsaPublicKey) / 2 + 1) . '00' . $rsaPublicKey;
        $subjectPublicKeyInfo = $this->asn1Sequence($algorithmIdentifier . $bitString);

        $derBinary = hex2bin($subjectPublicKeyInfo);

        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($derBinary), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }

    protected function asn1Integer(string $hex): string
    {
        return '02' . $this->asn1Length(strlen($hex) / 2) . $hex;
    }

    protected function asn1Sequence(string $hex): string
    {
        return '30' . $this->asn1Length(strlen($hex) / 2) . $hex;
    }

    protected function asn1Length(int $length): string
    {
        if ($length < 128) {
            return str_pad(dechex($length), 2, '0', STR_PAD_LEFT);
        }

        $hex = dechex($length);
        if (strlen($hex) % 2 !== 0) {
            $hex = '0' . $hex;
        }

        $bytes = strlen($hex) / 2;
        return dechex(0x80 | $bytes) . $hex;
    }

    protected function base64UrlDecode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder > 0) {
            $input .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($input, '-_', '+/'), true);
        if ($decoded === false) {
            throw new InvalidWebhookSignatureException('Invalid base64url data in JWK.');
        }

        return $decoded;
    }

    protected function assertFreshTimestamp(string $timestamp): void
    {
        $ts = strtotime($timestamp);
        if ($ts === false) {
            throw new InvalidWebhookSignatureException('Invalid webhook timestamp.');
        }

        $diff = abs(time() - $ts);
        if ($diff > $this->toleranceSeconds) {
            throw new InvalidWebhookSignatureException(
                "Webhook timestamp outside tolerance window ({$diff}s)."
            );
        }
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, string>
     */
    protected function normalizeHeaders(array $headers): array
    {
        $out = [];
        foreach ($headers as $key => $value) {
            $out[strtolower((string) $key)] = is_array($value) ? (string) ($value[0] ?? '') : (string) $value;
        }
        return $out;
    }
}
