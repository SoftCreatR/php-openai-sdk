<?php

/*
 * Copyright (c) 2023, Sascha Greuel and Contributors
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

namespace SoftCreatR\OpenAI;

use InvalidArgumentException;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

/**
 * Creates URLs for OpenAI API endpoints.
 */
final class OpenAIURLBuilder
{
    public const ORIGIN = 'api.openai.com';

    public const API_VERSION = 'v1';

    private const HTTP_METHOD_GET = 'GET';

    private const HTTP_METHOD_POST = 'POST';

    private const HTTP_METHOD_DELETE = 'DELETE';

    /**
     * @var array<string, array<string, string>> OpenAI API endpoints configuration.
     */
    private static array $urlEndpoints = [
        // Audio: https://platform.openai.com/docs/api-reference/audio
        'createSpeech' => ['method' => self::HTTP_METHOD_POST, 'path' => '/audio/speech'],
        'createTranscription' => ['method' => self::HTTP_METHOD_POST, 'path' => '/audio/transcriptions'],
        'createTranslation' => ['method' => self::HTTP_METHOD_POST, 'path' => '/audio/translations'],

        // Chat Completions: https://platform.openai.com/docs/api-reference/chat
        'createChatCompletion' => ['method' => self::HTTP_METHOD_POST, 'path' => '/chat/completions'],

        // Embeddings: https://platform.openai.com/docs/api-reference/embeddings
        'createEmbedding' => ['method' => self::HTTP_METHOD_POST, 'path' => '/embeddings'],

        // Fine-tuning: https://platform.openai.com/docs/api-reference/fine-tuning
        'createFineTuningJob' => ['method' => self::HTTP_METHOD_POST, 'path' => '/fine_tuning/jobs'],
        'listFineTuningJobs' => ['method' => self::HTTP_METHOD_GET, 'path' => '/fine_tuning/jobs'],
        'retrieveFineTuningJob' => ['method' => self::HTTP_METHOD_GET, 'path' => '/fine_tuning/jobs/%s'],
        'cancelFineTuning' => ['method' => self::HTTP_METHOD_POST, 'path' => '/fine_tuning/jobs/%s/cancel'],
        'listFineTuningEvents' => ['method' => self::HTTP_METHOD_GET, 'path' => '/fine_tuning/jobs/%s/events'],

        // Files: https://platform.openai.com/docs/api-reference/files
        'listFiles' => ['method' => self::HTTP_METHOD_GET, 'path' => '/files'],
        'createFile' => ['method' => self::HTTP_METHOD_POST, 'path' => '/files'],
        'deleteFile' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/files/%s'],
        'retrieveFile' => ['method' => self::HTTP_METHOD_GET, 'path' => '/files/%s'],
        'downloadFile' => ['method' => self::HTTP_METHOD_GET, 'path' => '/files/%s/content'],

        // Images: https://platform.openai.com/docs/api-reference/images
        'createImage' => ['method' => self::HTTP_METHOD_POST, 'path' => '/images/generations'],
        'createImageEdit' => ['method' => self::HTTP_METHOD_POST, 'path' => '/images/edits'],
        'createImageVariation' => ['method' => self::HTTP_METHOD_POST, 'path' => '/images/variations'],

        // Models: https://platform.openai.com/docs/api-reference/models
        'listModels' => ['method' => self::HTTP_METHOD_GET, 'path' => '/models'],
        'retrieveModel' => ['method' => self::HTTP_METHOD_GET, 'path' => '/models/%s'],
        'deleteModel' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/models/%s'],

        // Moderations: https://platform.openai.com/docs/api-reference/moderations
        'createModeration' => ['method' => self::HTTP_METHOD_POST, 'path' => '/moderations'],
    ];

    /**
     * Gets the OpenAI API endpoint configuration.
     *
     * @param string $key The endpoint key.
     *
     * @return array<string, string> The endpoint configuration.
     *
     * @throws InvalidArgumentException If the provided key is invalid.
     */
    public static function getEndpoint(string $key): array
    {
        if (!isset(self::$urlEndpoints[$key])) {
            throw new InvalidArgumentException('Invalid OpenAI URL key "' . $key . '".');
        }

        return self::$urlEndpoints[$key];
    }

    /**
     * Creates a URL for the specified OpenAI API endpoint.
     *
     * @param UriFactoryInterface $uriFactory The PSR-17 URI factory instance used for creating URIs.
     * @param string $key The key representing the API endpoint.
     * @param string|null $parameter Optional parameter to replace in the endpoint path.
     * @param string $origin Custom origin (Hostname), if needed.
     *
     * @return UriInterface The fully constructed URL for the API endpoint.
     *
     * @throws InvalidArgumentException If the provided key is invalid.
     */
    public static function createUrl(
        UriFactoryInterface $uriFactory,
        string $key,
        ?string $parameter = null,
        string $origin = '',
        ?string $apiVersion = null
    ): UriInterface {
        $endpoint = self::getEndpoint($key);
        $path = self::replacePathParameters($endpoint['path'], $parameter);

        return $uriFactory
            ->createUri()
            ->withScheme('https')
            ->withHost($origin ?: self::ORIGIN)
            ->withPath(($apiVersion ?? self::API_VERSION) . $path);
    }

    /**
     * Replaces path parameters in the given path with provided parameter value.
     *
     * @param string $path The path containing the parameter placeholder.
     * @param string|null $parameter The parameter value to replace the placeholder with.
     *
     * @return string The path with replaced parameter value.
     */
    private static function replacePathParameters(string $path, ?string $parameter = null): string
    {
        if ($parameter !== null) {
            return \sprintf($path, $parameter);
        }

        return $path;
    }
}
