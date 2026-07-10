<?php

/*
 * Copyright (c) 2023-present, Sascha Greuel and Contributors
 *
 * Permission to use, copy, modify, and/or distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

namespace SoftCreatR\OpenAI\Tests\Webhook;

use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\TestCase;
use SoftCreatR\OpenAI\Webhook\InvalidWebhookSignatureException;
use SoftCreatR\OpenAI\Webhook\WebhookException;
use SoftCreatR\OpenAI\Webhook\WebhookVerifier;

/**
 * @covers \SoftCreatR\OpenAI\Webhook\InvalidWebhookSignatureException
 * @covers \SoftCreatR\OpenAI\Webhook\WebhookException
 * @covers \SoftCreatR\OpenAI\Webhook\WebhookVerifier
 */
final class WebhookVerifierTest extends TestCase
{
    private const RAW_BODY = '{"object":"event","id":"evt_test","type":"response.completed","data":{"id":"resp_test"}}';
    private const SECRET = '0123456789abcdef0123456789abcdef';
    private const WEBHOOK_ID = 'wh_test_123';

    /**
     * @throws WebhookException
     */
    public function testVerifiesAndUnwrapsUsingCaseInsensitiveArrayHeaders(): void
    {
        $timestamp = \time();
        $headers = [
            'Webhook-Id' => [self::WEBHOOK_ID],
            'WEBHOOK-TIMESTAMP' => [(string) $timestamp],
            'webhook-Signature' => [$this->signature(self::RAW_BODY, $timestamp)],
        ];

        $event = $this->verifier()->unwrap(self::RAW_BODY, $headers);

        $this->assertSame('evt_test', $event['id']);
        $this->assertSame('response.completed', $event['type']);
        $this->assertSame(['id' => 'resp_test'], $event['data']);
    }

    /**
     * @throws InvalidWebhookSignatureException
     */
    public function testAcceptsAnyMatchingV1SignatureDuringSecretRotation(): void
    {
        $timestamp = \time();
        $headers = $this->headers(
            $timestamp,
            'v1,' . \base64_encode(\str_repeat('x', 32)) . ' v2,ignored ' . $this->signature(self::RAW_BODY, $timestamp),
        );

        $this->verifier()->verify(self::RAW_BODY, $headers);

        $this->addToAssertionCount(1);
    }

    /**
     * @throws InvalidWebhookSignatureException
     */
    public function testAcceptsAnUnprefixedSigningSecret(): void
    {
        $timestamp = \time();
        $verifier = new WebhookVerifier(\base64_encode(self::SECRET));

        $verifier->verify(self::RAW_BODY, $this->headers($timestamp));

        $this->addToAssertionCount(1);
    }

    public function testTreatsTheRequestBodyAsRawSignedBytes(): void
    {
        $timestamp = \time();
        $headers = $this->headers($timestamp, $this->signature(self::RAW_BODY, $timestamp));

        $this->expectException(InvalidWebhookSignatureException::class);
        $this->expectExceptionMessage('signature is invalid');

        $this->verifier()->verify(self::RAW_BODY . "\n", $headers);
    }

    public function testRejectsAnInvalidSignature(): void
    {
        $timestamp = \time();

        $this->expectException(InvalidWebhookSignatureException::class);
        $this->expectExceptionMessage('signature is invalid');

        $this->verifier()->verify(self::RAW_BODY, $this->headers($timestamp, 'v1,invalid'));
    }

    public function testRejectsMissingRequiredHeaders(): void
    {
        $this->expectException(InvalidWebhookSignatureException::class);
        $this->expectExceptionMessage('webhook-id header is missing');

        $this->verifier()->verify(self::RAW_BODY, []);
    }

    public function testRejectsAnExpiredTimestamp(): void
    {
        $timestamp = \time() - WebhookVerifier::DEFAULT_TOLERANCE - 1;

        $this->expectException(InvalidWebhookSignatureException::class);
        $this->expectExceptionMessage('timestamp is too old');

        $this->verifier()->verify(self::RAW_BODY, $this->headers($timestamp));
    }

    public function testRejectsATimestampTooFarInTheFuture(): void
    {
        $timestamp = \time() + WebhookVerifier::DEFAULT_TOLERANCE + 1;

        $this->expectException(InvalidWebhookSignatureException::class);
        $this->expectExceptionMessage('timestamp is too new');

        $this->verifier()->verify(self::RAW_BODY, $this->headers($timestamp));
    }

