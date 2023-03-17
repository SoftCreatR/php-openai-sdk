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

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use SoftCreatR\OpenAI\Exception\OpenAIException;

use const JSON_THROW_ON_ERROR;

/**
 * Class ScOpenAI
 *
 * A wrapper for the OpenAI API.
 *
 * @method ResponseInterface cancelFineTune(string $id)
 * @method ResponseInterface createEdit(array $options = [])
 * @method ResponseInterface createEmbedding(array $options = [])
 * @method ResponseInterface createFile(array $options = [])
 * @method ResponseInterface createFineTune(array $options = [])
 * @method ResponseInterface createImage(array $options = [])
 * @method ResponseInterface createImageEdit(array $options = [])
 * @method ResponseInterface createImageVariation(array $options = [])
 * @method ResponseInterface createModeration(array $options = [])
 * @method ResponseInterface createTranscription(array $options = [])
 * @method ResponseInterface createTranslation(array $options = [])
 * @method ResponseInterface deleteFile(string $id)
 * @method ResponseInterface deleteModel(string $id)
 * @method ResponseInterface downloadFile(string $id)
 * @method ResponseInterface listFiles()
 * @method ResponseInterface listFineTuneEvents(string $id, array $options = [])
 * @method ResponseInterface listFineTunes()
 * @method ResponseInterface listModels()
 * @method ResponseInterface retrieveFile(string $id)
 * @method ResponseInterface retrieveFineTune(string $id)
 * @method ResponseInterface retrieveModel(string $id)
 */
class OpenAI
{
    private const DEFAULT_MODEL = 'text-davinci-002';

    private const DEFAULT_CHAT_MODEL = 'gpt-3.5-turbo';

    /**
     * @var array<string, string> Custom headers for the API requests.
     */
    private array $headers = [];

    /**
     * @var int Timeout for the API requests in seconds.
     */
    private int $timeout = 60;

    /**
     * @var string The API key.
     */
    private string $apiKey;

    /**
     * @var ?ClientInterface Instance of a configured Guzzle HTTP client, using cURL.
     */
    private ?ClientInterface $httpClient;

    /**
     * @var self|null The singleton instance of the OpenAI class.
     */
    private static ?OpenAI $instance = null;

    /**
     * Private constructor to prevent creating a new instance outside the class.
     */
    private function __construct(string $apiKey, ?string $organisation = null)
    {
        $this->apiKey = $apiKey;
        $this->headers['openai-organization'] = $organisation;
    }

    /**
     * Get the singleton instance of the OpenAI class.
     *
     * @return OpenAI The singleton instance of the OpenAI class.
     */
    public static function getInstance(string $apiKey, ?string $organisation = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($apiKey, $organisation);
        }

