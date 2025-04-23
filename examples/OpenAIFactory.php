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

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use SoftCreatR\OpenAI\OpenAI;
use SoftCreatR\OpenAI\OpenAIURLBuilder;

/**
 * Load .env from project root if present
 */
$projectRoot = \dirname(__DIR__);

if (\file_exists($projectRoot . '/.env')) {
    Dotenv::createImmutable($projectRoot)->load();
}

/**
 * Example factory class for creating and using the OpenAI client.
 */
final class OpenAIFactory
{
    private function __construct() {}

    /**
     * Create an OpenAI client.
     *
     * @param string $apiKey
     * @return OpenAI
     */
    public static function create(
        #[SensitiveParameter]
        string $apiKey = ''
    ): OpenAI {
        $psr17Factory = new HttpFactory();
        $httpClient = new Client(['stream' => true]);

        return new OpenAI(
            requestFactory: $psr17Factory,
            streamFactory: $psr17Factory,
            uriFactory: $psr17Factory,
            httpClient: $httpClient,
            apiKey: $apiKey,
            organization: $_ENV['OPENAI_ORGANIZATION_ID'] ?? '',
            origin: $_ENV['OPENAI_API_ORIGIN'] ?? '',
            basePath: $_ENV['OPENAI_API_BASE_PATH'] ?? '',
        );
    }

    /**
     * Send a generic request to an OpenAI endpoint.
     *
     * @param string         $method         Method name, e.g. 'createChatCompletion'
     * @param array          $parameters     URL/path params
     * @param array          $options        Body or query params
     * @param callable|null  $streamCallback Stream callback for SSE
     * @param bool           $returnResponse If true, returns raw body
     * @param bool           $useAdminKey    If true, uses $_ENV['OPENAI_ADMIN_KEY']
     */
    public static function request(
        string $method,
        array $parameters = [],
        array $options = [],
        ?callable $streamCallback = null,
        bool $returnResponse = false,
        bool $useAdminKey = false
    ): mixed {
        $keyName = $useAdminKey ? 'OPENAI_ADMIN_KEY' : 'OPENAI_API_KEY';
        $openAI = self::create($_ENV[$keyName] ?? '');

        try {
            $endpoint = OpenAIURLBuilder::getEndpoint($method);
            $path = $endpoint['path'];
            $hasPlaceholders = (bool)\preg_match('/\{\w+}/', $path);

            if ($hasPlaceholders) {
                $urlParams = $parameters;
                $bodyOpts = $options;
            } else {
                $urlParams = [];
                $bodyOpts = $parameters + $options;
            }

            if ($streamCallback !== null) {
                $openAI->{$method}($urlParams, $bodyOpts, $streamCallback);

                return null;
            }

            $response = $openAI->{$method}($urlParams, $bodyOpts);

            if ($returnResponse) {
                return $response->getBody()->getContents();
            }

            $contentType = $response->getHeaderLine('Content-Type');
            $body = $response->getBody()->getContents();

            if (\str_contains($contentType, 'application/json')) {
                $decoded = \json_decode($body, true, 512, \JSON_THROW_ON_ERROR);
                echo "============\n| Response |\n============\n\n"
                    . \json_encode($decoded, \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR)
                    . "\n\n============\n";
            } else {
                echo "Received response with Content-Type: {$contentType}\n";
                echo $body;
            }
        } catch (Exception $e) {
            echo "Error: {$e->getMessage()}\n";
        }

        return null;
    }

    /**
     * Send an administrative request.
     */
    public static function adminRequest(
        string $method,
        array $parameters = [],
        array $options = [],
        ?callable $streamCallback = null,
        bool $returnResponse = false
    ): mixed {
        return self::request(
            $method,
            $parameters,
            $options,
            $streamCallback,
            $returnResponse,
            true
        );
    }
}
