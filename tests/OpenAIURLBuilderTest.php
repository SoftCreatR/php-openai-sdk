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

namespace SoftCreatR\OpenAI\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SoftCreatR\OpenAI\OpenAIURLBuilder;

/**
 * @covers \SoftCreatR\OpenAI\OpenAIURLBuilder
 */
class OpenAIURLBuilderTest extends TestCase
{
    public function testGetEndpoint(): void
    {
        $endpoint = OpenAIURLBuilder::getEndpoint('listModels');

        $this->assertArrayHasKey('method', $endpoint);
        $this->assertArrayHasKey('path', $endpoint);
    }

    public function testGetEndpointInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);

        OpenAIURLBuilder::getEndpoint('invalidKey');
    }

    public function testCreateUrl(): void
    {
        $url = OpenAIURLBuilder::createUrl(new HttpFactory(), 'listModels');

        $this->assertEquals('https', $url->getScheme());
        $this->assertEquals(OpenAIURLBuilder::ORIGIN, $url->getHost());
        $this->assertEquals(OpenAIURLBuilder::API_VERSION . '/models', $url->getPath());
    }

    public function testCreateUrlWithPathParameter(): void
    {
        $url = OpenAIURLBuilder::createUrl(new HttpFactory(), 'retrieveFile', 'fileId');

        $this->assertEquals('https', $url->getScheme());
        $this->assertEquals(OpenAIURLBuilder::ORIGIN, $url->getHost());
        $this->assertEquals(OpenAIURLBuilder::API_VERSION . '/files/fileId', $url->getPath());
    }

    public function testCreateUrlInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);

        OpenAIURLBuilder::createUrl(new HttpFactory(), 'invalidKey');
    }
}
