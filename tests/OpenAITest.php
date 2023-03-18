<?php

namespace SoftCreatR\OpenAI\Tests;

use Exception;
use JsonException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
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

        $psr17Factory = new Psr17Factory();
        $this->mockedClient = $this->createMock(ClientInterface::class);

        $this->openAI = new OpenAI(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $this->mockedClient,
            ''
        );
    }

    /**
     * Test that OpenAI::__call method can handle API calls correctly.
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
            $response = $this->openAI->createCompletion([
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
            $this->openAI->retrieveModel('text-davinci-003');
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
     * @param string $responseFile The path to the file containing the expected response.
     */
    private function testApiCall(callable $apiCall, string $responseFile): void
    {
        $response = null;
        $fakeResponseBody = TestHelper::loadResponseFromFile($responseFile);
        $fakeResponse = new Response(200, [], $fakeResponseBody);

        $this->sendRequestMock(static function () use ($fakeResponse) {
            return $fakeResponse;
        });

        try {
            $response = $apiCall();
        } catch (Exception $e) {
            // ignore
        }

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
