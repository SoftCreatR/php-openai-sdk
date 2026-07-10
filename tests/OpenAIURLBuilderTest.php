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

namespace SoftCreatR\OpenAI\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use SoftCreatR\OpenAI\OpenAIURLBuilder;

/**
 * @covers \SoftCreatR\OpenAI\OpenAIURLBuilder
 */
final class OpenAIURLBuilderTest extends TestCase
{
    /**
     * Tests the constructor of OpenAIURLBuilder to ensure it's covered.
     *
     * @throws ReflectionException
     */
    public function testOpenAIURLBuilderConstructor(): void
    {
        $constructor = TestHelper::getPrivateConstructor(OpenAIURLBuilder::class);

        $reflectionClass = new ReflectionClass(OpenAIURLBuilder::class);
        $instance = $reflectionClass->newInstanceWithoutConstructor();

        // Invoke the constructor
        $constructor->invoke($instance);

        $this->assertInstanceOf(OpenAIURLBuilder::class, $instance);
    }

    /**
     * Tests that getEndpoint throws an exception for an invalid key.
     */
    public function testGetEndpointWithInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid OpenAI URL key "invalidKey".');

        OpenAIURLBuilder::getEndpoint('invalidKey');
    }

    /**
     * Tests that createUrl throws an exception when a required path parameter is missing.
     */
    public function testCreateUrlWithMissingPathParameter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing path parameter "model".');

        $uriFactory = new HttpFactory();
        OpenAIURLBuilder::createUrl($uriFactory, 'retrieveModel');
    }

    /**
     * Tests that createUrl throws an exception when a path parameter is not scalar.
     */
    public function testCreateUrlWithNonScalarPathParameter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "model" must be a scalar value, array given.');

        $uriFactory = new HttpFactory();
        OpenAIURLBuilder::createUrl($uriFactory, 'retrieveModel', ['model' => ['not', 'scalar']]);
    }

    public function testRegistryContainsCurrentSupportedSurfaceOnly(): void
    {
        $endpoints = OpenAIURLBuilder::getEndpoints();

        $this->assertArrayNotHasKey('createAssistant', $endpoints);
        $this->assertArrayNotHasKey('createImageVariation', $endpoints);

        $routes = [];

        foreach ($endpoints as $name => $endpoint) {
            $this->assertContains($endpoint['method'], ['GET', 'POST', 'DELETE'], $name);
            $this->assertContains($endpoint['body'], ['none', 'json', 'multipart'], $name);
            $this->assertNotSame('', $endpoint['category'], $name);

            if ($endpoint['body'] === 'multipart') {
                $this->assertNotEmpty($endpoint['fileFields'] ?? [], $name);
            }

            $route = $endpoint['method'] . ' ' . $endpoint['path'];
            $this->assertArrayNotHasKey($route, $routes, "Duplicate route registered by {$name}.");
            $routes[$route] = true;

            $this->assertFalse(\str_starts_with($endpoint['path'], '/assistants'));
            $this->assertFalse(\str_starts_with($endpoint['path'], '/threads'));
            $this->assertFalse(\str_starts_with($endpoint['path'], '/evals'));
            $this->assertFalse(\str_starts_with($endpoint['path'], '/videos'));
            $this->assertNotContains($endpoint['path'], [
                '/completions',
                '/images/variations',
                '/realtime/sessions',
                '/realtime/transcription_sessions',
            ]);
        }

        $this->assertSame('POST', $endpoints['modifyProjectRateLimit']['method']);
        $this->assertTrue($endpoints['createFineTuningJob']['deprecated']);
    }

    public function testCreateUrlEncodesPathSegments(): void
    {
        $uri = OpenAIURLBuilder::createUrl(
            new HttpFactory(),
            'retrieveModel',
            ['model' => 'custom/model name'],
        );

        $this->assertSame('/v1/models/custom%2Fmodel%20name', $uri->getPath());
    }

    public function testCreateUrlSupportsAbsoluteBaseUrlAndExplicitBasePath(): void
    {
        $uri = OpenAIURLBuilder::createUrl(
            new HttpFactory(),
            'listModels',
            [],
            'http://localhost:8080/openai/v1',
        );
        $overridden = OpenAIURLBuilder::createUrl(
            new HttpFactory(),
            'listModels',
            [],
            'http://localhost:8080/openai/v1',
            '/compatible/v1',
        );

        $this->assertSame('http://localhost:8080/openai/v1/models', (string) $uri);
        $this->assertSame('http://localhost:8080/compatible/v1/models', (string) $overridden);
    }
}