        return self::$instance;
    }

    /**
     * Magic method to call the OpenAI API endpoints.
     *
     * @param string $key The endpoint method.
     * @param array $args The arguments for the endpoint method.
     *
     * @return ResponseInterface The API response.
     *
     * @throws OpenAIException If an error occurs during the API request.
     * @throws JsonException If there is an error while encoding the request JSON.
     */
    public function __call(string $key, array $args): ResponseInterface
    {
        $endpoint = OpenAIUrlFactory::getEndpoint($key);
        $httpMethod = $endpoint['method'];

        [$parameter, $opts] = $this->extractCallArguments($args);

        return $this->callAPI($httpMethod, $key, $parameter, $opts);
    }

    /**
     * Extracts the arguments from the input array.
     *
     * @param array $args The input arguments.
     * @return array An array containing the extracted parameter and options.
     */
    private function extractCallArguments(array $args): array
    {
        $parameter = null;
        $opts = [];

        if (!isset($args[0])) {
            return [$parameter, $opts];
        }

        if (\is_string($args[0])) {
            $parameter = $args[0];

            if (isset($args[1]) && \is_array($args[1])) {
                $opts = $args[1];
            }
        } elseif (\is_array($args[0])) {
            $opts = $args[0];
        }

        return [$parameter, $opts];
    }

    /**
     * Sends a completion request to the OpenAI API.
     *
     * @param array $opts The options for the completion request.
     *
     * @return ResponseInterface The API response.
     *
     * @throws OpenAIException If an error occurs during the API request.
     * @throws JsonException If there is an error while encoding the request JSON.
     */
    public function createCompletion(array $opts): ResponseInterface
    {
        return $this->processWithOptions('createCompletion', self::DEFAULT_MODEL, $opts);
    }

    /**
     * Sends a chat completion request to the OpenAI API.
     *
     * @param array $opts The options for the chat completion request.
     *
     * @return ResponseInterface The API response.
     *
     * @throws OpenAIException If an error occurs during the API request.
     * @throws JsonException
     */
    public function createChatCompletion(array $opts): ResponseInterface
    {
        return $this->processWithOptions('createChatCompletion', self::DEFAULT_CHAT_MODEL, $opts);
    }

    /**
     * Set the timeout value for the HTTP client.
     *
     * @param int $timeout The timeout value in seconds.
     *
     * @return self Returns the current instance to allow for method chaining.
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;

        $this->httpClient = null;

        return self::$instance;
    }

    /**
     * Set the HTTP client instance.
     *
     * @param ClientInterface $client The HTTP client instance to use.
     *
     * @return self Returns the current instance to allow for method chaining.
     */
    public function setHttpClient(ClientInterface $client): self
    {
        $this->httpClient = $client;

        return $this;
    }

    /**
     * Returns a configured Guzzle HTTP client.
     *
     * @return ClientInterface The configured Guzzle HTTP client.
     */
    public function getHttpClient(): ClientInterface
    {
        if (!isset($this->httpClient)) {
            $this->setHttpClient(new Client([RequestOptions::TIMEOUT => $this->timeout]));
        }

        return $this->httpClient;
    }

    /**
     * Processes a request to the OpenAI API with the provided options.
     *
     * @param string $endpoint The API endpoint.
     * @param string $defaultModel The default model to use if not provided in the options.
     * @param array $opts The options for the request.
     *
     * @return ResponseInterface The API response.
     *
     * @throws OpenAIException If an error occurs during the API request.
     * @throws JsonException If there is an error while encoding the request JSON.
     */
    private function processWithOptions(string $endpoint, string $defaultModel, array $opts): ResponseInterface
    {
        $opts['model'] = $opts['model'] ?? $defaultModel;

        return $this->callAPI('POST', $endpoint, null, $opts);
    }

    /**
     * Calls the OpenAI API with the provided method, key, parameter, and options.
     *
     * @param string $method The HTTP method for the request.
     * @param string $key The API endpoint key.
     * @param string|null $parameter An optional parameter for the request.
     * @param array $opts The options for the request.
     *
     * @return ResponseInterface The API response.
     *
     * @throws OpenAIException If an error occurs during the API request.
     * @throws JsonException If there is an error while encoding the request JSON.
     */
    private function callAPI(string $method, string $key, ?string $parameter = null, array $opts = []): ResponseInterface
    {
        return $this->sendRequest(OpenAIUrlFactory::createUrl($key, $parameter), $method, $opts);
    }

    /**
     * Sends an HTTP request to the OpenAI API.
     *
     * @param string $uri The URL for the API request.
     * @param string $method The HTTP method for the request.
     * @param array $opts The options for the request.
     *
     * @return ResponseInterface The API response.
     *
     * @throws OpenAIException If an error occurs during the API request.
     * @throws JsonException If there is an error while encoding the request JSON.
     */
    private function sendRequest(string $uri, string $method, array $opts = []): ResponseInterface
    {
        $headers = $this->buildHeaders();
        $requestBody = $this->buildRequestBody($opts, $headers);

        try {
            $request = new Request($method, $uri, \array_merge($this->headers, $headers), $requestBody);

            return $this->getHttpClient()->send($request);
        } catch (RequestException | ClientExceptionInterface | Exception $e) {
            return $this->handleSendRequestExceptions($e);
        }
    }

    /**
     * Builds the headers for the API request.
     *
     * @return array An associative array of header names and their corresponding values.
     */
    private function buildHeaders(): array
    {
        return ['authorization' => 'Bearer ' . $this->apiKey];
    }

    /**
     * Builds the request body based on the provided options.
     *
     * @param array $opts An associative array of options for the API request.
     *
     * @return mixed The request body as a JSON-encoded string, a MultipartStream object, or an empty string.
     *
     * @throws JsonException If a JSON encoding error occurs.
     */
    private function buildRequestBody(array $opts, array &$headers): string
    {
        if (\array_key_exists('file', $opts) || \array_key_exists('image', $opts)) {
            $multipart = $this->buildMultipart($opts);

            $requestBody = new MultipartStream($multipart);
        } else {
            $headers['content-type'] = 'application/json';
            $requestBody = !empty($opts) ? \json_encode($opts, JSON_THROW_ON_ERROR) : '';
        }

        return $requestBody;
    }

    /**
     * Builds the multipart content for a multipart request.
     *
     * @param array $opts An associative array of options for the API request.
     *
     * @return array An array of multipart content.
     */
    private function buildMultipart(array $opts): array
    {
        $multipart = [];

        foreach ($opts as $key => $value) {
            if ($key === 'file' || $key === 'image' || $key === 'mask') {
                $multipart[] = [
                    'name' => $key,
                    'contents' => \fopen($value, 'rb'),
                ];
            } else {
                $multipart[] = [
                    'name' => $key,
                    'contents' => $value,
                ];
            }
        }

        return $multipart;
    }

    /**
     * Handles exceptions that occur during the sendRequest method.
     *
     * @param RequestException|ClientExceptionInterface|Exception $e The exception that occurred.
     *
     * @throws OpenAIException A custom exception with the appropriate error message and code.
     */
    private function handleSendRequestExceptions(Exception $e): ResponseInterface
    {
        if (\method_exists($e, 'getResponse') && null !== $e->getResponse()) {
            $message = $e->getResponse()->getBody()->getContents();
        } else {
            $message = $e->getMessage();
        }

        throw new OpenAIException($message, $e->getCode(), $e);
    }
}
