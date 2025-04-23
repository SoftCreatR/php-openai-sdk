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
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

/**
 * Utility class for creating URLs for OpenAI API endpoints.
 */
class OpenAIURLBuilder
{
    public const ORIGIN = 'api.openai.com';

    public const BASE_PATH = '/v1';

    private const HTTP_METHOD_POST = 'POST';

    private const HTTP_METHOD_GET = 'GET';

    private const HTTP_METHOD_DELETE = 'DELETE';

    /**
     * Configuration of OpenAI API endpoints.
     *
     * @var array<string, array{method: string, path: string}>
     */
    private static array $urlEndpoints = [
        // Audio
        'createSpeech' => ['method' => self::HTTP_METHOD_POST, 'path' => '/audio/speech'],
        'createTranscription' => ['method' => self::HTTP_METHOD_POST, 'path' => '/audio/transcriptions'],
        'createTranslation' => ['method' => self::HTTP_METHOD_POST, 'path' => '/audio/translations'],

        // Responses
        'createResponse' => ['method' => self::HTTP_METHOD_POST, 'path' => '/responses'],
        'getResponse' => ['method' => self::HTTP_METHOD_GET, 'path' => '/responses/{response_id}'],
        'deleteResponse' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/responses/{response_id}'],
        'listInputItems' => ['method' => self::HTTP_METHOD_GET, 'path' => '/responses/{response_id}/input_items'],

        // Chat Completions
        'createChatCompletion' => ['method' => self::HTTP_METHOD_POST, 'path' => '/chat/completions'],
        'getChatCompletion' => ['method' => self::HTTP_METHOD_GET, 'path' => '/chat/completions/{completion_id}'],
        'getChatMessages' => ['method' => self::HTTP_METHOD_GET, 'path' => '/chat/completions/{completion_id}/messages'],
        'listChatCompletions' => ['method' => self::HTTP_METHOD_GET, 'path' => '/chat/completions'],
        'updateChatCompletion' => ['method' => self::HTTP_METHOD_POST, 'path' => '/chat/completions/{completion_id}'],
        'deleteChatCompletion' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/chat/completions/{completion_id}'],

        // Embeddings
        'createEmbedding' => ['method' => self::HTTP_METHOD_POST, 'path' => '/embeddings'],

        // Fine-Tuning Jobs
        'createFineTuningJob' => ['method' => self::HTTP_METHOD_POST, 'path' => '/fine_tuning/jobs'],
        'listFineTuningJobs' => ['method' => self::HTTP_METHOD_GET, 'path' => '/fine_tuning/jobs'],
        'listFineTuningEvents' => ['method' => self::HTTP_METHOD_GET, 'path' => '/fine_tuning/jobs/{fine_tuning_job_id}/events'],
        'listFineTuningCheckpoints' => ['method' => self::HTTP_METHOD_GET,  'path' => '/fine_tuning/jobs/{fine_tuning_job_id}/checkpoints'],
        'retrieveFineTuningJob' => ['method' => self::HTTP_METHOD_GET, 'path' => '/fine_tuning/jobs/{fine_tuning_job_id}'],
        'cancelFineTuning' => ['method' => self::HTTP_METHOD_POST, 'path' => '/fine_tuning/jobs/{fine_tuning_job_id}/cancel'],

        // Batches
        'createBatch' => ['method' => self::HTTP_METHOD_POST, 'path' => '/batches'],
        'retrieveBatch' => ['method' => self::HTTP_METHOD_GET, 'path' => '/batches/{batch_id}'],
        'cancelBatch' => ['method' => self::HTTP_METHOD_POST, 'path' => '/batches/{batch_id}/cancel'],
        'listBatches' => ['method' => self::HTTP_METHOD_GET, 'path' => '/batches'],

        // Files
        'uploadFile' => ['method' => self::HTTP_METHOD_POST, 'path' => '/files'],
        'listFiles' => ['method' => self::HTTP_METHOD_GET, 'path' => '/files'],
        'retrieveFile' => ['method' => self::HTTP_METHOD_GET, 'path' => '/files/{file_id}'],
        'deleteFile' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/files/{file_id}'],
        'retrieveFileContent' => ['method' => self::HTTP_METHOD_GET, 'path' => '/files/{file_id}/content'],

        // Uploads
        'createUpload' => ['method' => self::HTTP_METHOD_POST, 'path' => '/uploads'],
        'addUploadPart' => ['method' => self::HTTP_METHOD_POST, 'path' => '/uploads/{upload_id}/parts'],
        'completeUpload' => ['method' => self::HTTP_METHOD_POST, 'path' => '/uploads/{upload_id}/complete'],
        'cancelUpload' => ['method' => self::HTTP_METHOD_POST, 'path' => '/uploads/{upload_id}/cancel'],

        // Images
        'createImage' => ['method' => self::HTTP_METHOD_POST, 'path' => '/images/generations'],
        'createImageEdit' => ['method' => self::HTTP_METHOD_POST, 'path' => '/images/edits'],
        'createImageVariation' => ['method' => self::HTTP_METHOD_POST, 'path' => '/images/variations'],

        // Models
        'listModels' => ['method' => self::HTTP_METHOD_GET, 'path' => '/models'],
        'retrieveModel' => ['method' => self::HTTP_METHOD_GET, 'path' => '/models/{model}'],
        'deleteModel' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/models/{model}'],

        // Moderations
        'createModeration' => ['method' => self::HTTP_METHOD_POST, 'path' => '/moderations'],

        // Assistants
        'createAssistant' => ['method' => self::HTTP_METHOD_POST, 'path' => '/assistants'],
        'listAssistants' => ['method' => self::HTTP_METHOD_GET, 'path' => '/assistants'],
        'retrieveAssistant' => ['method' => self::HTTP_METHOD_GET, 'path' => '/assistants/{assistant_id}'],
        'modifyAssistant' => ['method' => self::HTTP_METHOD_POST, 'path' => '/assistants/{assistant_id}'],
        'deleteAssistant' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/assistants/{assistant_id}'],

        // Threads
        'createThread' => ['method' => self::HTTP_METHOD_POST, 'path' => '/threads'],
        'retrieveThread' => ['method' => self::HTTP_METHOD_GET, 'path' => '/threads/{thread_id}'],
        'modifyThread' => ['method' => self::HTTP_METHOD_POST, 'path' => '/threads/{thread_id}'],
        'deleteThread' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/threads/{thread_id}'],

        // Messages
        'createMessage' => ['method' => self::HTTP_METHOD_POST, 'path' => '/threads/{thread_id}/messages'],
        'listMessages' => ['method' => self::HTTP_METHOD_GET, 'path' => '/threads/{thread_id}/messages'],
        'retrieveMessage' => ['method' => self::HTTP_METHOD_GET, 'path' => '/threads/{thread_id}/messages/{message_id}'],
        'modifyMessage' => ['method' => self::HTTP_METHOD_POST, 'path' => '/threads/{thread_id}/messages/{message_id}'],
        'deleteMessage' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/threads/{thread_id}/messages/{message_id}'],

        // Runs
        'createRun' => ['method' => self::HTTP_METHOD_POST, 'path' => '/threads/{thread_id}/runs'],
        'createThreadAndRun' => ['method' => self::HTTP_METHOD_POST, 'path' => '/threads/runs'],
        'listRuns' => ['method' => self::HTTP_METHOD_GET, 'path' => '/threads/{thread_id}/runs'],
        'retrieveRun' => ['method' => self::HTTP_METHOD_GET, 'path' => '/threads/{thread_id}/runs/{run_id}'],
        'modifyRun' => ['method' => self::HTTP_METHOD_POST, 'path' => '/threads/{thread_id}/runs/{run_id}'],
        'submitToolOutputsToRun' => ['method' => self::HTTP_METHOD_POST, 'path' => '/threads/{thread_id}/runs/{run_id}/submit_tool_outputs'],
        'cancelRun' => ['method' => self::HTTP_METHOD_POST, 'path' => '/threads/{thread_id}/runs/{run_id}/cancel'],

        // Run Steps
        'listRunSteps' => ['method' => self::HTTP_METHOD_GET, 'path' => '/threads/{thread_id}/runs/{run_id}/steps'],
        'retrieveRunStep' => ['method' => self::HTTP_METHOD_GET, 'path' => '/threads/{thread_id}/runs/{run_id}/steps/{step_id}'],

        // Vector Stores
        'createVectorStore' => ['method' => self::HTTP_METHOD_POST, 'path' => '/vector_stores'],
        'listVectorStores' => ['method' => self::HTTP_METHOD_GET, 'path' => '/vector_stores'],
        'retrieveVectorStore' => ['method' => self::HTTP_METHOD_GET, 'path' => '/vector_stores/{vector_store_id}'],
        'modifyVectorStore' => ['method' => self::HTTP_METHOD_POST, 'path' => '/vector_stores/{vector_store_id}'],
        'deleteVectorStore' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/vector_stores/{vector_store_id}'],
        'searchVectorStore' => ['method' => self::HTTP_METHOD_POST, 'path' => '/vector_stores/{vector_store_id}/search'],

        // Vector Store Files
        'createVectorStoreFile' => ['method' => self::HTTP_METHOD_POST, 'path' => '/vector_stores/{vector_store_id}/files'],
        'listVectorStoreFiles' => ['method' => self::HTTP_METHOD_GET, 'path' => '/vector_stores/{vector_store_id}/files'],
        'retrieveVectorStoreFile' => ['method' => self::HTTP_METHOD_GET, 'path' => '/vector_stores/{vector_store_id}/files/{file_id}'],
        'retrieveVectorStoreFileContent' => ['method' => self::HTTP_METHOD_GET, 'path' => '/vector_stores/{vector_store_id}/files/{file_id}/content'],
        'updateVectorStoreFileAttributes' => ['method' => self::HTTP_METHOD_POST, 'path' => '/vector_stores/{vector_store_id}/files/{file_id}'],
        'deleteVectorStoreFile' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/vector_stores/{vector_store_id}/files/{file_id}'],

        // Vector Store File Batches
        'createVectorStoreFileBatch' => ['method' => self::HTTP_METHOD_POST, 'path' => '/vector_stores/{vector_store_id}/file_batches'],
        'retrieveVectorStoreFileBatch' => ['method' => self::HTTP_METHOD_GET, 'path' => '/vector_stores/{vector_store_id}/file_batches/{batch_id}'],
        'cancelVectorStoreFileBatch' => ['method' => self::HTTP_METHOD_POST, 'path' => '/vector_stores/{vector_store_id}/file_batches/{batch_id}/cancel'],
        'listVectorStoreFilesInBatch' => ['method' => self::HTTP_METHOD_GET, 'path' => '/vector_stores/{vector_store_id}/file_batches/{batch_id}/files'],

        // Admin API Keys
        'listAdminApiKeys' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/admin_api_keys'],
        'createAdminApiKey' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/admin_api_keys'],
        'retrieveAdminApiKey' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/admin_api_keys/{key_id}'],
        'deleteAdminApiKey' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/organization/admin_api_keys/{key_id}'],

        // Organization Invites
        'listInvites' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/invites'],
        'createInvite' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/invites'],
        'retrieveInvite' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/invites/{invite_id}'],
        'deleteInvite' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/organization/invites/{invite_id}'],

        // Organization Users
        'listUsers' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/users'],
        'modifyUser' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/users/{user_id}'],
        'retrieveUser' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/users/{user_id}'],
        'deleteUser' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/organization/users/{user_id}'],

        // Organization Projects
        'listProjects' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects'],
        'createProject' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects'],
        'retrieveProject' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}'],
        'modifyProject' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects/{project_id}'],
        'archiveProject' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects/{project_id}/archive'],

