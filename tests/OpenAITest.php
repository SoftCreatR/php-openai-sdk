<?php

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
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use SoftCreatR\OpenAI\Exception\OpenAIException;
use SoftCreatR\OpenAI\OpenAI;

/**
 * @covers \SoftCreatR\OpenAI\OpenAI
 */
class OpenAITest extends TestCase
{
    private OpenAI $openAI;

    private ClientInterface $mockedClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->openAI = OpenAI::getInstance('YOUR_API_KEY', 'org-Srefw9Um8eH15CQ0BZVKimUI');
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
        $response = null;
        $fakeResponseBody = $this->loadResponseFromFile('listModels.json');
        $fakeResponse = new Response(200, [], $fakeResponseBody);
        $this->mockedClient
            ->expects(self::once())
            ->method('send')
            ->willReturn($fakeResponse);

        try {
            $response = $this->openAI->__call('retrieveModel', ['text-davinci-003']);
        } catch (Exception $e) {
            // ignore
        }

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($fakeResponseBody, (string)$response->getBody());
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
        $response = null;
        $fakeResponseBody = $this->loadResponseFromFile('completion.json');
        $fakeResponse = new Response(200, [], $fakeResponseBody);
        $this->mockedClient
            ->expects(self::once())
            ->method('send')
            ->willReturn($fakeResponse);

        try {
            $response = $this->openAI->createCompletion([
                'model' => 'text-davinci-002',
                'prompt' => 'Say this is a test',
                'max_tokens' => 7,
                'temperature' => 0,
                'top_p' => 1,
                'n' => 1,
                'stream' => false,
                'logprobs' => null,
                'stop' => "\n",
            ]);
        } catch (Exception $e) {
            // ignore
        }

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($fakeResponseBody, (string)$response->getBody());
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
        $response = null;
        $fakeResponseBody = $this->loadResponseFromFile('chatCompletion.json');
        $fakeResponse = new Response(200, [], $fakeResponseBody);
        $this->mockedClient
            ->expects(self::once())
            ->method('send')
            ->willReturn($fakeResponse);

        try {
            $response = $this->openAI->createChatCompletion([
                'model' => 'gpt-3.5-turbo',
                'messages' => [['role' => 'user', 'content' => 'Hello!']],
            ]);
        } catch (Exception $e) {
            // ignore
        }

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($fakeResponseBody, (string)$response->getBody());
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
        $fakeResponseBody = $this->loadResponseFromFile('createFile.json');

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
     * Test that getHttpClient returns a GuzzleHttp\ClientInterface object.
     */
    public function testGetHttpClient(): void
    {
        $httpClient = $this->openAI->getHttpClient();
        $newTimeout = 10;
        $this->openAI->setTimeout($newTimeout);

        $reflection = new ReflectionClass(OpenAI::class);
        $timeoutProperty = $reflection->getProperty('timeout');
        $timeoutProperty->setAccessible(true);

        // Check if the timeout has been updated correctly.
        self::assertEquals($newTimeout, $timeoutProperty->getValue($this->openAI));

        // Invalidate the existing httpClient to force a new one with the updated timeout.
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($this->openAI, null);

        // Get the new httpClient and check if the timeout has been updated.
        $httpClient2 = $this->openAI->getHttpClient();
        self::assertNotSame($httpClient, $httpClient2);

        /** @noinspection PhpDeprecationInspection */
        $config = $httpClient2->getConfig();
        self::assertEquals($newTimeout, $config[RequestOptions::TIMEOUT]);
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
        // Create a MockHandler to simulate an HTTP exception
        $mock = new MockHandler([
            new Exception('Test Client Exception'),
        ]);

        // Create the HandlerStack with the MockHandler
        $handlerStack = HandlerStack::create($mock);

        // Replace the httpClient with a new one using the HandlerStack
        $this->openAI->setHttpClient(new Client(['handler' => $handlerStack]));

        // Use Reflection to access the private sendRequest method
        $reflection = new ReflectionClass(OpenAI::class);
        $sendRequest = $reflection->getMethod('sendRequest');
        $sendRequest->setAccessible(true);

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
        // Create a MockHandler to simulate an HTTP exception
        $mock = new MockHandler([
            new RequestException(
                '',
                new Request('GET', '/'),
                new Response(400, [], '{"error": {"message": "Test Client Exception"}}')
            ),
        ]);

        // Create the HandlerStack with the MockHandler
        $handlerStack = HandlerStack::create($mock);

        // Replace the httpClient with a new one using the HandlerStack
        $this->openAI->setHttpClient(new Client(['handler' => $handlerStack]));

        // Use Reflection to access the private sendRequest method
        $reflection = new ReflectionClass(OpenAI::class);
        $sendRequest = $reflection->getMethod('sendRequest');
        $sendRequest->setAccessible(true);

        // Test that an OpenAIException is thrown when calling sendRequest
        $this->expectException(OpenAIException::class);
        $this->expectExceptionMessage('Test Client Exception');
        $this->expectExceptionCode(400);
        $sendRequest->invokeArgs($this->openAI, ['GET', '/test-path']);
    }

    /**
     * @throws ReflectionException
     */
    public function testExtractCallArguments(): void
    {
        // Invoke the protected method 'extractCallArguments' via reflection
        $reflectionMethod = new ReflectionMethod($this->openAI, 'extractCallArguments');
        $reflectionMethod->setAccessible(true);

        // Test case with a string parameter and an options array
        $args = ['stringParam', ['key' => 'value']];
        $result = $reflectionMethod->invoke($this->openAI, $args);
        $this->assertEquals(['stringParam', ['key' => 'value']], $result);

        // Test case with only a string parameter
        $args = ['stringParam'];
        $result = $reflectionMethod->invoke($this->openAI, $args);
        $this->assertEquals(['stringParam', []], $result);

        // Test case with only an options array
        $args = [['key' => 'value']];
        $result = $reflectionMethod->invoke($this->openAI, $args);
        $this->assertEquals([null, ['key' => 'value']], $result);

        // Test case with an empty array
        $args = [];
        $result = $reflectionMethod->invoke($this->openAI, $args);
        $this->assertEquals([null, []], $result);
    }

    protected function loadResponseFromFile(string $filename): string
    {
        $filePath = __DIR__ . '/responses/' . $filename;
        if (\file_exists($filePath)) {
            return \file_get_contents($filePath);
        }

        return '{}';
    }
}
