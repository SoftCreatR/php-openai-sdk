<?php

namespace SoftCreatR\OpenAI\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SoftCreatR\OpenAI\OpenAIUrlFactory;

/**
 * @covers \SoftCreatR\OpenAI\OpenAIUrlFactory
 */
class OpenAIUrlFactoryTest extends TestCase
{
    /**
     * @covers \SoftCreatR\OpenAI\OpenAIUrlFactory::getEndpoint
     */
    public function testGetEndpoint(): void
    {
        $endpoint = OpenAIUrlFactory::getEndpoint('listModels');

        $this->assertArrayHasKey('method', $endpoint);
        $this->assertArrayHasKey('path', $endpoint);
    }

    /**
     * @covers \SoftCreatR\OpenAI\OpenAIUrlFactory::getEndpoint
     */
    public function testGetEndpointInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);

        OpenAIUrlFactory::getEndpoint('invalidKey');
    }

    /**
     * @covers \SoftCreatR\OpenAI\OpenAIUrlFactory::createUrl
     */
    public function testCreateUrl(): void
    {
        $url = OpenAIUrlFactory::createUrl('listModels');

        $this->assertEquals('https', $url->getScheme());
        $this->assertEquals(OpenAIUrlFactory::ORIGIN, $url->getHost());
        $this->assertEquals(OpenAIUrlFactory::API_VERSION . '/models', $url->getPath());
    }

    /**
     * @covers \SoftCreatR\OpenAI\OpenAIUrlFactory::createUrl
     */
    public function testCreateUrlWithPathParameter(): void
    {
        $url = OpenAIUrlFactory::createUrl('retrieveFile', 'fileId');

        $this->assertEquals('https', $url->getScheme());
        $this->assertEquals(OpenAIUrlFactory::ORIGIN, $url->getHost());
        $this->assertEquals(OpenAIUrlFactory::API_VERSION . '/files/fileId', $url->getPath());
    }

    /**
     * @covers \SoftCreatR\OpenAI\OpenAIUrlFactory::createUrl
     */
    public function testCreateUrlInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);

        OpenAIUrlFactory::createUrl('invalidKey');
    }
}
