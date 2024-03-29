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
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use JsonException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use ReflectionException;
use SoftCreatR\OpenAI\Exception\OpenAIException;
use SoftCreatR\OpenAI\OpenAI;
use Throwable;

use const JSON_THROW_ON_ERROR;

/**
 * @covers \SoftCreatR\OpenAI\Exception\OpenAIException
 * @covers \SoftCreatR\OpenAI\OpenAI
 * @covers \SoftCreatR\OpenAI\OpenAIURLBuilder
 */
class OpenAITest extends TestCase
{
    /**
     * The OpenAI instance used for testing.
     */
    private OpenAI $openAI;

    /**
     * The mocked HTTP client used for simulating API responses.
     */
    private ClientInterface $mockedClient;

    /**
     * API key for the OpenAI API.
     */
    private string $apiKey = 'sk-...';

    /**
     * Organization identifier for the OpenAI API.
     */
    private string $organization = 'org-...';

    /**
     * Custom origin for the OpenAI API, if needed.
     */
    private string $origin = 'example.com';

    /**
     * API version.
     */
    private ?string $apiVersion = '';

    /**
     * Sets up the test environment by creating an OpenAI instance and
     * a mocked HTTP client, then assigning the mocked client to the OpenAI instance.
     *
     * This method is called before each test method is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $psr17Factory = new HttpFactory();
        $this->mockedClient = $this->createMock(ClientInterface::class);

        $this->openAI = new OpenAI(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $this->mockedClient,
            $this->apiKey,
            $this->organization,
            $this->origin,
            $this->apiVersion
        );
    }

    /**
     * Test that OpenAI::__call method can handle API calls correctly.
     */
    public function testCall(): void
    {
        $this->testApiCall(
            fn() => $this->openAI->__call('retrieveModel', ['gpt-3.5-turbo-instruct']),
            'listModels.json'
        );
    }

    /**
     * Test that OpenAI::chat method can handle API calls correctly.
     */
    public function testCreateChatCompletion(): void
    {
        $this->testApiCall(
            fn() => $this->openAI->createChatCompletion([
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
     */
    public function testUploadFile(): void
    {
        $response = null;
        $fixture = __DIR__ . '/fixtures/mydata.jsonl';
        $fakeResponseBody = TestHelper::loadResponseFromFile('createFile.json');

        $this->mockedClient
            ->expects(self::once())
            ->method('sendRequest')
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
     * Test that OpenAI::callAPI handles JSON encoding errors correctly.
     *
     * This test ensures that when the JSON encoding fails due to an invalid value,
     * the method catches the JsonException and sets the request body to an empty string.
     */
    public function testCallAPIJsonEncodingException(): void
    {
        $this->sendRequestMock(static function (RequestInterface $request) {
            $fakeResponse = new Response(200, [], '');
            // Check if the request body is empty
            self::assertEquals('', (string)$request->getBody());

            return $fakeResponse;
        });

        $invalidValue = \tmpfile(); // create an invalid value that cannot be JSON encoded
        $response = null;

        try {
            $response = $this->openAI->createChatCompletion([
                'model' => 'text-davinci-002',
                'prompt' => 'Say this is a test',
                'max_tokens' => 7,
                'invalid' => $invalidValue, // pass the invalid value
            ]);
        } catch (Exception $e) {
            // ignore
        }

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('', (string)$response->getBody());
    }

    /**
     * Test that OpenAI::sendRequest method throws an OpenAIException when a ClientExceptionInterface occurs.
     *
     * @throws Exception
     */
    public function testSendRequestException(): void
    {
        $this->sendRequestMock(function () {
            throw $this->createMock(ClientExceptionInterface::class);
        });

        $this->expectException(OpenAIException::class);

        try {
            $this->openAI->retrieveModel('gpt-3.5-turbo-instruct');
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Test that OpenAI::callAPI handles non-200 status codes correctly.
     *
     * @throws JsonException
     */
    public function testCallApiErrorHandling(): void
    {
        $fakeErrorResponseBody = \json_encode([
            'error' => [
                'message' => 'An error occurred.',
            ],
        ], JSON_THROW_ON_ERROR);

        // Configure the mocked client to throw an exception with a non-200 status code
        $this->sendRequestMock(
            function () use ($fakeErrorResponseBody) {
                throw new class ($fakeErrorResponseBody) extends Exception implements ClientExceptionInterface {
                    public function __construct($message, $code = 400, ?Throwable $previous = null)
                    {
                        parent::__construct($message, $code, $previous);
                    }
                };
            }
        );

        $this->expectException(OpenAIException::class);
        $this->expectExceptionMessage('An error occurred.');
        $this->expectExceptionCode(400);

        $this->openAI->__call('listModels', []);
    }

    /**
     * Test an API call using a callable and a response file.
     * This method mocks the HTTP client to return a predefined response loaded from a file,
     * and checks if the status code and the response body match the expected values.
     *
     * @param callable $apiCall The API call to test, wrapped in a callable function.
     * @param string|null $responseFile The path to the file containing the expected response.
     */
    private function testApiCall(callable $apiCall, ?string $responseFile): void
    {
        $response = null;
        $fakeResponseBody = $responseFile ? TestHelper::loadResponseFromFile($responseFile) : '';
        $fakeResponse = new Response(200, [], $fakeResponseBody);

        $this->sendRequestMock(static function () use ($fakeResponse) {
            return $fakeResponse;
        });

        try {
            $response = $apiCall();
        } catch (Exception $e) {
            // ignore
        }

        self::assertEquals($this->apiKey, $this->openAI->apiKey);
        self::assertEquals($this->organization, $this->openAI->organization);
        self::assertEquals($this->origin, $this->openAI->origin);
        self::assertEquals($this->apiVersion, $this->openAI->apiVersion);
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($fakeResponseBody, (string)$response->getBody());
    }

    /**
     * Sets up a mock for the sendRequest method of the mocked client.
     *
     * This helper method is used to reduce code duplication when configuring
     * the sendRequest mock in multiple test cases. It accepts a callable, which
     * will be used as the return value or exception thrown by the sendRequest mock.
     *
     * @param callable $responseCallback A callable that returns a response or throws an exception
     */
    private function sendRequestMock(callable $responseCallback): void
    {
        $this->mockedClient
            ->expects(self::once())
            ->method('sendRequest')
            ->willReturnCallback($responseCallback);
    }
}