    /**
     * @throws InvalidWebhookSignatureException
     */
    public function testUsesTheConfiguredTimestampTolerance(): void
    {
        $timestamp = \time() - WebhookVerifier::DEFAULT_TOLERANCE - 1;

        $this->verifier(600)->verify(self::RAW_BODY, $this->headers($timestamp));

        $this->addToAssertionCount(1);
    }

    public function testRejectsMalformedTimestampsBeforeSignatureComparison(): void
    {
        $this->expectException(InvalidWebhookSignatureException::class);
        $this->expectExceptionMessage('timestamp is invalid');

        $this->verifier()->verify(self::RAW_BODY, [
            'webhook-id' => self::WEBHOOK_ID,
            'webhook-timestamp' => '1e3',
            'webhook-signature' => 'v1,invalid',
        ]);
    }

    /**
     * @throws WebhookException
     */
    public function testRejectsAValidSignedPayloadThatIsNotAJsonObject(): void
    {
        $rawBody = 'null';
        $timestamp = \time();

        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('payload must be a JSON object');

        $this->verifier()->unwrap($rawBody, $this->headers($timestamp, $this->signature($rawBody, $timestamp)));
    }

    /**
     * @throws InvalidWebhookSignatureException
     */
    public function testRejectsANonStringHeaderValue(): void
    {
        $timestamp = \time();

        $this->expectException(InvalidWebhookSignatureException::class);
        $this->expectExceptionMessage('webhook-id header is invalid');

        $this->verifier()->verify(self::RAW_BODY, [
            'webhook-id' => null,
            'webhook-timestamp' => (string) $timestamp,
            'webhook-signature' => $this->signature(self::RAW_BODY, $timestamp),
        ]);
    }

    /**
     * @throws InvalidWebhookSignatureException
     */
    public function testRejectsDuplicateSingleValueHeaders(): void
    {
        $timestamp = \time();

        $this->expectException(InvalidWebhookSignatureException::class);
        $this->expectExceptionMessage('webhook-id header is invalid');

        $this->verifier()->verify(self::RAW_BODY, [
            'webhook-id' => [self::WEBHOOK_ID, self::WEBHOOK_ID],
            'webhook-timestamp' => (string) $timestamp,
            'webhook-signature' => $this->signature(self::RAW_BODY, $timestamp),
        ]);
    }

    public function testWrapsJsonDecodingFailuresInAWebhookException(): void
    {
        $rawBody = '{not-json}';
        $timestamp = \time();

        try {
            $this->verifier()->unwrap($rawBody, $this->headers($timestamp, $this->signature($rawBody, $timestamp)));
            $this->fail('Expected malformed webhook JSON to be rejected.');
        } catch (WebhookException $exception) {
            $this->assertSame('The webhook payload is not valid JSON.', $exception->getMessage());
            $this->assertInstanceOf(JsonException::class, $exception->getPrevious());
        }
    }

    public function testRejectsANegativeTimestampTolerance(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tolerance must not be negative');

        new WebhookVerifier('whsec_' . \base64_encode(self::SECRET), -1);
    }

    public function testRejectsAnInvalidSigningSecret(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('valid base64-encoded OpenAI signing secret');

        new WebhookVerifier('whsec_not base64');
    }

    /**
     * @return array<string, string>
     */
    private function headers(int $timestamp, ?string $signature = null): array
    {
        return [
            'webhook-id' => self::WEBHOOK_ID,
            'webhook-timestamp' => (string) $timestamp,
            'webhook-signature' => $signature ?? $this->signature(self::RAW_BODY, $timestamp),
        ];
    }

    private function signature(string $rawBody, int $timestamp): string
    {
        $signedPayload = self::WEBHOOK_ID . '.' . $timestamp . '.' . $rawBody;

        return 'v1,' . \base64_encode(\hash_hmac('sha256', $signedPayload, self::SECRET, true));
    }

    private function verifier(int $tolerance = WebhookVerifier::DEFAULT_TOLERANCE): WebhookVerifier
    {
        return new WebhookVerifier('whsec_' . \base64_encode(self::SECRET), $tolerance);
    }
}
