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

use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use SoftCreatR\OpenAI\Exception\OpenAIException;

use const JSON_THROW_ON_ERROR;

/**
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
    /**
     * The default model to be used when none is specified.
     */
    private const DEFAULT_MODEL = 'text-davinci-002';

    /**
     * The default chat model to be used when none is specified.
     */
    private const DEFAULT_CHAT_MODEL = 'gpt-3.5-turbo';

    /**
     * The HTTP client instance used for sending requests.
     *
     * @var ClientInterface
     */
    private ClientInterface $httpClient;

    /**
     * The PSR-17 request factory instance used for creating requests.
     *
     * @var RequestFactoryInterface
     */
    private RequestFactoryInterface $requestFactory;

    /**
     * The PSR-17 stream factory instance used for creating request bodies.
     *
     * @var StreamFactoryInterface
     */
    private StreamFactoryInterface $streamFactory;

    /**
     * The PSR-17 URI factory instance used for creating URIs.
     *
     * @var UriFactoryInterface
     */
    private UriFactoryInterface $uriFactory;

    /**
     * The API key used for authentication.
     *
     * @var string
     */
    private string $apiKey;

    /**
     * The name of the organization associated with the API key.
     *
     * @var string|null
     */
    private ?string $organisation;

    public function __construct(
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        UriFactoryInterface $uriFactory,
        ClientInterface $httpClient,
        string $apiKey,
        ?string $organisation = null
    ) {
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->uriFactory = $uriFactory;
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->organisation = $organisation;
    }

    /**
     * Magic method to call the OpenAI API endpoints.
     *
     * @param string $key The endpoint method.
     * @param array $args The arguments for the endpoint method.
     *
     * @return ResponseInterface The API response.
     *
     * @throws OpenAIException If the API returns an error (HTTP status code >= 400).
     */
    public function __call(string $key, array $args): ResponseInterface
    {
        $endpoint = OpenAIURLBuilder::getEndpoint($key);
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
     * @throws OpenAIException If the API returns an error (HTTP status code >= 400).
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
     * @throws OpenAIException If the API returns an error (HTTP status code >= 400).
     */
    public function createChatCompletion(array $opts): ResponseInterface
    {
        return $this->processWithOptions('createChatCompletion', self::DEFAULT_CHAT_MODEL, $opts);
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
     * @throws OpenAIException If the API returns an error (HTTP status code >= 400).
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
     * @throws OpenAIException If the API returns an error (HTTP status code >= 400).
     */
    private function callAPI(string $method, string $key, ?string $parameter = null, array $opts = []): ResponseInterface
    {
        return $this->sendRequest(OpenAIURLBuilder::createUrl($key, $parameter), $method, $opts);
    }

    /**
     * Sends an HTTP request to the OpenAI API and returns the response.
     *
     * @param string $url The URL to send the request to.
     * @param string $method The HTTP method to use (e.g., 'GET', 'POST', etc.).
     * @param array $params An associative array of parameters to send with the request (optional).
     *
     * @return ResponseInterface The response from the OpenAI API.
     *
     * @throws OpenAIException If the API returns an error (HTTP status code >= 400).
     */
    private function sendRequest(string $url, string $method, array $params = []): ResponseInterface
    {
        $uri = $this->uriFactory->createUri($url);
        $request = $this->requestFactory->createRequest($method, $uri);

        $isMultipart = $this->isMultipartRequest($params);
        $boundary = $isMultipart ? $this->generateMultipartBoundary() : '';
        $headers = $this->createHeaders($isMultipart, $boundary);
        $request = $this->applyHeaders($request, $headers);

        $body = $isMultipart
            ? $this->createMultipartStream($params, $boundary)
            : $this->createJsonBody($params);

        if (!empty($body)) {
            $request = $request->withBody($this->streamFactory->createStream($body));
        }

        try {
            $response = $this->httpClient->sendRequest($request);

            // Check if the response has a non-200 status code (error)
            if ($response->getStatusCode() >= 400) {
                throw new OpenAIException($response->getBody()->getContents(), $response->getStatusCode());
            }
        } catch (ClientExceptionInterface $e) {
            throw new OpenAIException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        return $response;
    }

    /**
     * Generates a unique multipart boundary string.
     *
     * @return string The generated multipart boundary string.
     */
    private function generateMultipartBoundary(): string
    {
        return '----OpenAI' . \hash('sha256', \uniqid('', true));
    }

    /**
     * Creates the headers for an API request.
     *
     * @param bool $isMultipart Indicates whether the request is multipart or not.
     * @param string|null $boundary The multipart boundary string, if applicable.
     *
     * @return array An associative array of headers.
     */
    private function createHeaders(bool $isMultipart, ?string $boundary): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'OpenAI-Organization' => $this->organisation ?: '',
            'Content-Type' => $isMultipart
                ? "multipart/form-data; boundary={$boundary}"
                : 'application/json',
        ];
    }

    /**
     * Applies the headers to the given request.
     *
     * @param RequestInterface $request The request to apply headers to.
     * @param array $headers An associative array of headers to apply.
     *
     * @return RequestInterface The request with headers applied.
     */
    private function applyHeaders(RequestInterface $request, array $headers): RequestInterface
    {
        foreach ($headers as $key => $value) {
            $request = $request->withHeader($key, $value);
        }

        return $request;
    }

    /**
     * Creates a JSON encoded body string from the given parameters.
     *
     * @param array $params An associative array of parameters to encode as JSON.
     *
     * @return string The JSON encoded body string, or an empty string if encoding fails.
     */
    private function createJsonBody(array $params): string
    {
        try {
            return !empty($params) ? \json_encode($params, JSON_THROW_ON_ERROR) : '';
        } catch (JsonException $e) {
            // Fallback to an empty string if encoding fails
            return '';
        }
    }

    /**
     * Creates a multipart stream for sending files, images, or masks in a request.
     *
     * @param array $params An associative array of parameters to send with the request.
     * @param string $multipartBoundary A string used as a boundary to separate parts of the multipart stream.
     *
     * @return string The multipart stream as a string.
     */
    private function createMultipartStream(array $params, string $multipartBoundary): string
    {
        $multipartStream = '';

        foreach ($params as $key => $value) {
            $multipartStream .= "--{$multipartBoundary}\r\n";
            $multipartStream .= "Content-Disposition: form-data; name=\"{$key}\"";

            if (\in_array($key, ['file', 'image', 'mask'], true)) {
                $filename = \basename($value);
                $multipartStream .= "; filename=\"{$filename}\"\r\n";
                $multipartStream .= "Content-Type: application/octet-stream\r\n";
                $multipartStream .= "\r\n" . \file_get_contents($value) . "\r\n";
            } else {
                $multipartStream .= "\r\n\r\n" . $value . "\r\n";
            }
        }

        $multipartStream .= "--{$multipartBoundary}--\r\n";

        return $multipartStream;
    }

    /**
     * Determines if a request is a multipart request based on the provided parameters.
     *
     * @param array $params An associative array of parameters to check for multipart request indicators.
     *
     * @return bool True if the request is a multipart request, false otherwise.
     */
    private function isMultipartRequest(array $params): bool
    {
        return \array_intersect_key(\array_flip(['file', 'image', 'mask']), $params) !== [];
    }
}
