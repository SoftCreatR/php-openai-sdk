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

namespace SoftCreatR\OpenAI;

use InvalidArgumentException;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Random\RandomException;
use SensitiveParameter;
use SoftCreatR\OpenAI\Exception\OpenAIException;

use const JSON_THROW_ON_ERROR;

/**
 * A wrapper for the OpenAI API.
 *
 * @method ResponseInterface|null createSpeech(array $parameters = [], array $options = []) Generates audio from the input text.
 * @method ResponseInterface|null createTranscription(array $parameters = [], array $options = []) Transcribes audio into the input language.
 * @method ResponseInterface|null createTranslation(array $parameters = [], array $options = []) Translates audio into English.
 *
 * @method ResponseInterface|null createResponse(array $options = [], ?\callable $streamCallback = null) Creates a response object.
 * @method ResponseInterface|null getResponse(array $parameters = [], array $options = []) Retrieves a response by its ID.
 * @method ResponseInterface|null deleteResponse(array $parameters = [], array $options = []) Deletes a response.
 * @method ResponseInterface|null listInputItems(array $parameters = [], array $options = []) Lists input items for a given response.
 *
 * @method ResponseInterface|null createChatCompletion(array $options, ?\callable $streamCallback = null) Creates a model response for the given chat conversation.
 * @method ResponseInterface|null getChatCompletion(array $parameters = [], array $options = []) Retrieves a chat completion by its ID.
 * @method ResponseInterface|null getChatMessages(array $parameters = [], array $options = []) Retrieves messages for a chat completion.
 * @method ResponseInterface|null listChatCompletions(array $parameters = [], array $options = []) Lists chat completions.
 * @method ResponseInterface|null updateChatCompletion(array $parameters = [], array $options = []) Updates a chat completion.
 * @method ResponseInterface|null deleteChatCompletion(array $parameters = [], array $options = []) Deletes a chat completion.
 *
 * @method ResponseInterface|null createEmbedding(array $parameters = [], array $options = []) Creates an embedding vector representing the input text.
 *
 * @method ResponseInterface|null createFineTuningJob(array $parameters = [], array $options = []) Creates a fine-tuning job to create a new model from a given dataset.
 * @method ResponseInterface|null listFineTuningJobs(array $parameters = [], array $options = []) Lists your organization's fine-tuning jobs.
 * @method ResponseInterface|null listFineTuningEvents(array $parameters = [], array $options = []) Gets status updates for a fine-tuning job.
 * @method ResponseInterface|null listFineTuningCheckpoints(array $parameters = [], array $options = []) Lists checkpoints for a fine-tuning job.
 * @method ResponseInterface|null retrieveFineTuningJob(array $parameters = [], array $options = []) Gets info about a fine-tuning job.
 * @method ResponseInterface|null cancelFineTuning(array $parameters = [], array $options = []) Immediately cancels a fine-tuning job.
 *
 * @method ResponseInterface|null createBatch(array $parameters = [], array $options = []) Creates and executes a batch from an uploaded file of requests.
 * @method ResponseInterface|null retrieveBatch(array $parameters = [], array $options = []) Retrieves a batch.
 * @method ResponseInterface|null cancelBatch(array $parameters = [], array $options = []) Cancels an in-progress batch.
 * @method ResponseInterface|null listBatches(array $parameters = [], array $options = []) Lists your organization's batches.
 *
 * @method ResponseInterface|null uploadFile(array $parameters = [], array $options = []) Uploads a file that can be used across various endpoints.
 * @method ResponseInterface|null listFiles(array $parameters = [], array $options = []) Returns a list of files that belong to the user's organization.
 * @method ResponseInterface|null retrieveFile(array $parameters = [], array $options = []) Returns information about a specific file.
 * @method ResponseInterface|null deleteFile(array $parameters = [], array $options = []) Deletes a file.
 * @method ResponseInterface|null retrieveFileContent(array $parameters = [], array $options = []) Returns the contents of the specified file.
 *
 * @method ResponseInterface|null createUpload(array $parameters = [], array $options = []) Creates an intermediate Upload object that you can add parts to.
 * @method ResponseInterface|null addUploadPart(array $parameters = [], array $options = []) Adds a part to an Upload object.
 * @method ResponseInterface|null completeUpload(array $parameters = [], array $options = []) Completes the upload.
 * @method ResponseInterface|null cancelUpload(array $parameters = [], array $options = []) Cancels the upload.
 *
 * @method ResponseInterface|null createImage(array $parameters = [], array $options = []) Creates an image given a prompt.
 * @method ResponseInterface|null createImageEdit(array $parameters = [], array $options = []) Creates an edited or extended image given an original image and a prompt.
 * @method ResponseInterface|null createImageVariation(array $parameters = [], array $options = []) Creates a variation of a given image.
 *
 * @method ResponseInterface|null listModels(array $parameters = [], array $options = []) Lists the currently available models.
 * @method ResponseInterface|null retrieveModel(array $parameters = [], array $options = []) Retrieves a model instance.
 * @method ResponseInterface|null deleteModel(array $parameters = [], array $options = []) Deletes a fine-tuned model.
 *
 * @method ResponseInterface|null createModeration(array $parameters = [], array $options = []) Classifies if text and/or image inputs are potentially harmful.
 *
 * @method ResponseInterface|null createAssistant(array $parameters = [], array $options = []) Creates an assistant with a model and instructions.
 * @method ResponseInterface|null listAssistants(array $parameters = [], array $options = []) Returns a list of assistants.
 * @method ResponseInterface|null retrieveAssistant(array $parameters = [], array $options = []) Retrieves an assistant.
 * @method ResponseInterface|null modifyAssistant(array $parameters = [], array $options = []) Modifies an assistant.
 * @method ResponseInterface|null deleteAssistant(array $parameters = [], array $options = []) Deletes an assistant.
 *
 * @method ResponseInterface|null createThread(array $parameters = [], array $options = []) Creates a thread.
 * @method ResponseInterface|null retrieveThread(array $parameters = [], array $options = []) Retrieves a thread.
 * @method ResponseInterface|null modifyThread(array $parameters = [], array $options = []) Modifies a thread.
 * @method ResponseInterface|null deleteThread(array $parameters = [], array $options = []) Deletes a thread.
 *
 * @method ResponseInterface|null createMessage(array $parameters = [], array $options = []) Creates a message in a thread.
 * @method ResponseInterface|null listMessages(array $parameters = [], array $options = []) Returns a list of messages for a given thread.
 * @method ResponseInterface|null retrieveMessage(array $parameters = [], array $options = []) Retrieves a message.
 * @method ResponseInterface|null modifyMessage(array $parameters = [], array $options = []) Modifies a message.
 * @method ResponseInterface|null deleteMessage(array $parameters = [], array $options = []) Deletes a message.
 *
 * @method ResponseInterface|null createRun(array $parameters = [], array $options = [], ?\callable $streamCallback = null) Creates a run.
 * @method ResponseInterface|null createThreadAndRun(array $options = [], ?\callable $streamCallback = null) Creates a thread and runs it in one request.
 * @method ResponseInterface|null listRuns(array $parameters = [], array $options = []) Returns a list of runs belonging to a thread.
 * @method ResponseInterface|null retrieveRun(array $parameters = [], array $options = []) Retrieves a run.
 * @method ResponseInterface|null modifyRun(array $parameters = [], array $options = []) Modifies a run.
 * @method ResponseInterface|null submitToolOutputsToRun(array $parameters = [], array $options = [], ?\callable $streamCallback = null) Submits tool outputs to a run.
 * @method ResponseInterface|null cancelRun(array $parameters = [], array $options = []) Cancels a run in progress.
 *
 * @method ResponseInterface|null listRunSteps(array $parameters = [], array $options = []) Returns a list of run steps belonging to a run.
 * @method ResponseInterface|null retrieveRunStep(array $parameters = [], array $options = []) Retrieves a run step.
 *
 * @method ResponseInterface|null createVectorStore(array $parameters = [], array $options = []) Creates a vector store.
 * @method ResponseInterface|null listVectorStores(array $parameters = [], array $options = []) Returns a list of vector stores.
 * @method ResponseInterface|null retrieveVectorStore(array $parameters = [], array $options = []) Retrieves a vector store.
 * @method ResponseInterface|null modifyVectorStore(array $parameters = [], array $options = []) Modifies a vector store.
 * @method ResponseInterface|null deleteVectorStore(array $parameters = [], array $options = []) Deletes a vector store.
 * @method ResponseInterface|null searchVectorStore(array $parameters = [], array $options = []) Search vector store.
 *
 * @method ResponseInterface|null createVectorStoreFile(array $parameters = [], array $options = []) Creates a vector store file by attaching a file to a vector store.
 * @method ResponseInterface|null listVectorStoreFiles(array $parameters = [], array $options = []) Returns a list of vector store files.
 * @method ResponseInterface|null retrieveVectorStoreFile(array $parameters = [], array $options = []) Retrieves a vector store file.
 * @method ResponseInterface|null retrieveVectorStoreFileContent(array $parameters = [], array $options = []) Retrieve vector store file content.
 * @method ResponseInterface|null updateVectorStoreFileAttributes(array $parameters = [], array $options = []) Update vector store file attributes.
 * @method ResponseInterface|null deleteVectorStoreFile(array $parameters = [], array $options = []) Deletes a vector store file.
 *
 * @method ResponseInterface|null createVectorStoreFileBatch(array $parameters = [], array $options = []) Creates a vector store file batch.
 * @method ResponseInterface|null retrieveVectorStoreFileBatch(array $parameters = [], array $options = []) Retrieves a vector store file batch.
 * @method ResponseInterface|null cancelVectorStoreFileBatch(array $parameters = [], array $options = []) Cancels a vector store file batch.
 * @method ResponseInterface|null listVectorStoreFilesInBatch(array $parameters = [], array $options = []) Returns a list of vector store files in a batch.
 *
 * @method ResponseInterface|null listAdminApiKeys(array $parameters = [], array $options = []) Returns a list of admin API keys.
 * @method ResponseInterface|null createAdminApiKey(array $parameters = [], array $options = []) Creates an admin API key.
 * @method ResponseInterface|null retrieveAdminApiKey(array $parameters = [], array $options = []) Retrieves an admin API key.
 * @method ResponseInterface|null deleteAdminApiKey(array $parameters = [], array $options = []) Deletes an admin API key.
 *
 * @method ResponseInterface|null listInvites(array $parameters = [], array $options = []) Returns a list of invites in the organization.
 * @method ResponseInterface|null createInvite(array $parameters = [], array $options = []) Creates an invitation for a user to the organization.
 * @method ResponseInterface|null retrieveInvite(array $parameters = [], array $options = []) Retrieves an invitation.
 * @method ResponseInterface|null deleteInvite(array $parameters = [], array $options = []) Deletes an invitation.
 *
 * @method ResponseInterface|null listUsers(array $parameters = [], array $options = []) Lists all the users in the organization.
 * @method ResponseInterface|null modifyUser(array $parameters = [], array $options = []) Modifies a user's role in the organization.
 * @method ResponseInterface|null retrieveUser(array $parameters = [], array $options = []) Retrieves a user by their identifier.
 * @method ResponseInterface|null deleteUser(array $parameters = [], array $options = []) Deletes a user from the organization.
 *
 * @method ResponseInterface|null listProjects(array $parameters = [], array $options = []) Returns a list of projects.
 * @method ResponseInterface|null createProject(array $parameters = [], array $options = []) Creates a new project in the organization.
 * @method ResponseInterface|null retrieveProject(array $parameters = [], array $options = []) Retrieves a project.
 * @method ResponseInterface|null modifyProject(array $parameters = [], array $options = []) Modifies a project in the organization.
 * @method ResponseInterface|null archiveProject(array $parameters = [], array $options = []) Archives a project in the organization.
 *
 * @method ResponseInterface|null listProjectUsers(array $parameters = [], array $options = []) Returns a list of users in the project.
 * @method ResponseInterface|null createProjectUser(array $parameters = [], array $options = []) Adds a user to the project.
 * @method ResponseInterface|null retrieveProjectUser(array $parameters = [], array $options = []) Retrieves a user in the project.
 * @method ResponseInterface|null modifyProjectUser(array $parameters = [], array $options = []) Modifies a user's role in the project.
 * @method ResponseInterface|null deleteProjectUser(array $parameters = [], array $options = []) Deletes a user from the project.
 *
 * @method ResponseInterface|null listProjectServiceAccounts(array $parameters = [], array $options = []) Returns a list of service accounts in the project.
 * @method ResponseInterface|null createProjectServiceAccount(array $parameters = [], array $options = []) Creates a new service account in the project.
 * @method ResponseInterface|null retrieveProjectServiceAccount(array $parameters = [], array $options = []) Retrieves a service account in the project.
 * @method ResponseInterface|null deleteProjectServiceAccount(array $parameters = [], array $options = []) Deletes a service account from the project.
 *
 * @method ResponseInterface|null listProjectApiKeys(array $parameters = [], array $options = []) Returns a list of API keys in the project.
 * @method ResponseInterface|null retrieveProjectApiKey(array $parameters = [], array $options = []) Retrieves an API key in the project.
 * @method ResponseInterface|null deleteProjectApiKey(array $parameters = [], array $options = []) Deletes an API key from the project.
 *
 * @method ResponseInterface|null listProjectRateLimits(array $parameters = [], array $options = []) Returns a list of rate limits in the project.
 * @method ResponseInterface|null modifyProjectRateLimit(array $parameters = [], array $options = []) Modifies a rate limit in the project.
 *
 * @method ResponseInterface|null listAuditLogs(array $parameters = [], array $options = []) Lists user actions and configuration changes within this organization.
 *
 * @method ResponseInterface|null getCompletionsUsage(array $parameters = [], array $options = []) Retrieves usage metrics for completions.
 * @method ResponseInterface|null getEmbeddingsUsage(array $parameters = [], array $options = []) Retrieves usage metrics for embeddings.
 * @method ResponseInterface|null getModerationsUsage(array $parameters = [], array $options = []) Retrieves usage metrics for moderations.
 * @method ResponseInterface|null getImagesUsage(array $parameters = [], array $options = []) Retrieves usage metrics for images.
 * @method ResponseInterface|null getAudioSpeechesUsage(array $parameters = [], array $options = []) Retrieves usage metrics for audio speeches.
 * @method ResponseInterface|null getVectorStoresUsage(array $parameters = [], array $options = []) Retrieves usage metrics for vector stores.
 * @method ResponseInterface|null getCosts(array $parameters = [], array $options = []) Retrieves cost data for the organization.
 *
 * @method ResponseInterface|null uploadCertificate(array $parameters = [], array $options = []) Uploads a certificate.
 * @method ResponseInterface|null getCertificate(array $parameters = [], array $options = []) Retrieves a certificate.
 * @method ResponseInterface|null modifyCertificate(array $parameters = [], array $options = []) Modifies a certificate.
 * @method ResponseInterface|null deleteCertificate(array $parameters = [], array $options = []) Deletes a certificate.
 * @method ResponseInterface|null listCertificates(array $parameters = [], array $options = []) Returns a list of certificates.
 * @method ResponseInterface|null listProjectCertificates(array $parameters = [], array $options = []) Returns a list of certificates in a project.
 * @method ResponseInterface|null activateCertificates(array $parameters = [], array $options = []) Activates certificates.
 * @method ResponseInterface|null deactivateCertificates(array $parameters = [], array $options = []) Deactivates certificates.
 * @method ResponseInterface|null activateProjectCertificates(array $parameters = [], array $options = []) Activates certificates for a project.
 * @method ResponseInterface|null deactivateProjectCertificates(array $parameters = [], array $options = []) Deactivates certificates for a project.
 */
