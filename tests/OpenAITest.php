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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use ReflectionException;
use SoftCreatR\OpenAI\Exception\OpenAIException;
use SoftCreatR\OpenAI\Http\MultipartBodyBuilder;
use SoftCreatR\OpenAI\Http\ServerSentEventDecoder;
use SoftCreatR\OpenAI\Http\StreamingClientInterface;
use SoftCreatR\OpenAI\OpenAI;
use SoftCreatR\OpenAI\OpenAIURLBuilder;
use Throwable;

#[CoversClass(OpenAIException::class)]
#[CoversClass(MultipartBodyBuilder::class)]
#[CoversClass(ServerSentEventDecoder::class)]
#[CoversClass(OpenAI::class)]
#[CoversClass(OpenAIURLBuilder::class)]
final class OpenAITest extends TestCase
{
    /**
     * The OpenAI instance used for testing.
     */
    private OpenAI $openAI;

    /**
     * The mocked HTTP client used for simulating API responses.
     */
    private ClientInterface&Stub $mockedClient;

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
        $this->mockedClient = $this->createStub(ClientInterface::class);

        $this->openAI = new OpenAI(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $this->mockedClient,
            $this->apiKey,
            $this->organization,
            $this->origin,
        );
    }


    /**
     * Tests that an InvalidArgumentException is thrown when the first argument is not an array.
     *
     * @throws OpenAIException
     * @throws Throwable
     */
    public function testInvalidFirstArgumentInCall(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('First argument must be an array of parameters.');

        $this->openAI->__call('createChatCompletion', ['invalid_argument']);
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
            $body = (string) $request->getBody();
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
            static fn() => throw new class ('Client error', 0) extends Exception implements ClientExceptionInterface {},
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
            },
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
            fn() => $this->fail('Streaming callback should not be called on empty data.'),
        );

        $this->addToAssertionCount(1);
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
            },
        );
    }

    /**
     * Tests that handleStreamingResponse catches ClientExceptionInterface exceptions.
     */
    public function testHandleStreamingResponseCatchesClientException(): void
    {
        $this->sendRequestMock(
            static fn() => throw new class ('Client error in streaming', 0) extends Exception implements ClientExceptionInterface {},
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
            },
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
            '',  // Empty organization
        );

        $mockedClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) {
                $this->assertFalse(
                    $request->hasHeader('OpenAI-Organization'),
                    'OpenAI-Organization header should not be set when organization is empty.',
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
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testCreateHeadersWithProjectScope(): void
    {
        $psr17Factory = new HttpFactory();
        $mockedClient = $this->createMock(ClientInterface::class);
        $openAIWithProject = new OpenAI(
            requestFactory: $psr17Factory,
            streamFactory: $psr17Factory,
            uriFactory: $psr17Factory,
            httpClient: $mockedClient,
            apiKey: $this->apiKey,
            organization: $this->organization,
            project: 'proj_abc123',
        );

        $mockedClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) {
                $this->assertSame($this->organization, $request->getHeaderLine('OpenAI-Organization'));
                $this->assertSame('proj_abc123', $request->getHeaderLine('OpenAI-Project'));

                return new Response(200, [], '{}');
            });

        $openAIWithProject->listModels();
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

        $multipartStream = (string) $reflectionMethod->invoke($this->openAI, $params, $boundary);

        $this->assertStringContainsString("--{$boundary}\r\n", $multipartStream);
        $this->assertStringContainsString('Content-Disposition: form-data; name="file"; filename', $multipartStream);
        $this->assertStringContainsString('Dummy content', $multipartStream);

        \unlink($filePath);
    }

    /**
     * Tests that createMultipartStream writes upload-part data as raw bytes.
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

        $multipartStream = (string) $reflectionMethod->invoke($this->openAI, $params, $boundary);

        $this->assertStringContainsString("--{$boundary}\r\n", $multipartStream);
        $this->assertStringContainsString('Content-Disposition: form-data; name="data"; filename', $multipartStream);
        $this->assertStringContainsString('Binary content', $multipartStream);
        $this->assertStringNotContainsString(\base64_encode('Binary content'), $multipartStream);

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
            'chatCompletion.json',
        );
    }

    /**
     * The README has always documented body-first calls, so 4.0 must send this body.
     */
    public function testCreateChatCompletionSupportsBodyFirstCall(): void
    {
        $this->sendRequestMock(function (RequestInterface $request) {
            $this->assertSame('POST', $request->getMethod());
            $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
            $this->assertSame(
                ['model' => 'gpt-5.4-mini', 'messages' => [['role' => 'user', 'content' => 'Hello']]],
                \json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR),
            );

            return new Response(200, ['Content-Type' => 'application/json'], '{}');
        });

        $this->openAI->createChatCompletion([
            'model' => 'gpt-5.4-mini',
            'messages' => [['role' => 'user', 'content' => 'Hello']],
        ]);
    }

    /**
     * @throws Throwable
     */
    public function testUnauthenticatedEndpointOmitsOpenAICredentials(): void
    {
        $this->sendRequestMock(function (RequestInterface $request) {
            $this->assertFalse($request->hasHeader('Authorization'));
            $this->assertFalse($request->hasHeader('OpenAI-Organization'));
            $this->assertFalse($request->hasHeader('OpenAI-Project'));
            $this->assertSame('/oauth/token', $request->getUri()->getPath());

            return new Response(200, ['Content-Type' => 'application/json'], '{}');
        });

        $this->openAI->exchangeWorkloadIdentityToken([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:token-exchange',
            'subject_token_type' => 'urn:ietf:params:oauth:token-type:jwt',
            'subject_token' => 'external-token',
            'identity_provider_id' => 'idp_abc123',
            'service_account_id' => 'svcacct_abc123',
        ]);
    }

    public function testCallbackDoesNotDiscardANonStreamingResponse(): void
    {
        $response = new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}');
        $this->sendRequestMock(static fn() => $response);

        $actual = $this->openAI->createChatCompletion(
            ['model' => 'gpt-5.4-mini', 'messages' => []],
            fn() => $this->fail('A callback must not run for a non-streaming response.'),
        );

        $this->assertSame($response, $actual);
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
                $streamCallback,
            ),
            $streamCallback,
        );

        $expectedOutput = 'Hello';
        $this->assertEquals($expectedOutput, $output);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testStreamingRequestUsesStreamingTransportWhenAvailable(): void
    {
        $psr17Factory = new HttpFactory();
        $client = $this->createMock(StreamingClientInterface::class);
        $response = new Response(
            200,
            ['Content-Type' => 'text/event-stream'],
            "data: {\"value\":\"streamed\"}\n\n",
        );
        $client->expects($this->once())
            ->method('sendStreamingRequest')
            ->willReturn($response);
        $client->expects($this->never())
            ->method('sendRequest');

        $openAI = new OpenAI(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $client,
            $this->apiKey,
            $this->organization,
            $this->origin,
        );
        $events = [];

        $actual = $openAI->createChatCompletion(
            ['model' => 'gpt-5.6-terra', 'messages' => [], 'stream' => true],
            static function (array $event) use (&$events): void {
                $events[] = $event;
            },
        );

        $this->assertSame($response, $actual);
        $this->assertSame([['value' => 'streamed']], $events);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testEndpointMetadataCanSelectTheStreamingTransport(): void
    {
        $psr17Factory = new HttpFactory();
        $client = $this->createMock(StreamingClientInterface::class);
        $response = new Response(
            200,
            ['Content-Type' => 'text/event-stream'],
            "data: {\"type\":\"agent.session.updated\"}\n\n",
        );
        $client->expects($this->once())
            ->method('sendStreamingRequest')
            ->willReturn($response);
        $client->expects($this->never())
            ->method('sendRequest');

        $openAI = new OpenAI(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $client,
            $this->apiKey,
            $this->organization,
            $this->origin,
        );
        $events = [];

        $actual = $openAI->streamAgentSessionEvents(
            ['session_id' => 'sess_abc123'],
            static function (array $event) use (&$events): void {
                $events[] = $event;
            },
        );

        $this->assertSame($response, $actual);
        $this->assertSame([['type' => 'agent.session.updated']], $events);
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
            'listModels.json',
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
            'retrieveModel.json',
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
            'uploadFile.json',
        );

        \unlink($filePath);
    }

    /**
     * @throws ReflectionException
     */
    public function testExtractCallArgumentsWithCallableAsSecondArgument(): void
    {
        $reflection = TestHelper::getPrivateMethod($this->openAI, 'extractCallArguments');
        $callback = static fn() => 'i-am-called';

        // Pass [ parameters, callback ]
        [$parameters, $opts, $streamCallback] = $reflection->invoke(
            $this->openAI,
            [ ['foo' => 'bar'], $callback ],
        );

        $this->assertSame(['foo' => 'bar'], $parameters);
        $this->assertSame([], $opts);
        $this->assertSame($callback, $streamCallback);
    }

    /**
     * @throws OpenAIException
     * @throws Throwable
     */
    public function testRejectsMoreThanThreeEndpointArguments(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Endpoint calls accept at most three arguments.');

        $this->openAI->__call('listModels', [[], [], static fn() => null, []]);
    }

    /**
     * @throws OpenAIException
     * @throws Throwable
     */
    public function testRejectsANonArrayNonCallableSecondArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Second argument must be an array or callable.');

        $this->openAI->__call('listModels', [[], 'invalid']);
    }

    /**
     * @throws OpenAIException
     * @throws Throwable
     */
    public function testRejectsAThirdArgumentWithoutASecondArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Third argument must be a stream callback.');

        $this->openAI->__call('listModels', [[], static fn() => null, static fn() => null]);
    }

    /**
     * @throws Throwable
     */
    public function testSplitsCombinedPathParametersFromTheRequestBody(): void
    {
        $this->sendRequestMock(function (RequestInterface $request) {
            $this->assertSame('/v1/conversations/conv_123', $request->getUri()->getPath());
            $this->assertSame(
                ['metadata' => ['topic' => 'coverage']],
                \json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR),
            );

            return new Response(200, [], '{}');
        });

        $this->openAI->updateConversation([
            'conversation_id' => 'conv_123',
            'metadata' => ['topic' => 'coverage'],
        ]);
    }

    /**
     * @throws Throwable
     */
    public function testRejectsInvalidCustomHeadersInTheFirstArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('customHeaders must be an array.');

        $this->openAI->listModels(['customHeaders' => 'invalid']);
    }

    /**
     * @throws Throwable
     */
    public function testRejectsInvalidCustomHeadersInTheSecondArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('customHeaders must be an array.');

        $this->openAI->createChatCompletion([], ['customHeaders' => 'invalid']);
    }

    /**
     * @throws Throwable
     */
    public function testMergesCustomHeadersFromBothArguments(): void
    {
        $this->sendRequestMock(function (RequestInterface $request) {
            $this->assertSame('first', $request->getHeaderLine('X-First'));
            $this->assertSame('second', $request->getHeaderLine('X-Second'));
            $this->assertSame('second', $request->getHeaderLine('X-Shared'));
            $this->assertArrayNotHasKey(
                'customHeaders',
                \json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR),
            );

            return new Response(200, [], '{}');
        });

        $this->openAI->createChatCompletion(
            ['customHeaders' => ['X-First' => 'first', 'X-Shared' => 'first']],
            [
                'model' => 'gpt-5.4-mini',
                'messages' => [],
                'customHeaders' => ['X-Second' => 'second', 'X-Shared' => 'second'],
            ],
        );
    }

    /**
     * @throws Throwable
     */
    public function testEndpointHeadersAreAppliedAndCanBeOverridden(): void
    {
        $requests = 0;
        $this->sendRequestMock(function (RequestInterface $request) use (&$requests) {
            ++$requests;
            $expected = $requests === 1 ? 'agents=v1' : 'agents=preview';
            $this->assertSame($expected, $request->getHeaderLine('OpenAI-Beta'));

            return new Response(200, [], '{}');
        });

        $this->openAI->listAgents();
        $this->openAI->listAgents([
            'customHeaders' => ['OpenAI-Beta' => 'agents=preview'],
        ]);

        $this->assertSame(2, $requests);
    }

    /**
     * @throws Throwable
     */
    public function testRealtimeCallAcceptsScalarMultipartFields(): void
    {
        $this->sendRequestMock(function (RequestInterface $request) {
            $body = (string) $request->getBody();
            $this->assertStringContainsString('multipart/form-data', $request->getHeaderLine('Content-Type'));
            $this->assertStringContainsString('name="sdp"', $body);
            $this->assertStringContainsString('v=0', $body);
            $this->assertStringContainsString('name="session"', $body);

            return new Response(200, [], 'v=0');
        });

        $this->openAI->createRealtimeCall([
            'sdp' => "v=0\r\n",
            'session' => '{"type":"realtime","model":"gpt-realtime"}',
        ]);
    }

    /**
     * @throws ReflectionException
     */
    public function testInfersLegacyEndpointBodyTypes(): void
    {
        $reflection = TestHelper::getPrivateMethod($this->openAI, 'inferBodyType');

        $this->assertSame('none', $reflection->invoke($this->openAI, 'GET', '/models'));
        $this->assertSame('multipart', $reflection->invoke($this->openAI, 'POST', '/audio/transcriptions'));
        $this->assertSame('json', $reflection->invoke($this->openAI, 'POST', '/responses'));
    }

    /**
     * @throws ReflectionException
     */
    public function testCreatesLegacyJsonHeadersFromFalseMultipartFlag(): void
    {
        $reflection = TestHelper::getPrivateMethod($this->openAI, 'createHeaders');
        $headers = $reflection->invoke($this->openAI, false);

        $this->assertIsArray($headers);
        $this->assertSame('application/json', $headers['Content-Type']);
    }

    /**
     * Ensure that GET requests with parameters and options
     * get merged into the URI query string.
     */
    public function testListModelsAddsQueryParametersToUri(): void
    {
        $this->sendRequestMock(function (RequestInterface $request) {
            $query = $request->getUri()->getQuery();

            $this->assertStringContainsString('foo=bar', $query);
            $this->assertStringContainsString('baz=qux', $query);

            return new Response(200, [], '{"success":true}');
        });

        $response = $this->openAI->listModels(
            ['foo' => 'bar'],
            ['baz' => 'qux'],
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Custom headers on GET requests must not be serialized as query parameters.
     */
    public function testGetRequestExtractsCustomHeaders(): void
    {
        $this->sendRequestMock(function (RequestInterface $request) {
            $this->assertSame('test-request-id', $request->getHeaderLine('X-Client-Request-Id'));
            $this->assertStringNotContainsString('customHeaders', $request->getUri()->getQuery());

            return new Response(200, [], '{}');
        });

        $this->openAI->listModels([
            'limit' => 10,
            'customHeaders' => ['X-Client-Request-Id' => 'test-request-id'],
        ]);
    }

    /**
     * @throws Throwable
     */
    public function testEndpointQueryParametersAreAppliedAndCanBeOverridden(): void
    {
        $requests = 0;
        $this->sendRequestMock(function (RequestInterface $request) use (&$requests) {
            ++$requests;
            $query = [];
            \parse_str($request->getUri()->getQuery(), $query);

            $this->assertSame($requests === 1 ? 'true' : 'false', $query['beta']);
            $this->assertSame('file_search_call.results', $query['include']);

            return new Response(200, [], '{}');
        });

        $this->openAI->getBetaResponse([
            'response_id' => 'resp_abc123',
            'include' => 'file_search_call.results',
        ]);
        $this->openAI->getBetaResponse([
            'response_id' => 'resp_abc123',
            'beta' => 'false',
            'include' => 'file_search_call.results',
        ]);

        $this->assertSame(2, $requests);
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
        self::assertEquals($fakeResponseBody, (string) $response->getBody());
    }

    /**
     * Mocks an API call with streaming support using a callable and a response file.
     *
     * Mocks the HTTP client to return a predefined streaming response loaded from a file,
     * and utilizes the provided stream callback to process the response.
     *
     * @param callable $apiCall       The API call to test.
     * @param callable $streamCallback The callback function to handle streaming data.
     *
     * @throws Exception
     */
    private function testApiCallWithStreaming(callable $apiCall, callable $streamCallback): void
    {
        $fakeResponseContent = TestHelper::loadResponseFromFile('chatCompletionStreaming.txt');
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
            ->method('sendRequest')
            ->willReturnCallback($responseCallback);
    }
}
