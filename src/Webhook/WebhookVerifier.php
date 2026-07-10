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

namespace SoftCreatR\OpenAI\Webhook;

use InvalidArgumentException;
use JsonException;

use const FILTER_VALIDATE_INT;
use const JSON_THROW_ON_ERROR;

final class WebhookVerifier
{
    public const DEFAULT_TOLERANCE = 300;

    private const SECRET_PREFIX = 'whsec_';
    private const SIGNATURE_VERSION = 'v1';

    private string $secret;

    public function __construct(
        string               $secret,
        private readonly int $tolerance = self::DEFAULT_TOLERANCE,
    ) {
        if ($tolerance < 0) {
            throw new InvalidArgumentException('The webhook timestamp tolerance must not be negative.');
        }

        $secret = \trim($secret);
        $encodedSecret = \str_starts_with($secret, self::SECRET_PREFIX)
            ? \substr($secret, \strlen(self::SECRET_PREFIX))
            : $secret;
        $decodedSecret = \base64_decode($encodedSecret, true);

        if ($decodedSecret === false || $decodedSecret === '') {
            throw new InvalidArgumentException('The webhook secret must be a valid base64-encoded OpenAI signing secret.');
        }

        $this->secret = $decodedSecret;
    }

    /**
     * @param array<string, mixed> $headers
     *
     * @throws InvalidWebhookSignatureException
     */
    public function verify(string $rawBody, array $headers): void
    {
        $webhookId = $this->header($headers, 'webhook-id');
        $timestamp = $this->header($headers, 'webhook-timestamp');
        $signatureHeader = $this->header($headers, 'webhook-signature', true);

        $timestampValue = \filter_var($timestamp, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0],
        ]);

        if ($timestampValue === false || (string) $timestampValue !== $timestamp) {
            throw new InvalidWebhookSignatureException('The webhook timestamp is invalid.');
        }

        $now = \time();

        if ($timestampValue < $now - $this->tolerance) {
            throw new InvalidWebhookSignatureException('The webhook timestamp is too old.');
        }

        if ($timestampValue > $now + $this->tolerance) {
            throw new InvalidWebhookSignatureException('The webhook timestamp is too new.');
        }

        $signedPayload = $webhookId . '.' . $timestamp . '.' . $rawBody;
        $expectedSignature = \base64_encode(\hash_hmac('sha256', $signedPayload, $this->secret, true));

        foreach (\preg_split('/\s+/', $signatureHeader, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $signature) {
            [$version, $value] = \array_pad(\explode(',', $signature, 2), 2, null);

            if ($version === self::SIGNATURE_VERSION && \is_string($value) && \hash_equals($expectedSignature, $value)) {
                return;
            }
        }

        throw new InvalidWebhookSignatureException('The webhook signature is invalid.');
    }

    /**
     * Verifies and decodes an OpenAI webhook event without changing its signed body.
     *
     * @param array<string, mixed> $headers
     *
     * @return array<string, mixed>
     *
     * @throws WebhookException
     */
    public function unwrap(string $rawBody, array $headers): array
    {
        $this->verify($rawBody, $headers);

        try {
            $event = \json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new WebhookException('The webhook payload is not valid JSON.', 0, $exception);
        }

        if (!\is_array($event)) {
            throw new WebhookException('The webhook payload must be a JSON object.');
        }

        /** @var array<string, mixed> $event */
        return $event;
    }

    /**
     * @param array<string, mixed> $headers
     *
     * @throws InvalidWebhookSignatureException
     */
    private function header(array $headers, string $name, bool $allowMultiple = false): string
    {
        $values = [];

        foreach ($headers as $headerName => $headerValue) {
            if (\strcasecmp($headerName, $name) !== 0) {
                continue;
            }

            foreach (\is_array($headerValue) ? $headerValue : [$headerValue] as $value) {
                if (!\is_string($value) || \trim($value) === '') {
                    throw new InvalidWebhookSignatureException("The {$name} header is invalid.");
                }

                $values[] = \trim($value);
            }
        }

        if ($values === []) {
            throw new InvalidWebhookSignatureException("The {$name} header is missing.");
        }

        if (!$allowMultiple && \count($values) !== 1) {
            throw new InvalidWebhookSignatureException("The {$name} header is invalid.");
        }

        return \implode(' ', $values);
    }
}