class OpenAI
{
    /**
     * Constructs a new instance of the OpenAI client.
     *
     * @param RequestFactoryInterface $requestFactory The PSR-17 request factory.
     * @param StreamFactoryInterface  $streamFactory  The PSR-17 stream factory.
     * @param UriFactoryInterface     $uriFactory     The PSR-17 URI factory.
     * @param ClientInterface         $httpClient     The PSR-18 HTTP client.
     * @param string                  $apiKey         Your OpenAI API key.
     * @param string                  $origin         Custom API origin (hostname).
     * @param string                  $basePath       Custom base path.
     */
    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly UriFactoryInterface $uriFactory,
        private readonly ClientInterface $httpClient,
        #[SensitiveParameter]
        private readonly string $apiKey,
        private readonly string $organization = '',
        private readonly string $origin = '',
        private readonly string $basePath = ''
    ) {}

    /**
     * Magic method to call the OpenAI API endpoints.
     *
     * @param string $key The endpoint method.
     * @param array $args The arguments for the endpoint method.
     *
     * @return ResponseInterface|null The API response or null if streaming.
     *
     * @throws OpenAIException       If the API returns an error.
     * @throws InvalidArgumentException If the parameters are invalid.
     * @throws RandomException
     */
    public function __call(string $key, array $args): ?ResponseInterface
    {
        $endpoint = OpenAIURLBuilder::getEndpoint($key);
        $httpMethod = $endpoint['method'];

        [$parameters, $opts, $streamCallback] = $this->extractCallArguments($args);

        return $this->callAPI($httpMethod, $key, $parameters, $opts, $streamCallback);
    }

    /**
     * Extracts the arguments from the input array.
     *
     * @param array $args The input arguments.
     *
     * @return array An array containing the extracted parameters, options, and stream callback.
     *
     * @throws InvalidArgumentException If the first argument is not an array.
     */
    private function extractCallArguments(array $args): array
    {
        $parameters = [];
        $opts = [];
        $streamCallback = null;

        if (!isset($args[0])) {
            return [$parameters, $opts, $streamCallback];
        }

        if (\is_array($args[0])) {
            $parameters = $args[0];

            if (isset($args[1]) && \is_array($args[1])) {
                $opts = $args[1];

                if (isset($args[2]) && \is_callable($args[2])) {
                    $streamCallback = $args[2];
                }
            } elseif (isset($args[1]) && \is_callable($args[1])) {
                $streamCallback = $args[1];
            }
        } else {
            throw new InvalidArgumentException('First argument must be an array of parameters.');
        }

        return [$parameters, $opts, $streamCallback];
    }

    /**
     * Calls the OpenAI API with the provided method, key, parameters, and options.
     *
     * Splits out path parameters (used to fill {placeholders})
     * from query parameters, and for GET requests builds the
     * query string instead of sending a body.
     *
     * @param string               $method         The HTTP method for the request.
     * @param string               $key            The API endpoint key.
     * @param array<string,mixed>  $parameters     Parameters for URL placeholders and/or query.
     * @param array<string,mixed>  $opts           The options for the request body (POST/PUT/etc).
     * @param callable|null        $streamCallback Callback function to handle streaming data.
     *
     * @return ResponseInterface|null The API response or null if streaming.
     *
     * @throws OpenAIException If the API returns an error.
     * @throws RandomException
     */
    private function callAPI(
        string $method,
        string $key,
        array $parameters = [],
        array $opts = [],
        ?callable $streamCallback = null
    ): ?ResponseInterface {
        // Figure out which parameters are path placeholders
        $endpoint = OpenAIURLBuilder::getEndpoint($key);
        $pathTemplate = $endpoint['path'];
        \preg_match_all('/\{(\w+)}/', $pathTemplate, $matches);
        $placeholders = $matches[1];

        // Split $parameters into path vs query
        $pathParams = \array_intersect_key($parameters, \array_flip($placeholders));
        $queryParams = \array_diff_key($parameters, \array_flip($placeholders));

        // Build the URI using only the path parameters
        $uri = OpenAIURLBuilder::createUrl(
            $this->uriFactory,
            $key,
            $pathParams,
            $this->origin,
            $this->basePath
        );

        // If this is a GET, merge any remaining parameters or opts into the query string,
        // and clear $opts so no body is sent
        if ($method === 'GET') {
            $allQueries = $queryParams + $opts;

            if (!empty($allQueries)) {
                $uri = $uri->withQuery(\http_build_query($allQueries));
            }

            $opts = [];
        }

        // Extract any custom headers, then dispatch
        $customHeaders = $opts['customHeaders'] ?? [];
        unset($opts['customHeaders']);

        return $this->sendRequest($uri, $method, $opts, $streamCallback, $customHeaders);
    }

    /**
     * Sends an HTTP request to the OpenAI API and returns the response.
     *
     * @param UriInterface $uri The URI to send the request to.
     * @param string $method The HTTP method to use.
     * @param array $params Parameters to include in the request body.
     * @param callable|null $streamCallback Callback function to handle streaming data.
     *
     * @return ResponseInterface|null The response from the OpenAI API or null if streaming.
     *
     * @throws OpenAIException If the API returns an error.
     * @throws RandomException
     */
    private function sendRequest(
        UriInterface $uri,
        string $method,
        array $params = [],
        ?callable $streamCallback = null,
        array $customHeaders = []
    ): ?ResponseInterface {
        $request = $this->requestFactory->createRequest($method, $uri);
        $isMultipart = $this->isMultipartRequest($params);
        $boundary = $isMultipart ? $this->generateMultipartBoundary() : null;
        $headers = $this->createHeaders($isMultipart, $boundary, $customHeaders);
        $request = $this->applyHeaders($request, $headers);

        $body = $isMultipart
            ? $this->createMultipartStream($params, $boundary)
            : $this->createJsonBody($params);

        if ($body !== '') {
            $request = $request->withBody($this->streamFactory->createStream($body));
        }

        try {
            if ($streamCallback !== null && ($params['stream'] ?? false) === true) {
                $this->handleStreamingResponse($request, $streamCallback);

                return null;
            }

            $response = $this->httpClient->sendRequest($request);

            if ($response->getStatusCode() >= 400) {
                throw new OpenAIException($response->getBody()->getContents(), $response->getStatusCode());
            }

            return $response;
        } catch (ClientExceptionInterface $e) {
            throw new OpenAIException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Handles a streaming response from the API.
     *
     * @param RequestInterface $request        The request to send.
     * @param callable         $streamCallback The callback function to handle streaming data.
     *
     * @return void
     *
     * @throws OpenAIException If an error occurs during streaming.
     */
    private function handleStreamingResponse(RequestInterface $request, callable $streamCallback): void
    {
        try {
            $response = $this->httpClient->sendRequest($request);
            $statusCode = $response->getStatusCode();

            if ($statusCode >= 400) {
                throw new OpenAIException($response->getBody()->getContents(), $statusCode);
            }

            $body = $response->getBody();
            $buffer = '';

            while (!$body->eof()) {
                $chunk = $body->read(8192);
                $buffer .= $chunk;

                while (($newlinePos = \strpos($buffer, "\n")) !== false) {
                    $line = \substr($buffer, 0, $newlinePos);
                    $buffer = \substr($buffer, $newlinePos + 1);

                    $data = \trim($line);

                    if ($data === '') {
                        continue;
                    }

                    if ($data === 'data: [DONE]') {
                        return;
                    }

                    if (\str_starts_with($data, 'data: ')) {
                        $json = \substr($data, 6);

                        try {
                            $decoded = \json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                            $streamCallback($decoded);
                        } catch (JsonException $e) {
                            throw new OpenAIException('JSON decode error: ' . $e->getMessage(), 0, $e);
                        }
                    }
                }
            }
        } catch (ClientExceptionInterface $e) {
            throw new OpenAIException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Generates a unique multipart boundary string.
     *
     * @return string The generated multipart boundary string.
     *
     * @throws RandomException
     */
    private function generateMultipartBoundary(): string
    {
        return '----OpenAI' . \bin2hex(\random_bytes(16));
    }

    /**
     * Creates the headers for an API request.
     *
     * @param bool        $isMultipart Indicates whether the request is multipart.
     * @param string|null $boundary    The multipart boundary string, if applicable.
     *
     * @return array An associative array of headers.
     */
    private function createHeaders(bool $isMultipart, ?string $boundary, array $customHeaders = []): array
    {
        $defaultHeaders = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'OpenAI-Organization' => $this->organization ?: '',
            'Content-Type' => $isMultipart
                ? "multipart/form-data; boundary={$boundary}"
                : 'application/json',
        ];

        // Remove OpenAI-Organization header if it's empty
        if ($defaultHeaders['OpenAI-Organization'] === '') {
            unset($defaultHeaders['OpenAI-Organization']);
        }

        // Merge custom headers, overriding defaults if necessary
        return \array_merge($defaultHeaders, $customHeaders);
    }

    /**
     * Applies the headers to the given request.
     *
     * @param RequestInterface $request The request to apply headers to.
     * @param array            $headers An associative array of headers to apply.
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
     * Creates a JSON-encoded body string from the given parameters.
     *
     * @param array $params An associative array of parameters to encode as JSON.
     *
     * @return string The JSON-encoded body string.
     *
     * @throws OpenAIException If JSON encoding fails.
     */
    private function createJsonBody(array $params): string
    {
        if (empty($params)) {
            return '';
        }

        try {
            return \json_encode($params, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new OpenAIException('JSON encode error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Creates a multipart stream for sending files in a request.
     *
     * @param array $params An associative array of parameters to send with the request.
     * @param string $boundary A string used as a boundary to separate parts of the multipart stream.
     *
     * @return string The multipart stream as a string.
     * @throws RandomException
     */
    private function createMultipartStream(array $params, string $boundary): string
    {
        $multipartStream = '';

        foreach ($params as $key => $value) {
            $multipartStream .= "--{$boundary}\r\n";
            $multipartStream .= "Content-Disposition: form-data; name=\"{$key}\"";

            if (\in_array($key, ['file', 'image', 'mask', 'data'], true)) {
                $filename = \bin2hex(\random_bytes(20)) . '.' . \mb_strtolower(\mb_substr(
                    \basename($value),
                    \mb_strrpos(\basename($value), '.') + 1
                ));
                $fileContents = \file_get_contents($value);

                if ($key === 'data') {
                    $fileContents = \base64_encode($fileContents);
                }

                $multipartStream .= "; filename=\"{$filename}\"\r\n";
                $multipartStream .= "Content-Type: application/octet-stream\r\n\r\n";
                $multipartStream .= "{$fileContents}\r\n";
            } else {
                $multipartStream .= "\r\n\r\n{$value}\r\n";
            }
        }

        $multipartStream .= "--{$boundary}--\r\n";

        return $multipartStream;
    }

    /**
     * Determines if a request is a multipart request based on the provided parameters.
     *
     * @param array $params An associative array of parameters to check.
     *
     * @return bool True if the request is a multipart request, false otherwise.
     */
    private function isMultipartRequest(array $params): bool
    {
        $isMultipartRequest = \array_intersect_key(\array_flip(['file', 'image', 'mask', 'data']), $params) !== [];

        if ($isMultipartRequest) {
            foreach ($params as $param) {
                if (\is_array($param)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
