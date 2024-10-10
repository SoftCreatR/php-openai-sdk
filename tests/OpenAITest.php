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

use Exception;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use ReflectionException;
use SoftCreatR\OpenAI\Exception\OpenAIException;
use SoftCreatR\OpenAI\OpenAI;

/**
 * @covers \SoftCreatR\OpenAI\Exception\OpenAIException
 * @covers \SoftCreatR\OpenAI\OpenAI
 * @covers \SoftCreatR\OpenAI\OpenAIURLBuilder
 */
final class OpenAITest extends TestCase
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
     * Sets up the test environment by creating an OpenAI instance and
     * a mocked HTTP client, then assigns the mocked client to the OpenAI instance.
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
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
            $this->origin
        );
    }

    /**
     * Tests that an InvalidArgumentException is thrown when the first argument is not an array.
     */
    public function testInvalidFirstArgumentInCall(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('First argument must be an array of parameters.');

        /** @noinspection PhpParamsInspection */
        $this->openAI->createChatCompletion('invalid_argument');
    }

    /**
     * Tests that the createMultipartStream method is called and the boundary is generated.
     *
     * @throws Exception
     */
    public function testUploadFileCreatesMultipartStream(): void
    {
        $filePath = __DIR__ . '/fixtures/dummyFile.jsonl';
        \file_put_contents($filePath, 'Dummy content');

        $this->sendRequestMock(function (RequestInterface $request) {
            $body = (string)$request->getBody();
            $this->assertStringContainsString('multipart/form-data', $request->getHeaderLine('Content-Type'));
            $this->assertStringContainsString('Dummy content', $body);

            return new Response(200, [], '{"success": true}');
        });

        // Pass parameters as $opts, not $parameters
        $response = $this->openAI->uploadFile([], [
            'file' => $filePath,
            'purpose' => 'fine-tune',
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        \unlink($filePath);
    }

    /**
     * Tests that an OpenAIException is thrown when the API returns an error response.
     */
    public function testCallAPIHandlesErrorResponse(): void
    {
        $this->sendRequestMock(static function () {
            return new Response(400, [], 'Bad Request');
        });

        $this->expectException(OpenAIException::class);
        $this->expectExceptionMessage('Bad Request');

        // Pass options as the second argument
        $this->openAI->createChatCompletion([], [
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Test message',
                ],
            ],
        ]);
    }

    /**
     * Tests that an OpenAIException is thrown when the HTTP client throws a ClientExceptionInterface.
     */
    public function testCallAPICatchesClientException(): void
    {
        $this->sendRequestMock(
            static fn() => throw new class ('Client error', 0) extends Exception implements ClientExceptionInterface {}
        );

        $this->expectException(OpenAIException::class);
        $this->expectExceptionMessage('Client error');

        // Pass options as the second argument
        $this->openAI->createChatCompletion([], [
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Test message',
                ],
            ],
        ]);
    }

    /**
     * Tests that handleStreamingResponse throws an OpenAIException when the response status code is >= 400.
     */
    public function testHandleStreamingResponseHandlesErrorResponse(): void
    {
        $this->sendRequestMock(static function () {
            return new Response(400, [], 'Bad Request');
        });

        $this->expectException(OpenAIException::class);
        $this->expectExceptionMessage('Bad Request');

        $this->openAI->createChatCompletion(
            [],
            [
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Test message',
                    ],
                ],
                'stream' => true,
            ],
            static function () {
                // Streaming callback
            }
        );
    }

    /**
     * Tests that handleStreamingResponse continues when data is an empty string.
     */
    public function testHandleStreamingResponseContinuesOnEmptyData(): void
    {
        $fakeResponseContent = "\n"; // Empty data
        $stream = \fopen('php://temp', 'rb+');
        \fwrite($stream, $fakeResponseContent);
        \rewind($stream);

        $fakeResponse = new Response(200, [], $stream);

        $this->sendRequestMock(static function () use ($fakeResponse) {
            return $fakeResponse;
        });

        $this->openAI->createChatCompletion(
            [],
            [
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Test message',
                    ],
                ],
                'stream' => true,
            ],
            fn() => $this->fail('Streaming callback should not be called on empty data.')
        );

        $this->assertTrue(true); // If no exception is thrown, test passes
    }

    /**
     * Tests that handleStreamingResponse throws an OpenAIException when JSON decoding fails.
     */
    public function testHandleStreamingResponseJsonException(): void
    {
        $fakeResponseContent = "data: invalid_json\n";
        $stream = \fopen('php://temp', 'rb+');
        \fwrite($stream, $fakeResponseContent);
        \rewind($stream);

        $fakeResponse = new Response(200, [], $stream);

        $this->sendRequestMock(static function () use ($fakeResponse) {
            return $fakeResponse;
        });

        $this->expectException(OpenAIException::class);
        $this->expectExceptionMessageMatches('/JSON decode error:/');

        $this->openAI->createChatCompletion(
            [],
            [
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Test message',
                    ],
                ],
                'stream' => true,
            ],
            static function ($data) {
                // Streaming callback
            }
        );
    }

    /**
     * Tests that handleStreamingResponse catches ClientExceptionInterface exceptions.
     */
    public function testHandleStreamingResponseCatchesClientException(): void
    {
        $this->sendRequestMock(
            static fn() => throw new class ('Client error in streaming', 0) extends Exception implements ClientExceptionInterface {}
        );

        $this->expectException(OpenAIException::class);
        $this->expectExceptionMessage('Client error in streaming');

        $this->openAI->createChatCompletion(
            [],
            [
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Test message',
                    ],
                ],
                'stream' => true,
            ],
            static function () {
                // Streaming callback
            }
        );
    }

    /**
     * Tests that generateMultipartBoundary generates a boundary string.
     *
     * @throws ReflectionException
     */
    public function testGenerateMultipartBoundary(): void
    {
        $reflectionMethod = TestHelper::getPrivateMethod($this->openAI, 'generateMultipartBoundary');
        $boundary = $reflectionMethod->invoke($this->openAI);

        $this->assertMatchesRegularExpression('/^----OpenAI[0-9a-f]{32}$/', $boundary);
    }

    /**
     * Tests that createHeaders sets the correct Content-Type for multipart requests.
     *
     * @throws ReflectionException
     */
    public function testCreateHeadersForMultipartRequest(): void
    {
        $reflectionMethod = TestHelper::getPrivateMethod($this->openAI, 'createHeaders');
        $boundary = 'testBoundary';

        $headers = $reflectionMethod->invoke($this->openAI, true, $boundary);

        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals("multipart/form-data; boundary={$boundary}", $headers['Content-Type']);
    }

    /**
     * Tests that createHeaders removes the 'OpenAI-Organization' header when organization is empty.
     *
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testCreateHeadersWithoutOrganization(): void
    {
        $psr17Factory = new HttpFactory();
        $mockedClient = $this->createMock(ClientInterface::class);

        $openAIWithoutOrg = new OpenAI(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $mockedClient,
            $this->apiKey,
            ''  // Empty organization
        );

        $mockedClient
            ->expects(self::once())
            ->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) {
                $this->assertFalse(
                    $request->hasHeader('OpenAI-Organization'),
                    'OpenAI-Organization header should not be set when organization is empty.'
                );

                return new Response(200, [], '{"success": true}');
            });

        $openAIWithoutOrg->createChatCompletion([], [
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Test message',
                ],
            ],
        ]);
    }

    /**
     * Tests that createJsonBody throws an OpenAIException when JSON encoding fails.
     *
     * @throws ReflectionException
     */
    public function testCreateJsonBodyJsonException(): void
    {
        $reflectionMethod = TestHelper::getPrivateMethod($this->openAI, 'createJsonBody');

        $this->expectException(OpenAIException::class);
        $this->expectExceptionMessageMatches('/^JSON encode error:/');

        $invalidValue = \tmpfile(); // Cannot be JSON encoded
        $params = ['invalid' => $invalidValue];

        $reflectionMethod->invoke($this->openAI, $params);
    }

    /**
     * Tests that createMultipartStream creates a valid multipart stream.
     *
     * @throws ReflectionException
     */
    public function testCreateMultipartStream(): void
    {
        $reflectionMethod = TestHelper::getPrivateMethod($this->openAI, 'createMultipartStream');
        $boundary = 'testBoundary';
        $filePath = __DIR__ . '/fixtures/dummyFile.jsonl';
        \file_put_contents($filePath, 'Dummy content');

        $params = [
            'file' => $filePath,
            'purpose' => 'fine-tune',
        ];

        $multipartStream = $reflectionMethod->invoke($this->openAI, $params, $boundary);

        $this->assertStringContainsString("--{$boundary}\r\n", $multipartStream);
        $this->assertStringContainsString('Content-Disposition: form-data; name="file"; filename', $multipartStream);
        $this->assertStringContainsString('Dummy content', $multipartStream);

        \unlink($filePath);
    }

    /**
     * Tests that createMultipartStream correctly base64 encodes 'data' parameter.
     *
     * @throws ReflectionException
     */
    public function testCreateMultipartStreamWithData(): void
    {
        $reflectionMethod = TestHelper::getPrivateMethod($this->openAI, 'createMultipartStream');
        $boundary = 'testBoundary';
        $filePath = __DIR__ . '/fixtures/dummyFile.bin';
        \file_put_contents($filePath, 'Binary content');

        $params = [
            'data' => $filePath,
            'purpose' => 'fine-tune',
        ];

        $multipartStream = $reflectionMethod->invoke($this->openAI, $params, $boundary);

        $this->assertStringContainsString("--{$boundary}\r\n", $multipartStream);
        $this->assertStringContainsString('Content-Disposition: form-data; name="data"; filename', $multipartStream);
        $this->assertStringContainsString(\base64_encode('Binary content'), $multipartStream);

        \unlink($filePath);
    }

    /**
     * Tests that the createChatCompletion method handles API calls correctly.
     *
     * @throws Exception
     */
    public function testCreateChatCompletion(): void
    {
        $this->testApiCall(
            fn() => $this->openAI->createChatCompletion([], [
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant.',
                    ],
                    [
                        'role' => 'user',
                        'content' => 'Hello!',
                    ],
                ],
            ]),
            'chatCompletion.json'
        );
    }

    /**
     * Tests that the createChatCompletion method handles streaming API calls correctly.
     *
     * @throws Exception
     */
    public function testCreateChatCompletionWithStreaming(): void
    {
        $output = '';

        $streamCallback = static function ($data) use (&$output) {
            if (isset($data['choices'][0]['delta']['content'])) {
                $output .= $data['choices'][0]['delta']['content'];
            }
        };

        $this->testApiCallWithStreaming(
            fn($streamCallback) => $this->openAI->createChatCompletion(
                [],
                [
                    'model' => 'gpt-4',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => 'Tell me a story about a brave knight.',
                        ],
                    ],
                    'stream' => true,
                ],
                $streamCallback
            ),
            'chatCompletionStreaming.txt',
            $streamCallback
        );

        $expectedOutput = 'Hello';
        $this->assertEquals($expectedOutput, $output);
    }

    /**
     * Tests that the listModels method handles API calls correctly.
     *
     * @throws Exception
     */
    public function testListModels(): void
    {
        $this->testApiCall(
            fn() => $this->openAI->listModels(),
            'listModels.json'
        );
    }

    /**
     * Tests that the retrieveModel method handles API calls correctly.
     *
     * @throws Exception
     */
    public function testRetrieveModel(): void
    {
        $this->testApiCall(
            fn() => $this->openAI->retrieveModel(['model' => 'gpt-3.5-turbo-instruct']),
            'retrieveModel.json'
        );
    }

    /**
     * Tests that the uploadFile method handles API calls correctly.
     *
     * @throws Exception
     */
    public function testUploadFile(): void
    {
        $filePath = __DIR__ . '/fixtures/dummyFile.jsonl';
        \file_put_contents($filePath, '{"prompt": "Hello", "completion": "World"}');

        $this->testApiCall(
            fn() => $this->openAI->uploadFile([], [
                'file' => $filePath,
                'purpose' => 'fine-tune',
            ]),
            'uploadFile.json'
        );

        \unlink($filePath);
    }

    /**
     * Mocks an API call using a callable and a response file.
     *
     * Mocks the HTTP client to return a predefined response loaded from a file,
     * and checks if the status code and response body match the expected values.
     *
     * @param callable $apiCall      The API call to test.
     * @param string   $responseFile The path to the file containing the expected response.
     *
     * @throws Exception
     */
    private function testApiCall(callable $apiCall, string $responseFile): void
    {
        $fakeResponseBody = TestHelper::loadResponseFromFile($responseFile);
        $fakeResponse = new Response(200, [], $fakeResponseBody);

        $this->sendRequestMock(static function () use ($fakeResponse) {
            return $fakeResponse;
        });

        try {
            $response = $apiCall();
        } catch (Exception $e) {
            $this->fail('Exception occurred during API call: ' . $e->getMessage());
        }

        self::assertNotNull($response, 'Response should not be null.');
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($fakeResponseBody, (string)$response->getBody());
    }

    /**
     * Mocks an API call with streaming support using a callable and a response file.
     *
     * Mocks the HTTP client to return a predefined streaming response loaded from a file,
     * and utilizes the provided stream callback to process the response.
     *
     * @param callable $apiCall       The API call to test.
     * @param string   $responseFile  The path to the file containing the expected streaming response.
     * @param callable $streamCallback The callback function to handle streaming data.
     *
     * @throws Exception
     */
    private function testApiCallWithStreaming(callable $apiCall, string $responseFile, callable $streamCallback): void
    {
        $fakeResponseContent = TestHelper::loadResponseFromFile($responseFile);
        $fakeChunks = \explode("\n", \trim($fakeResponseContent));
        $stream = \fopen('php://temp', 'rb+');

        foreach ($fakeChunks as $chunk) {
            \fwrite($stream, $chunk . "\n");
        }
        \rewind($stream);

        $fakeResponse = new Response(200, [], $stream);

        $this->sendRequestMock(static function () use ($fakeResponse) {
            return $fakeResponse;
        });

        try {
            $apiCall($streamCallback);
        } catch (Exception $e) {
            $this->fail('Exception occurred during streaming: ' . $e->getMessage());
        }
    }

    /**
     * Sets up a mock for the sendRequest method of the mocked client.
     *
     * @param callable $responseCallback A callable that returns a response or throws an exception.
     */
    private function sendRequestMock(callable $responseCallback): void
    {
        $this->mockedClient
            ->expects(self::once())
            ->method('sendRequest')
            ->willReturnCallback($responseCallback);
    }
}
