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
     * @var array<string, array{
     *     method: string,
     *     path: string,
     *     category: string,
     *     body: 'none'|'json'|'multipart',
     *     fileFields?: list<string>,
     *     headers?: array<string, string|string[]>,
     *     query?: array<string, bool|float|int|string>,
     *     streaming?: bool,
     *     admin?: bool,
     *     deprecated?: bool
     * }>
     */
    private static array $urlEndpoints = [
        // Chat Completions
        'createChatCompletion' => ['method' => self::HTTP_METHOD_POST, 'path' => '/chat/completions', 'category' => 'chat', 'body' => 'json'],
        'listChatCompletions' => ['method' => self::HTTP_METHOD_GET, 'path' => '/chat/completions', 'category' => 'chat', 'body' => 'none'],
        'getChatCompletion' => ['method' => self::HTTP_METHOD_GET, 'path' => '/chat/completions/{completion_id}', 'category' => 'chat', 'body' => 'none'],
        'updateChatCompletion' => ['method' => self::HTTP_METHOD_POST, 'path' => '/chat/completions/{completion_id}', 'category' => 'chat', 'body' => 'json'],
        'deleteChatCompletion' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/chat/completions/{completion_id}', 'category' => 'chat', 'body' => 'none'],
        'getChatMessages' => ['method' => self::HTTP_METHOD_GET, 'path' => '/chat/completions/{completion_id}/messages', 'category' => 'chat', 'body' => 'none'],

        // Embeddings
        'createEmbedding' => ['method' => self::HTTP_METHOD_POST, 'path' => '/embeddings', 'category' => 'embeddings', 'body' => 'json'],

        // Files
        'listFiles' => ['method' => self::HTTP_METHOD_GET, 'path' => '/files', 'category' => 'files', 'body' => 'none'],
        'uploadFile' => ['method' => self::HTTP_METHOD_POST, 'path' => '/files', 'category' => 'files', 'body' => 'multipart', 'fileFields' => ['file']],
        'deleteFile' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/files/{file_id}', 'category' => 'files', 'body' => 'none'],
        'retrieveFile' => ['method' => self::HTTP_METHOD_GET, 'path' => '/files/{file_id}', 'category' => 'files', 'body' => 'none'],
        'retrieveFileContent' => ['method' => self::HTTP_METHOD_GET, 'path' => '/files/{file_id}/content', 'category' => 'files', 'body' => 'none'],

        // Images
        'createImage' => ['method' => self::HTTP_METHOD_POST, 'path' => '/images/generations', 'category' => 'images', 'body' => 'json'],
        'createImageEdit' => ['method' => self::HTTP_METHOD_POST, 'path' => '/images/edits', 'category' => 'images', 'body' => 'multipart', 'fileFields' => ['image', 'mask']],

        // Videos
        'createVideo' => ['method' => self::HTTP_METHOD_POST, 'path' => '/videos', 'category' => 'videos', 'body' => 'json'],
        'createVideoCharacter' => ['method' => self::HTTP_METHOD_POST, 'path' => '/videos/characters', 'category' => 'videos', 'body' => 'multipart', 'fileFields' => ['video']],
        'retrieveVideoCharacter' => ['method' => self::HTTP_METHOD_GET, 'path' => '/videos/characters/{character_id}', 'category' => 'videos', 'body' => 'none'],
        'listVideos' => ['method' => self::HTTP_METHOD_GET, 'path' => '/videos', 'category' => 'videos', 'body' => 'none'],
        'retrieveVideo' => ['method' => self::HTTP_METHOD_GET, 'path' => '/videos/{video_id}', 'category' => 'videos', 'body' => 'none'],
        'deleteVideo' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/videos/{video_id}', 'category' => 'videos', 'body' => 'none'],
        'downloadVideoContent' => ['method' => self::HTTP_METHOD_GET, 'path' => '/videos/{video_id}/content', 'category' => 'videos', 'body' => 'none'],
        'createVideoEdit' => ['method' => self::HTTP_METHOD_POST, 'path' => '/videos/edits', 'category' => 'videos', 'body' => 'json'],
        'createVideoExtension' => ['method' => self::HTTP_METHOD_POST, 'path' => '/videos/extensions', 'category' => 'videos', 'body' => 'json'],
        'createVideoRemix' => ['method' => self::HTTP_METHOD_POST, 'path' => '/videos/{video_id}/remix', 'category' => 'videos', 'body' => 'json'],

        // Audio
        'createTranscription' => ['method' => self::HTTP_METHOD_POST, 'path' => '/audio/transcriptions', 'category' => 'audio', 'body' => 'multipart', 'fileFields' => ['file']],
        'createTranslation' => ['method' => self::HTTP_METHOD_POST, 'path' => '/audio/translations', 'category' => 'audio', 'body' => 'multipart', 'fileFields' => ['file']],
        'createSpeech' => ['method' => self::HTTP_METHOD_POST, 'path' => '/audio/speech', 'category' => 'audio', 'body' => 'json'],
        'createVoice' => ['method' => self::HTTP_METHOD_POST, 'path' => '/audio/voices', 'category' => 'audio', 'body' => 'multipart', 'fileFields' => ['audio_sample']],
        'listVoiceConsents' => ['method' => self::HTTP_METHOD_GET, 'path' => '/audio/voice_consents', 'category' => 'audio', 'body' => 'none'],
        'createVoiceConsent' => ['method' => self::HTTP_METHOD_POST, 'path' => '/audio/voice_consents', 'category' => 'audio', 'body' => 'multipart', 'fileFields' => ['recording']],
        'retrieveVoiceConsent' => ['method' => self::HTTP_METHOD_GET, 'path' => '/audio/voice_consents/{consent_id}', 'category' => 'audio', 'body' => 'none'],
        'updateVoiceConsent' => ['method' => self::HTTP_METHOD_POST, 'path' => '/audio/voice_consents/{consent_id}', 'category' => 'audio', 'body' => 'json'],
        'deleteVoiceConsent' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/audio/voice_consents/{consent_id}', 'category' => 'audio', 'body' => 'none'],

        // Moderations
        'createModeration' => ['method' => self::HTTP_METHOD_POST, 'path' => '/moderations', 'category' => 'moderations', 'body' => 'json'],

        // Content Provenance and Safety
        'createContentProvenanceCheck' => ['method' => self::HTTP_METHOD_POST, 'path' => '/content_provenance_checks', 'category' => 'content_provenance', 'body' => 'multipart', 'fileFields' => ['file']],
        'retrieveSafetyAlert' => ['method' => self::HTTP_METHOD_GET, 'path' => '/safety/alerts/{id}', 'category' => 'safety', 'body' => 'none'],

        // Models
        'listModels' => ['method' => self::HTTP_METHOD_GET, 'path' => '/models', 'category' => 'models', 'body' => 'none'],
        'retrieveModel' => ['method' => self::HTTP_METHOD_GET, 'path' => '/models/{model}', 'category' => 'models', 'body' => 'none'],
        'deleteModel' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/models/{model}', 'category' => 'models', 'body' => 'none'],

        // Fine-Tuning Jobs
        'createFineTuningJob' => ['method' => self::HTTP_METHOD_POST, 'path' => '/fine_tuning/jobs', 'category' => 'fine_tuning', 'body' => 'json', 'deprecated' => true],
        'listFineTuningJobs' => ['method' => self::HTTP_METHOD_GET, 'path' => '/fine_tuning/jobs', 'category' => 'fine_tuning', 'body' => 'none', 'deprecated' => true],
        'retrieveFineTuningJob' => ['method' => self::HTTP_METHOD_GET, 'path' => '/fine_tuning/jobs/{fine_tuning_job_id}', 'category' => 'fine_tuning', 'body' => 'none', 'deprecated' => true],
        'listFineTuningEvents' => ['method' => self::HTTP_METHOD_GET, 'path' => '/fine_tuning/jobs/{fine_tuning_job_id}/events', 'category' => 'fine_tuning', 'body' => 'none', 'deprecated' => true],
        'cancelFineTuning' => ['method' => self::HTTP_METHOD_POST, 'path' => '/fine_tuning/jobs/{fine_tuning_job_id}/cancel', 'category' => 'fine_tuning', 'body' => 'none', 'deprecated' => true],
        'pauseFineTuning' => ['method' => self::HTTP_METHOD_POST, 'path' => '/fine_tuning/jobs/{fine_tuning_job_id}/pause', 'category' => 'fine_tuning', 'body' => 'none', 'deprecated' => true],
        'resumeFineTuning' => ['method' => self::HTTP_METHOD_POST, 'path' => '/fine_tuning/jobs/{fine_tuning_job_id}/resume', 'category' => 'fine_tuning', 'body' => 'none', 'deprecated' => true],
        'listFineTuningCheckpoints' => ['method' => self::HTTP_METHOD_GET, 'path' => '/fine_tuning/jobs/{fine_tuning_job_id}/checkpoints', 'category' => 'fine_tuning', 'body' => 'none', 'deprecated' => true],
        'listFineTuningCheckpointPermissions' => ['method' => self::HTTP_METHOD_GET, 'path' => '/fine_tuning/checkpoints/{fine_tuned_model_checkpoint}/permissions', 'category' => 'fine_tuning', 'body' => 'none', 'deprecated' => true],
        'createFineTuningCheckpointPermission' => ['method' => self::HTTP_METHOD_POST, 'path' => '/fine_tuning/checkpoints/{fine_tuned_model_checkpoint}/permissions', 'category' => 'fine_tuning', 'body' => 'json', 'deprecated' => true],
        'deleteFineTuningCheckpointPermission' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/fine_tuning/checkpoints/{fine_tuned_model_checkpoint}/permissions/{permission_id}', 'category' => 'fine_tuning', 'body' => 'none', 'deprecated' => true],
        'runFineTuningGrader' => ['method' => self::HTTP_METHOD_POST, 'path' => '/fine_tuning/alpha/graders/run', 'category' => 'fine_tuning', 'body' => 'json', 'deprecated' => true],
        'validateFineTuningGrader' => ['method' => self::HTTP_METHOD_POST, 'path' => '/fine_tuning/alpha/graders/validate', 'category' => 'fine_tuning', 'body' => 'json', 'deprecated' => true],

        // Evals
        'createEval' => ['method' => self::HTTP_METHOD_POST, 'path' => '/evals', 'category' => 'evals', 'body' => 'json'],
        'listEvals' => ['method' => self::HTTP_METHOD_GET, 'path' => '/evals', 'category' => 'evals', 'body' => 'none'],
        'retrieveEval' => ['method' => self::HTTP_METHOD_GET, 'path' => '/evals/{eval_id}', 'category' => 'evals', 'body' => 'none'],
        'updateEval' => ['method' => self::HTTP_METHOD_POST, 'path' => '/evals/{eval_id}', 'category' => 'evals', 'body' => 'json'],
        'deleteEval' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/evals/{eval_id}', 'category' => 'evals', 'body' => 'none'],
        'createEvalRun' => ['method' => self::HTTP_METHOD_POST, 'path' => '/evals/{eval_id}/runs', 'category' => 'evals', 'body' => 'json'],
        'listEvalRuns' => ['method' => self::HTTP_METHOD_GET, 'path' => '/evals/{eval_id}/runs', 'category' => 'evals', 'body' => 'none'],
        'retrieveEvalRun' => ['method' => self::HTTP_METHOD_GET, 'path' => '/evals/{eval_id}/runs/{run_id}', 'category' => 'evals', 'body' => 'none'],
        'cancelEvalRun' => ['method' => self::HTTP_METHOD_POST, 'path' => '/evals/{eval_id}/runs/{run_id}', 'category' => 'evals', 'body' => 'none'],
        'deleteEvalRun' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/evals/{eval_id}/runs/{run_id}', 'category' => 'evals', 'body' => 'none'],
        'listEvalRunOutputItems' => ['method' => self::HTTP_METHOD_GET, 'path' => '/evals/{eval_id}/runs/{run_id}/output_items', 'category' => 'evals', 'body' => 'none'],
        'retrieveEvalRunOutputItem' => ['method' => self::HTTP_METHOD_GET, 'path' => '/evals/{eval_id}/runs/{run_id}/output_items/{output_item_id}', 'category' => 'evals', 'body' => 'none'],

        // Vector Stores
        'listVectorStores' => ['method' => self::HTTP_METHOD_GET, 'path' => '/vector_stores', 'category' => 'vector_stores', 'body' => 'none'],
        'createVectorStore' => ['method' => self::HTTP_METHOD_POST, 'path' => '/vector_stores', 'category' => 'vector_stores', 'body' => 'json'],
        'retrieveVectorStore' => ['method' => self::HTTP_METHOD_GET, 'path' => '/vector_stores/{vector_store_id}', 'category' => 'vector_stores', 'body' => 'none'],
        'modifyVectorStore' => ['method' => self::HTTP_METHOD_POST, 'path' => '/vector_stores/{vector_store_id}', 'category' => 'vector_stores', 'body' => 'json'],
        'deleteVectorStore' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/vector_stores/{vector_store_id}', 'category' => 'vector_stores', 'body' => 'none'],
        'searchVectorStore' => ['method' => self::HTTP_METHOD_POST, 'path' => '/vector_stores/{vector_store_id}/search', 'category' => 'vector_stores', 'body' => 'json'],
        'listVectorStoreFiles' => ['method' => self::HTTP_METHOD_GET, 'path' => '/vector_stores/{vector_store_id}/files', 'category' => 'vector_stores', 'body' => 'none'],
        'createVectorStoreFile' => ['method' => self::HTTP_METHOD_POST, 'path' => '/vector_stores/{vector_store_id}/files', 'category' => 'vector_stores', 'body' => 'json'],
        'updateVectorStoreFileAttributes' => ['method' => self::HTTP_METHOD_POST, 'path' => '/vector_stores/{vector_store_id}/files/{file_id}', 'category' => 'vector_stores', 'body' => 'json'],
        'retrieveVectorStoreFile' => ['method' => self::HTTP_METHOD_GET, 'path' => '/vector_stores/{vector_store_id}/files/{file_id}', 'category' => 'vector_stores', 'body' => 'none'],
        'deleteVectorStoreFile' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/vector_stores/{vector_store_id}/files/{file_id}', 'category' => 'vector_stores', 'body' => 'none'],
        'retrieveVectorStoreFileContent' => ['method' => self::HTTP_METHOD_GET, 'path' => '/vector_stores/{vector_store_id}/files/{file_id}/content', 'category' => 'vector_stores', 'body' => 'none'],
        'createVectorStoreFileBatch' => ['method' => self::HTTP_METHOD_POST, 'path' => '/vector_stores/{vector_store_id}/file_batches', 'category' => 'vector_stores', 'body' => 'json'],
        'retrieveVectorStoreFileBatch' => ['method' => self::HTTP_METHOD_GET, 'path' => '/vector_stores/{vector_store_id}/file_batches/{batch_id}', 'category' => 'vector_stores', 'body' => 'none'],
        'cancelVectorStoreFileBatch' => ['method' => self::HTTP_METHOD_POST, 'path' => '/vector_stores/{vector_store_id}/file_batches/{batch_id}/cancel', 'category' => 'vector_stores', 'body' => 'none'],
        'listVectorStoreFilesInBatch' => ['method' => self::HTTP_METHOD_GET, 'path' => '/vector_stores/{vector_store_id}/file_batches/{batch_id}/files', 'category' => 'vector_stores', 'body' => 'none'],

        // Agents (beta)
        'createAgent' => ['method' => self::HTTP_METHOD_POST, 'path' => '/agents', 'category' => 'agents', 'body' => 'json', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'listAgents' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'retrieveAgent' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/{agent_id}', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'updateAgent' => ['method' => self::HTTP_METHOD_POST, 'path' => '/agents/{agent_id}', 'category' => 'agents', 'body' => 'json', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'deleteAgent' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/agents/{agent_id}', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'retrieveAgentEnvironment' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/environments/{environment_id}', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'createAgentEnvironmentFile' => ['method' => self::HTTP_METHOD_POST, 'path' => '/agents/environments/{environment_id}/files', 'category' => 'agents', 'body' => 'json', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'listAgentEnvironmentFiles' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/environments/{environment_id}/files', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'createAgentEnvironmentTemplate' => ['method' => self::HTTP_METHOD_POST, 'path' => '/agents/environments/templates', 'category' => 'agents', 'body' => 'json', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'listAgentEnvironmentTemplates' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/environments/templates', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'retrieveAgentEnvironmentTemplate' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/environments/templates/{environment_template_id}', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'updateAgentEnvironmentTemplate' => ['method' => self::HTTP_METHOD_POST, 'path' => '/agents/environments/templates/{environment_template_id}', 'category' => 'agents', 'body' => 'json', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'deleteAgentEnvironmentTemplate' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/agents/environments/templates/{environment_template_id}', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'createAgentSession' => ['method' => self::HTTP_METHOD_POST, 'path' => '/agents/sessions', 'category' => 'agents', 'body' => 'json', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'listAgentSessions' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/sessions', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'retrieveAgentSession' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/sessions/{session_id}', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'updateAgentSession' => ['method' => self::HTTP_METHOD_POST, 'path' => '/agents/sessions/{session_id}', 'category' => 'agents', 'body' => 'json', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'deleteAgentSession' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/agents/sessions/{session_id}', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'createAgentSessionEvents' => ['method' => self::HTTP_METHOD_POST, 'path' => '/agents/sessions/{session_id}/events', 'category' => 'agents', 'body' => 'json', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'streamAgentSessionEvents' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/sessions/{session_id}/events', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1'], 'streaming' => true],
        'listAgentSessionItems' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/sessions/{session_id}/items', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'listAgentSessionTurns' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/sessions/{session_id}/turns', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'retrieveAgentSessionTurn' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/sessions/{session_id}/turns/{turn_id}', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'listAgentSessionArtifacts' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/sessions/{session_id}/artifacts', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'retrieveAgentSessionArtifact' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/sessions/{session_id}/artifacts/{artifact_id}', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'deleteAgentSessionArtifact' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/agents/sessions/{session_id}/artifacts/{artifact_id}', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'retrieveAgentSessionArtifactContent' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/sessions/{session_id}/artifacts/{artifact_id}/content', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'listAgentSessionSubagents' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/sessions/{session_id}/subagents', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'retrieveAgentSessionSubagent' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/sessions/{session_id}/subagents/{subagent_id}', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'listAgentSessionSubagentItems' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/sessions/{session_id}/subagents/{subagent_id}/items', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'listAgentSessionSubagentTurns' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/sessions/{session_id}/subagents/{subagent_id}/turns', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'retrieveAgentSessionSubagentTurn' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/sessions/{session_id}/subagents/{subagent_id}/turns/{turn_id}', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'listAgentSessionSubagentTurnItems' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/sessions/{session_id}/subagents/{subagent_id}/turns/{turn_id}/items', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'createVault' => ['method' => self::HTTP_METHOD_POST, 'path' => '/vaults', 'category' => 'agents', 'body' => 'json', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'listVaults' => ['method' => self::HTTP_METHOD_GET, 'path' => '/vaults', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'retrieveVault' => ['method' => self::HTTP_METHOD_GET, 'path' => '/vaults/{vault_id}', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'deleteVault' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/vaults/{vault_id}', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'createVaultCredential' => ['method' => self::HTTP_METHOD_POST, 'path' => '/vaults/{vault_id}/credentials', 'category' => 'agents', 'body' => 'json', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'listVaultCredentials' => ['method' => self::HTTP_METHOD_GET, 'path' => '/vaults/{vault_id}/credentials', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'retrieveVaultCredential' => ['method' => self::HTTP_METHOD_GET, 'path' => '/vaults/{vault_id}/credentials/{credential_id}', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'rotateVaultCredential' => ['method' => self::HTTP_METHOD_POST, 'path' => '/vaults/{vault_id}/credentials/{credential_id}', 'category' => 'agents', 'body' => 'json', 'headers' => ['OpenAI-Beta' => 'agents=v1']],
        'deleteVaultCredential' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/vaults/{vault_id}/credentials/{credential_id}', 'category' => 'agents', 'body' => 'none', 'headers' => ['OpenAI-Beta' => 'agents=v1']],

        // ChatKit
        'cancelChatKitSession' => ['method' => self::HTTP_METHOD_POST, 'path' => '/chatkit/sessions/{session_id}/cancel', 'category' => 'chatkit', 'body' => 'none'],
        'createChatKitSession' => ['method' => self::HTTP_METHOD_POST, 'path' => '/chatkit/sessions', 'category' => 'chatkit', 'body' => 'json'],
        'listChatKitThreadItems' => ['method' => self::HTTP_METHOD_GET, 'path' => '/chatkit/threads/{thread_id}/items', 'category' => 'chatkit', 'body' => 'none'],
        'retrieveChatKitThread' => ['method' => self::HTTP_METHOD_GET, 'path' => '/chatkit/threads/{thread_id}', 'category' => 'chatkit', 'body' => 'none'],
        'deleteChatKitThread' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/chatkit/threads/{thread_id}', 'category' => 'chatkit', 'body' => 'none'],
        'listChatKitThreads' => ['method' => self::HTTP_METHOD_GET, 'path' => '/chatkit/threads', 'category' => 'chatkit', 'body' => 'none'],

        // Batches
        'createBatch' => ['method' => self::HTTP_METHOD_POST, 'path' => '/batches', 'category' => 'batches', 'body' => 'json'],
        'retrieveBatch' => ['method' => self::HTTP_METHOD_GET, 'path' => '/batches/{batch_id}', 'category' => 'batches', 'body' => 'none'],
        'cancelBatch' => ['method' => self::HTTP_METHOD_POST, 'path' => '/batches/{batch_id}/cancel', 'category' => 'batches', 'body' => 'none'],
        'listBatches' => ['method' => self::HTTP_METHOD_GET, 'path' => '/batches', 'category' => 'batches', 'body' => 'none'],

        // Uploads
        'createUpload' => ['method' => self::HTTP_METHOD_POST, 'path' => '/uploads', 'category' => 'uploads', 'body' => 'json'],
        'completeUpload' => ['method' => self::HTTP_METHOD_POST, 'path' => '/uploads/{upload_id}/complete', 'category' => 'uploads', 'body' => 'json'],
        'cancelUpload' => ['method' => self::HTTP_METHOD_POST, 'path' => '/uploads/{upload_id}/cancel', 'category' => 'uploads', 'body' => 'none'],
        'addUploadPart' => ['method' => self::HTTP_METHOD_POST, 'path' => '/uploads/{upload_id}/parts', 'category' => 'uploads', 'body' => 'multipart', 'fileFields' => ['data']],

        // Admin API Keys
        'listAuditLogs' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/audit_logs', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'listAdminApiKeys' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/admin_api_keys', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'createAdminApiKey' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/admin_api_keys', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'retrieveAdminApiKey' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/admin_api_keys/{key_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'deleteAdminApiKey' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/organization/admin_api_keys/{key_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],

        // Usage
        'getAudioSpeechesUsage' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/usage/audio_speeches', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'getAudioTranscriptionsUsage' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/usage/audio_transcriptions', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'getCodeInterpreterSessionsUsage' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/usage/code_interpreter_sessions', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'getCompletionsUsage' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/usage/completions', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'getEmbeddingsUsage' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/usage/embeddings', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'getImagesUsage' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/usage/images', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'getModerationsUsage' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/usage/moderations', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'getVectorStoresUsage' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/usage/vector_stores', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'getFileSearchCallsUsage' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/usage/file_search_calls', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'getWebSearchCallsUsage' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/usage/web_search_calls', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'getCosts' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/costs', 'category' => 'administration', 'body' => 'none', 'admin' => true],

        // Organization Invites
        'listInvites' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/invites', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'createInvite' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/invites', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'retrieveInvite' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/invites/{invite_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'deleteInvite' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/organization/invites/{invite_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],

        // Organization Users
        'listUsers' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/users', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'retrieveUser' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/users/{user_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'modifyUser' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/users/{user_id}', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'deleteUser' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/organization/users/{user_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'listUserRoles' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/users/{user_id}/roles', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'assignUserRole' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/users/{user_id}/roles', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'retrieveUserRole' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/users/{user_id}/roles/{role_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'unassignUserRole' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/organization/users/{user_id}/roles/{role_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],

        // Organization Groups
        'listGroups' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/groups', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'createGroup' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/groups', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'retrieveGroup' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/groups/{group_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'updateGroup' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/groups/{group_id}', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'deleteGroup' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/organization/groups/{group_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'listGroupUsers' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/groups/{group_id}/users', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'addGroupUser' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/groups/{group_id}/users', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'retrieveGroupUser' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/groups/{group_id}/users/{user_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'removeGroupUser' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/organization/groups/{group_id}/users/{user_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'listGroupRoles' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/groups/{group_id}/roles', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'assignGroupRole' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/groups/{group_id}/roles', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'retrieveGroupRole' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/groups/{group_id}/roles/{role_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'unassignGroupRole' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/organization/groups/{group_id}/roles/{role_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],

        // Organization Spend Limit
        'retrieveOrganizationSpendLimit' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/spend_limit', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'updateOrganizationSpendLimit' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/spend_limit', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'deleteOrganizationSpendLimit' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/organization/spend_limit', 'category' => 'administration', 'body' => 'none', 'admin' => true],

        // Organization Roles
        'listOrganizationRoles' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/roles', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'createOrganizationRole' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/roles', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'retrieveOrganizationRole' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/roles/{role_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'updateOrganizationRole' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/roles/{role_id}', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'deleteOrganizationRole' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/organization/roles/{role_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],

        // Organization Data Retention
        'retrieveOrganizationDataRetention' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/data_retention', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'updateOrganizationDataRetention' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/data_retention', 'category' => 'administration', 'body' => 'json', 'admin' => true],

        // Organization Spend Alerts
        'listOrganizationSpendAlerts' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/spend_alerts', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'createOrganizationSpendAlert' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/spend_alerts', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'retrieveOrganizationSpendAlert' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/spend_alerts/{alert_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'updateOrganizationSpendAlert' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/spend_alerts/{alert_id}', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'deleteOrganizationSpendAlert' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/organization/spend_alerts/{alert_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],

        // Certificates
        'listCertificates' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/certificates', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'uploadCertificate' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/certificates', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'getCertificate' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/certificates/{certificate_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'modifyCertificate' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/certificates/{certificate_id}', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'deleteCertificate' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/organization/certificates/{certificate_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'activateCertificates' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/certificates/activate', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'deactivateCertificates' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/certificates/deactivate', 'category' => 'administration', 'body' => 'json', 'admin' => true],

        // Organization Projects
        'listProjects' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'createProject' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'retrieveProject' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'modifyProject' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects/{project_id}', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'archiveProject' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects/{project_id}/archive', 'category' => 'administration', 'body' => 'none', 'admin' => true],

        // Project Users
        'listProjectUsers' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}/users', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'createProjectUser' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects/{project_id}/users', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'retrieveProjectUser' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}/users/{user_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'modifyProjectUser' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects/{project_id}/users/{user_id}', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'deleteProjectUser' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/organization/projects/{project_id}/users/{user_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'listProjectUserRoles' => ['method' => self::HTTP_METHOD_GET, 'path' => '/projects/{project_id}/users/{user_id}/roles', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'assignProjectUserRole' => ['method' => self::HTTP_METHOD_POST, 'path' => '/projects/{project_id}/users/{user_id}/roles', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'retrieveProjectUserRole' => ['method' => self::HTTP_METHOD_GET, 'path' => '/projects/{project_id}/users/{user_id}/roles/{role_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'unassignProjectUserRole' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/projects/{project_id}/users/{user_id}/roles/{role_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],

        // Project Service Accounts
        'listProjectServiceAccounts' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}/service_accounts', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'createProjectServiceAccount' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects/{project_id}/service_accounts', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'retrieveProjectServiceAccount' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}/service_accounts/{service_account_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'updateProjectServiceAccount' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects/{project_id}/service_accounts/{service_account_id}', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'deleteProjectServiceAccount' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/organization/projects/{project_id}/service_accounts/{service_account_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'createProjectServiceAccountApiKey' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects/{project_id}/service_accounts/{service_account_id}/api_keys', 'category' => 'administration', 'body' => 'json', 'admin' => true],

        // Project API Keys
        'listProjectApiKeys' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}/api_keys', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'retrieveProjectApiKey' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}/api_keys/{api_key_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'deleteProjectApiKey' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/organization/projects/{project_id}/api_keys/{api_key_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],

        // Rate Limits
        'listProjectRateLimits' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}/rate_limits', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'modifyProjectRateLimit' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects/{project_id}/rate_limits/{rate_limit_id}', 'category' => 'administration', 'body' => 'json', 'admin' => true],

        // Project Permissions
        'retrieveProjectModelPermissions' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}/model_permissions', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'modifyProjectModelPermissions' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects/{project_id}/model_permissions', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'deleteProjectModelPermissions' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/organization/projects/{project_id}/model_permissions', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'retrieveProjectHostedToolPermissions' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}/hosted_tool_permissions', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'modifyProjectHostedToolPermissions' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects/{project_id}/hosted_tool_permissions', 'category' => 'administration', 'body' => 'json', 'admin' => true],

        // Project Groups
        'listProjectGroups' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}/groups', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'addProjectGroup' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects/{project_id}/groups', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'retrieveProjectGroup' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}/groups/{group_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'removeProjectGroup' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/organization/projects/{project_id}/groups/{group_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'listProjectGroupRoles' => ['method' => self::HTTP_METHOD_GET, 'path' => '/projects/{project_id}/groups/{group_id}/roles', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'assignProjectGroupRole' => ['method' => self::HTTP_METHOD_POST, 'path' => '/projects/{project_id}/groups/{group_id}/roles', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'retrieveProjectGroupRole' => ['method' => self::HTTP_METHOD_GET, 'path' => '/projects/{project_id}/groups/{group_id}/roles/{role_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'unassignProjectGroupRole' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/projects/{project_id}/groups/{group_id}/roles/{role_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],

        // Project Roles
        'listProjectRoles' => ['method' => self::HTTP_METHOD_GET, 'path' => '/projects/{project_id}/roles', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'createProjectRole' => ['method' => self::HTTP_METHOD_POST, 'path' => '/projects/{project_id}/roles', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'retrieveProjectRole' => ['method' => self::HTTP_METHOD_GET, 'path' => '/projects/{project_id}/roles/{role_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'updateProjectRole' => ['method' => self::HTTP_METHOD_POST, 'path' => '/projects/{project_id}/roles/{role_id}', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'deleteProjectRole' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/projects/{project_id}/roles/{role_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],

        // Project Data Retention
        'retrieveProjectDataRetention' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}/data_retention', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'updateProjectDataRetention' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects/{project_id}/data_retention', 'category' => 'administration', 'body' => 'json', 'admin' => true],

        // Project Spend Alerts
        'listProjectSpendAlerts' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}/spend_alerts', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'createProjectSpendAlert' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects/{project_id}/spend_alerts', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'retrieveProjectSpendAlert' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}/spend_alerts/{alert_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'updateProjectSpendAlert' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects/{project_id}/spend_alerts/{alert_id}', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'deleteProjectSpendAlert' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/organization/projects/{project_id}/spend_alerts/{alert_id}', 'category' => 'administration', 'body' => 'none', 'admin' => true],

        // Project Spend Limit
        'retrieveProjectSpendLimit' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}/spend_limit', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'updateProjectSpendLimit' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects/{project_id}/spend_limit', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'deleteProjectSpendLimit' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/organization/projects/{project_id}/spend_limit', 'category' => 'administration', 'body' => 'none', 'admin' => true],

        // Project Certificates
        'listProjectCertificates' => ['method' => self::HTTP_METHOD_GET, 'path' => '/organization/projects/{project_id}/certificates', 'category' => 'administration', 'body' => 'none', 'admin' => true],
        'activateProjectCertificates' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects/{project_id}/certificates/activate', 'category' => 'administration', 'body' => 'json', 'admin' => true],
        'deactivateProjectCertificates' => ['method' => self::HTTP_METHOD_POST, 'path' => '/organization/projects/{project_id}/certificates/deactivate', 'category' => 'administration', 'body' => 'json', 'admin' => true],

        // Responses
        'createResponse' => ['method' => self::HTTP_METHOD_POST, 'path' => '/responses', 'category' => 'responses', 'body' => 'json'],
        'getResponse' => ['method' => self::HTTP_METHOD_GET, 'path' => '/responses/{response_id}', 'category' => 'responses', 'body' => 'none'],
        'deleteResponse' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/responses/{response_id}', 'category' => 'responses', 'body' => 'none'],
        'cancelResponse' => ['method' => self::HTTP_METHOD_POST, 'path' => '/responses/{response_id}/cancel', 'category' => 'responses', 'body' => 'none'],
        'compactResponse' => ['method' => self::HTTP_METHOD_POST, 'path' => '/responses/compact', 'category' => 'responses', 'body' => 'json'],
        'listInputItems' => ['method' => self::HTTP_METHOD_GET, 'path' => '/responses/{response_id}/input_items', 'category' => 'responses', 'body' => 'none'],
        'countResponseInputTokens' => ['method' => self::HTTP_METHOD_POST, 'path' => '/responses/input_tokens', 'category' => 'responses', 'body' => 'json'],

        // Responses (beta schema)
        'createBetaResponse' => ['method' => self::HTTP_METHOD_POST, 'path' => '/responses', 'category' => 'responses_beta', 'body' => 'json', 'query' => ['beta' => 'true']],
        'getBetaResponse' => ['method' => self::HTTP_METHOD_GET, 'path' => '/responses/{response_id}', 'category' => 'responses_beta', 'body' => 'none', 'query' => ['beta' => 'true']],
        'deleteBetaResponse' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/responses/{response_id}', 'category' => 'responses_beta', 'body' => 'none', 'query' => ['beta' => 'true']],
        'cancelBetaResponse' => ['method' => self::HTTP_METHOD_POST, 'path' => '/responses/{response_id}/cancel', 'category' => 'responses_beta', 'body' => 'none', 'query' => ['beta' => 'true']],
        'compactBetaResponse' => ['method' => self::HTTP_METHOD_POST, 'path' => '/responses/compact', 'category' => 'responses_beta', 'body' => 'json', 'query' => ['beta' => 'true']],
        'listBetaResponseInputItems' => ['method' => self::HTTP_METHOD_GET, 'path' => '/responses/{response_id}/input_items', 'category' => 'responses_beta', 'body' => 'none', 'query' => ['beta' => 'true']],
        'countBetaResponseInputTokens' => ['method' => self::HTTP_METHOD_POST, 'path' => '/responses/input_tokens', 'category' => 'responses_beta', 'body' => 'json', 'query' => ['beta' => 'true']],

        // Realtime
        'createRealtimeCall' => ['method' => self::HTTP_METHOD_POST, 'path' => '/realtime/calls', 'category' => 'realtime', 'body' => 'multipart', 'fileFields' => []],
        'createRealtimeClientSecret' => ['method' => self::HTTP_METHOD_POST, 'path' => '/realtime/client_secrets', 'category' => 'realtime', 'body' => 'json'],
        'acceptRealtimeCall' => ['method' => self::HTTP_METHOD_POST, 'path' => '/realtime/calls/{call_id}/accept', 'category' => 'realtime', 'body' => 'json'],
        'hangupRealtimeCall' => ['method' => self::HTTP_METHOD_POST, 'path' => '/realtime/calls/{call_id}/hangup', 'category' => 'realtime', 'body' => 'none'],
        'referRealtimeCall' => ['method' => self::HTTP_METHOD_POST, 'path' => '/realtime/calls/{call_id}/refer', 'category' => 'realtime', 'body' => 'json'],
        'rejectRealtimeCall' => ['method' => self::HTTP_METHOD_POST, 'path' => '/realtime/calls/{call_id}/reject', 'category' => 'realtime', 'body' => 'json'],
        'createRealtimeTranslationClientSecret' => ['method' => self::HTTP_METHOD_POST, 'path' => '/realtime/translations/client_secrets', 'category' => 'realtime', 'body' => 'json'],

        // Live
        'createLiveSession' => ['method' => self::HTTP_METHOD_POST, 'path' => '/live/sessions', 'category' => 'live', 'body' => 'json'],
        'acceptLiveSession' => ['method' => self::HTTP_METHOD_POST, 'path' => '/live/sessions/{session_id}/accept', 'category' => 'live', 'body' => 'json'],
        'downloadLiveSessionRecording' => ['method' => self::HTTP_METHOD_GET, 'path' => '/live/sessions/{session_id}/content', 'category' => 'live', 'body' => 'none'],
        'forkLiveSession' => ['method' => self::HTTP_METHOD_POST, 'path' => '/live/sessions/{session_id}/fork', 'category' => 'live', 'body' => 'json'],
        'hangupLiveSession' => ['method' => self::HTTP_METHOD_POST, 'path' => '/live/sessions/{session_id}/hangup', 'category' => 'live', 'body' => 'none'],
        'referLiveSession' => ['method' => self::HTTP_METHOD_POST, 'path' => '/live/sessions/{session_id}/refer', 'category' => 'live', 'body' => 'json'],
        'rejectLiveSession' => ['method' => self::HTTP_METHOD_POST, 'path' => '/live/sessions/{session_id}/reject', 'category' => 'live', 'body' => 'json'],

        // Conversations
        'createConversation' => ['method' => self::HTTP_METHOD_POST, 'path' => '/conversations', 'category' => 'conversations', 'body' => 'json'],
        'retrieveConversation' => ['method' => self::HTTP_METHOD_GET, 'path' => '/conversations/{conversation_id}', 'category' => 'conversations', 'body' => 'none'],
        'updateConversation' => ['method' => self::HTTP_METHOD_POST, 'path' => '/conversations/{conversation_id}', 'category' => 'conversations', 'body' => 'json'],
        'deleteConversation' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/conversations/{conversation_id}', 'category' => 'conversations', 'body' => 'none'],
        'createConversationItems' => ['method' => self::HTTP_METHOD_POST, 'path' => '/conversations/{conversation_id}/items', 'category' => 'conversations', 'body' => 'json'],
        'listConversationItems' => ['method' => self::HTTP_METHOD_GET, 'path' => '/conversations/{conversation_id}/items', 'category' => 'conversations', 'body' => 'none'],
        'retrieveConversationItem' => ['method' => self::HTTP_METHOD_GET, 'path' => '/conversations/{conversation_id}/items/{item_id}', 'category' => 'conversations', 'body' => 'none'],
        'deleteConversationItem' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/conversations/{conversation_id}/items/{item_id}', 'category' => 'conversations', 'body' => 'none'],

        // Containers
        'listContainers' => ['method' => self::HTTP_METHOD_GET, 'path' => '/containers', 'category' => 'containers', 'body' => 'none'],
        'createContainer' => ['method' => self::HTTP_METHOD_POST, 'path' => '/containers', 'category' => 'containers', 'body' => 'json'],
        'retrieveContainer' => ['method' => self::HTTP_METHOD_GET, 'path' => '/containers/{container_id}', 'category' => 'containers', 'body' => 'none'],
        'deleteContainer' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/containers/{container_id}', 'category' => 'containers', 'body' => 'none'],
        'listContainerFiles' => ['method' => self::HTTP_METHOD_GET, 'path' => '/containers/{container_id}/files', 'category' => 'containers', 'body' => 'none'],
        'createContainerFile' => ['method' => self::HTTP_METHOD_POST, 'path' => '/containers/{container_id}/files', 'category' => 'containers', 'body' => 'multipart', 'fileFields' => ['file']],
        'retrieveContainerFile' => ['method' => self::HTTP_METHOD_GET, 'path' => '/containers/{container_id}/files/{file_id}', 'category' => 'containers', 'body' => 'none'],
        'deleteContainerFile' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/containers/{container_id}/files/{file_id}', 'category' => 'containers', 'body' => 'none'],
        'retrieveContainerFileContent' => ['method' => self::HTTP_METHOD_GET, 'path' => '/containers/{container_id}/files/{file_id}/content', 'category' => 'containers', 'body' => 'none'],

        // Skills
        'createSkill' => ['method' => self::HTTP_METHOD_POST, 'path' => '/skills', 'category' => 'skills', 'body' => 'multipart', 'fileFields' => ['files']],
        'listSkills' => ['method' => self::HTTP_METHOD_GET, 'path' => '/skills', 'category' => 'skills', 'body' => 'none'],
        'retrieveSkill' => ['method' => self::HTTP_METHOD_GET, 'path' => '/skills/{skill_id}', 'category' => 'skills', 'body' => 'none'],
        'updateSkill' => ['method' => self::HTTP_METHOD_POST, 'path' => '/skills/{skill_id}', 'category' => 'skills', 'body' => 'json'],
        'deleteSkill' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/skills/{skill_id}', 'category' => 'skills', 'body' => 'none'],
        'retrieveSkillContent' => ['method' => self::HTTP_METHOD_GET, 'path' => '/skills/{skill_id}/content', 'category' => 'skills', 'body' => 'none'],
        'createSkillVersion' => ['method' => self::HTTP_METHOD_POST, 'path' => '/skills/{skill_id}/versions', 'category' => 'skills', 'body' => 'multipart', 'fileFields' => ['files']],
        'listSkillVersions' => ['method' => self::HTTP_METHOD_GET, 'path' => '/skills/{skill_id}/versions', 'category' => 'skills', 'body' => 'none'],
        'retrieveSkillVersion' => ['method' => self::HTTP_METHOD_GET, 'path' => '/skills/{skill_id}/versions/{version}', 'category' => 'skills', 'body' => 'none'],
        'deleteSkillVersion' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/skills/{skill_id}/versions/{version}', 'category' => 'skills', 'body' => 'none'],
        'retrieveSkillVersionContent' => ['method' => self::HTTP_METHOD_GET, 'path' => '/skills/{skill_id}/versions/{version}/content', 'category' => 'skills', 'body' => 'none'],
    ];

    /**
     * Prevents instantiation of this class.
     */
    protected function __construct()
    {
        // This class should not be instantiated.
    }

    /**
     * Gets all OpenAI API endpoint configurations.
     *
     * @return array<string, array{
     *     method: string,
     *     path: string,
     *     category: string,
     *     body: 'none'|'json'|'multipart',
     *     fileFields?: list<string>,
     *     headers?: array<string, string|string[]>,
     *     query?: array<string, bool|float|int|string>,
     *     streaming?: bool,
     *     admin?: bool,
     *     deprecated?: bool
     * }>
     */
    public static function getEndpoints(): array
    {
        return self::$urlEndpoints;
    }

    /**
     * Gets the OpenAI API endpoint configuration.
     *
     * @param string $key The endpoint key.
     *
     * @return array{
     *     method: string,
     *     path: string,
     *     category: string,
     *     body: 'none'|'json'|'multipart',
     *     fileFields?: list<string>,
     *     headers?: array<string, string|string[]>,
     *     query?: array<string, bool|float|int|string>,
     *     streaming?: bool,
     *     admin?: bool,
     *     deprecated?: bool
     * } The endpoint configuration.
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
        string $basePath = '',
    ): UriInterface {
        $endpoint = self::getEndpoint($key);
        $endpointPath = self::replacePathParameters($endpoint['path'], $parameters);
        $isAbsoluteOrigin = $origin !== '' && \preg_match('#^[a-z][a-z0-9+.-]*://#i', $origin) === 1;

        if ($isAbsoluteOrigin) {
            $uri = $uriFactory->createUri($origin);
            $originBasePath = $uri->getPath();
        } else {
            $uri = $uriFactory
                ->createUri()
                ->withScheme('https')
                ->withHost($origin !== '' ? $origin : self::ORIGIN);
            $originBasePath = '';
        }

        $resolvedBasePath = $basePath !== ''
            ? $basePath
            : ($isAbsoluteOrigin ? $originBasePath : self::BASE_PATH);
        $path = \rtrim('/' . \trim($resolvedBasePath, '/'), '/') . '/' . \ltrim($endpointPath, '/');

        return $uri->withPath($path);
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
                    \gettype($value),
                ));
            }

            return \rawurlencode((string) $value);
        }, $path);
    }
}
