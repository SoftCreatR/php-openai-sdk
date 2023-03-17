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

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use ReflectionException;
use SoftCreatR\OpenAI\Exception\OpenAIException;
use SoftCreatR\OpenAI\OpenAI;

/**
 * @covers \SoftCreatR\OpenAI\OpenAI
 */
class OpenAITest extends TestCase
{
    /**
     * The OpenAI instance used for testing.
     *
     * @var OpenAI
     */
    private OpenAI $openAI;

    /**
     * The mocked HTTP client used for simulating API responses.
     *
     * @var ClientInterface
     */
    private ClientInterface $mockedClient;

    /**
     * Sets up the test environment by creating an OpenAI instance and
     * a mocked HTTP client, then assigning the mocked client to the OpenAI instance.
     *
     * This method is called before each test method is executed.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->openAI = OpenAI::getInstance('your_api_key', 'your_organisation_id');
        $this->mockedClient = $this->createMock(Client::class);
        $this->openAI->setHttpClient($this->mockedClient);
    }

    /**
     * Test that OpenAI::__call method can handle API calls correctly.
     *
     * @covers \SoftCreatR\OpenAI\OpenAI::__call
     * @covers \SoftCreatR\OpenAI\OpenAIUrlFactory::createUrl
     * @covers \SoftCreatR\OpenAI\OpenAIUrlFactory::getEndpoint
     * @covers \SoftCreatR\OpenAI\OpenAIUrlFactory::replacePathParameters
     */
    public function testCall(): void
    {
        $this->testApiCall(
            fn () => $this->openAI->__call('retrieveModel', ['text-davinci-003']),
            'listModels.json'
        );
    }

    /**
     * Test that OpenAI::completion method can handle API calls correctly.
     *
     * @covers \SoftCreatR\OpenAI\OpenAI::createCompletion
     * @covers \SoftCreatR\OpenAI\OpenAIUrlFactory::createUrl
     * @covers \SoftCreatR\OpenAI\OpenAIUrlFactory::getEndpoint
     * @covers \SoftCreatR\OpenAI\OpenAI::createCompletion
     * @covers \SoftCreatR\OpenAI\OpenAIUrlFactory::replacePathParameters
     */
    public function testCreateCompletion(): void
    {
        $this->testApiCall(
            fn () => $this->openAI->createCompletion([
                'model' => 'text-davinci-002',
                'prompt' => 'Say this is a test',
                'max_tokens' => 7,
                'temperature' => 0,
                'top_p' => 1,
                'n' => 1,
                'stream' => false,
                'logprobs' => null,
                'stop' => "\n",
            ]),
            'completion.json'
        );
    }

    /**
     * Test that OpenAI::chat method can handle API calls correctly.
     *
     * @covers \SoftCreatR\OpenAI\OpenAI::createChatCompletion
     * @covers \SoftCreatR\OpenAI\OpenAIUrlFactory::createUrl
     * @covers \SoftCreatR\OpenAI\OpenAIUrlFactory::getEndpoint
     * @covers \SoftCreatR\OpenAI\OpenAIUrlFactory::replacePathParameters
     */
    public function testCreateChatCompletion(): void
    {
        $this->testApiCall(
            fn () => $this->openAI->createChatCompletion([
                'model' => 'gpt-3.5-turbo',
                'messages' => [['role' => 'user', 'content' => 'Hello!']],
            ]),
            'chatCompletion.json'
        );
    }

    /**
     * Test the uploadFile method.
     *
     * This test ensures that the 'Content-Type' header is set to 'multipart/form-data' and
     * that the given options (file and purpose) are included in the request body.
     *
     * @covers \SoftCreatR\OpenAI\OpenAI::__call
     * @covers \SoftCreatR\OpenAI\OpenAIUrlFactory::createUrl
     * @covers \SoftCreatR\OpenAI\OpenAIUrlFactory::getEndpoint
     * @covers \SoftCreatR\OpenAI\OpenAIUrlFactory::replacePathParameters
     */
    public function testUploadFile(): void
    {
        $response = null;
        $fixture = __DIR__ . '/fixtures/mydata.jsonl';
        $fakeResponseBody = TestHelper::loadResponseFromFile('createFile.json');

        $this->mockedClient
            ->expects(self::once())
            ->method('send')
            ->willReturnCallback(
                static function (RequestInterface $request) use ($fixture, $fakeResponseBody) {
                    $fakeResponse = new Response(200, [], $fakeResponseBody);

                    // Check if the given opts are present in the request body
                    $body = (string)$request->getBody();

                    // Assert that the file's content can be found in the request body
                    $fileContent = \file_get_contents($fixture);
                    $pattern = '/' . \preg_quote($fileContent, '/') . '/';
                    self::assertMatchesRegularExpression($pattern, $body);

                    return $fakeResponse;
                }
            );

        try {
            $response = $this->openAI->createFile([
                'file' => $fixture,
                'purpose' => 'fine-tune',
            ]);
        } catch (Exception $e) {
            // ignore
        }

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($fakeResponseBody, (string)$response->getBody());
    }