        // Project Users
        'listProjectUsers' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}/users'],
        'createProjectUser' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects/{project_id}/users'],
        'retrieveProjectUser' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}/users/{user_id}'],
        'modifyProjectUser' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects/{project_id}/users/{user_id}'],
        'deleteProjectUser' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/organization/projects/{project_id}/users/{user_id}'],

        // Project Service Accounts
        'listProjectServiceAccounts' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}/service_accounts'],
        'createProjectServiceAccount' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects/{project_id}/service_accounts'],
        'retrieveProjectServiceAccount' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}/service_accounts/{service_account_id}'],
        'deleteProjectServiceAccount' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/organization/projects/{project_id}/service_accounts/{service_account_id}'],

        // Project API Keys
        'listProjectApiKeys' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}/api_keys'],
        'retrieveProjectApiKey' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}/api_keys/{key_id}'],
        'deleteProjectApiKey' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/organization/projects/{project_id}/api_keys/{key_id}'],

        // Rate Limits
        'listProjectRateLimits' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}/rate_limits'],
        'modifyProjectRateLimit' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}/rate_limits/{rate_limit_id}'],

        // Audit Logs
        'listAuditLogs' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/audit_logs'],

        // Usage
        'getCompletionsUsage' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/usage/completions'],
        'getEmbeddingsUsage' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/usage/embeddings'],
        'getModerationsUsage' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/usage/moderations'],
        'getImagesUsage' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/usage/images'],
        'getAudioSpeechesUsage' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/usage/audio_speeches'],
        'getVectorStoresUsage' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/usage/vector_stores'],
        'getCosts' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/costs'],

        // Certificates
        'uploadCertificate' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/certificates'],
        'getCertificate' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/certificates/{cert_id}'],
        'modifyCertificate' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/certificates/{certificate_id}'],
        'deleteCertificate' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/organization/certificates/{certificate_id}'],
        'listCertificates' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/certificates'],
        'listProjectCertificates' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}/certificates'],
        'activateCertificates' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/certificates/activate'],
        'deactivateCertificates' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/certificates/deactivate'],
        'activateProjectCertificates' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects/{project_id}/certificates/activate'],
        'deactivateProjectCertificates' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects/{project_id}/certificates/deactivate'],
    ];

    /**
     * Prevents instantiation of this class.
     */
    protected function __construct()
    {
        // This class should not be instantiated.
    }

    /**
     * Gets the OpenAI API endpoint configuration.
     *
     * @param string $key The endpoint key.
     *
     * @return array{method: string, path: string} The endpoint configuration.
     *
     * @throws InvalidArgumentException If the provided key is invalid.
     */
    public static function getEndpoint(string $key): array
    {
        if (!isset(self::$urlEndpoints[$key])) {
            throw new InvalidArgumentException(\sprintf('Invalid OpenAI URL key "%s".', $key));
        }

        return self::$urlEndpoints[$key];
    }

    /**
     * Creates a URL for the specified OpenAI API endpoint.
     *
     * @param UriFactoryInterface  $uriFactory The PSR-17 URI factory instance used for creating URIs.
     * @param string               $key        The key representing the API endpoint.
     * @param array<string, mixed> $parameters Optional parameters to replace in the endpoint path.
     * @param string               $origin     Custom origin (hostname), if needed.
     * @param string               $basePath   Custom base path, if needed.
     *
     * @return UriInterface The fully constructed URL for the API endpoint.
     *
     * @throws InvalidArgumentException If a required path parameter is missing or invalid.
     */
    public static function createUrl(
        UriFactoryInterface $uriFactory,
        string $key,
        array $parameters = [],
        string $origin = '',
        string $basePath = ''
    ): UriInterface {
        $endpoint = self::getEndpoint($key);
        $path = self::replacePathParameters($endpoint['path'], $parameters);

        return $uriFactory
            ->createUri()
            ->withScheme('https')
            ->withHost($origin !== '' ? $origin : self::ORIGIN)
            ->withPath(\trim($basePath !== '' ? $basePath : self::BASE_PATH, '/') . $path);
    }

    /**
     * Replaces path parameters in the given path with provided parameter values.
     *
     * @param string              $path       The path containing parameter placeholders.
     * @param array<string, mixed> $parameters The parameter values to replace placeholders in the path.
     *
     * @return string The path with replaced parameter values.
     *
     * @throws InvalidArgumentException If a required path parameter is missing or invalid.
     */
    private static function replacePathParameters(string $path, array $parameters): string
    {
        return \preg_replace_callback('/\{(\w+)}/', static function ($matches) use ($parameters) {
            $key = $matches[1];

            if (!\array_key_exists($key, $parameters)) {
                throw new InvalidArgumentException(\sprintf('Missing path parameter "%s".', $key));
            }

            $value = $parameters[$key];

            if (!\is_scalar($value)) {
                throw new InvalidArgumentException(\sprintf(
                    'Parameter "%s" must be a scalar value, %s given.',
                    $key,
                    \gettype($value)
                ));
            }

            return (string)$value;
        }, $path);
    }
}