    /**
     * Test that getHttpClient returns a GuzzleHttp\ClientInterface object
     * and that the timeout and proxy settings are updated correctly.
     *
     * @throws ReflectionException
     */
    public function testGetHttpClient(): void
    {
        $httpClient = $this->openAI->getHttpClient();

        $proxy = 'https://proxy.example.com:8080';
        $newTimeout = 10;

        $this->openAI->setTimeout($newTimeout)->setProxy($proxy);

        // Check if the timeout has been updated correctly.
        $timeout = TestHelper::getPrivateProperty($this->openAI, 'timeout');
        self::assertEquals($newTimeout, $timeout);

        // Invalidate the existing httpClient to force a new one with the updated settings.
        TestHelper::setPrivateProperty($this->openAI, 'httpClient', null);
        $httpClient2 = $this->openAI->getHttpClient();

        // Assert that a new httpClient has been created with the updated settings.
        self::assertNotSame($httpClient, $httpClient2);

        /** @noinspection PhpDeprecationInspection */
        $config = $httpClient2->getConfig();

        self::assertEquals($newTimeout, $config[RequestOptions::TIMEOUT]);
        self::assertEquals($proxy, $config[RequestOptions::PROXY]);
    }

    /**
     * Test that ClientExceptionInterface gets caught and an OpenAIException is thrown in sendRequest.
     *
     * @covers \SoftCreatR\OpenAI\Exception\OpenAIException::__construct
     *
     * @throws ReflectionException
     */
    public function testClientExceptionHandlingWithStringResponse(): void
    {
        $this->prepareClientWithMockHandler([
            new Exception('Test Client Exception'),
        ]);

        // Use Reflection to access the private sendRequest method
        $sendRequest = TestHelper::getPrivateMethod($this->openAI, 'sendRequest');

        // Test that an OpenAIException is thrown when calling sendRequest
        $this->expectException(OpenAIException::class);
        $this->expectExceptionMessage('Test Client Exception');
        $this->expectExceptionCode(0);
        $sendRequest->invokeArgs($this->openAI, ['GET', '/test-path']);
    }

    /**
     * Test that ClientExceptionInterface gets caught and an OpenAIException is thrown in sendRequest.
     *
     * @covers \SoftCreatR\OpenAI\Exception\OpenAIException::__construct
     *
     * @throws ReflectionException
     */
    public function testClientExceptionHandlingWithJsonResponse(): void
    {
        $this->prepareClientWithMockHandler([
            new RequestException(
                '',
                new Request('GET', '/'),
                new Response(400, [], '{"error": {"message": "Test Client Exception"}}')
            ),
        ]);

        // Use Reflection to access the private sendRequest method
        $sendRequest = TestHelper::getPrivateMethod($this->openAI, 'sendRequest');

        // Test that an OpenAIException is thrown when calling sendRequest
        $this->expectException(OpenAIException::class);
        $this->expectExceptionMessage('Test Client Exception');
        $this->expectExceptionCode(400);
        $sendRequest->invokeArgs($this->openAI, ['GET', '/test-path']);
    }

    /**
     * Test the 'extractCallArguments' method with various input scenarios.
     *
     * This test ensures that the 'extractCallArguments' method correctly extracts
     * the parameter and options from the provided arguments array for different cases:
     * - String parameter and options array
     * - Only string parameter
     * - Only options array
     * - Empty array
     *
     * @throws ReflectionException
     */
    public function testExtractCallArguments(): void
    {
        // Invoke the protected method 'extractCallArguments' via reflection
        $reflectionMethod = TestHelper::getPrivateMethod($this->openAI, 'extractCallArguments');

        $testCases = [
            [['stringParam', ['key' => 'value']], ['stringParam', ['key' => 'value']]],
            [['stringParam'], ['stringParam', []]],
            [[['key' => 'value']], [null, ['key' => 'value']]],
            [[], [null, []]],
        ];

        foreach ($testCases as $testCase) {
            [$args, $expected] = $testCase;
            $result = $reflectionMethod->invoke($this->openAI, $args);
            $this->assertEquals($expected, $result);
        }
    }

    /**
     * Prepare the OpenAI instance with a new HTTP client that uses a MockHandler with the specified responses.
     *
     * @param array $responses An array of responses to be used by the MockHandler.
     */
    private function prepareClientWithMockHandler(array $responses): void
    {
        // Create the HandlerStack with the MockHandler
        $handlerStack = HandlerStack::create(new MockHandler($responses));

        // Replace the httpClient with a new one using the HandlerStack
        $this->openAI->setHttpClient(new Client(['handler' => $handlerStack]));
    }

    /**
     * Test an API call using a callable and a response file.
     * This method mocks the HTTP client to return a predefined response loaded from a file,
     * and checks if the status code and the response body match the expected values.
     *
     * @param callable $apiCall The API call to test, wrapped in a callable function.
     * @param string $responseFile The path to the file containing the expected response.
     */
    private function testApiCall(callable $apiCall, string $responseFile): void
    {
        $response = null;
        $fakeResponseBody = TestHelper::loadResponseFromFile($responseFile);
        $fakeResponse = new Response(200, [], $fakeResponseBody);
        $this->mockedClient
            ->expects(self::once())
            ->method('send')
            ->willReturn($fakeResponse);

        try {
            $response = $apiCall();
        } catch (Exception $e) {
            // ignore
        }

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($fakeResponseBody, (string)$response->getBody());
    }
}
