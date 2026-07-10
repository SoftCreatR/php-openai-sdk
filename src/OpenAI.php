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

use Exception;
use InvalidArgumentException;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Random\RandomException;
use SensitiveParameter;
use SoftCreatR\OpenAI\Exception\OpenAIException;
use SoftCreatR\OpenAI\Http\MultipartBodyBuilder;
use SoftCreatR\OpenAI\Http\ServerSentEventDecoder;
use Throwable;

use const JSON_THROW_ON_ERROR;
use const PHP_QUERY_RFC3986;

/**
 * PSR-17/PSR-18 client for the OpenAI API.
 *
 * Registered endpoints can be called as magic methods or through request().
 * See README.md for the complete method catalog.
 *
 * API METHODS START
 * @method ResponseInterface|null acceptRealtimeCall(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null activateCertificates(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null activateProjectCertificates(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null addGroupUser(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null addProjectGroup(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null addUploadPart(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null archiveProject(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null assignGroupRole(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null assignProjectGroupRole(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null assignProjectUserRole(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null assignUserRole(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null cancelBatch(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null cancelChatKitSession(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null cancelFineTuning(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null) Deprecated upstream.
 * @method ResponseInterface|null cancelResponse(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null cancelUpload(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null cancelVectorStoreFileBatch(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null compactResponse(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null completeUpload(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null countResponseInputTokens(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createAdminApiKey(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createBatch(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createChatCompletion(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createChatKitSession(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createContainer(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createContainerFile(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createConversation(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createConversationItems(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createEmbedding(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createFineTuningCheckpointPermission(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null) Deprecated upstream.
 * @method ResponseInterface|null createFineTuningJob(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null) Deprecated upstream.
 * @method ResponseInterface|null createGroup(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createImage(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createImageEdit(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createInvite(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createModeration(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createOrganizationRole(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createOrganizationSpendAlert(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createProject(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createProjectRole(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createProjectServiceAccount(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createProjectSpendAlert(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createProjectUser(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createRealtimeClientSecret(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createRealtimeTranslationClientSecret(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createResponse(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createSkill(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createSkillVersion(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createSpeech(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createTranscription(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createTranslation(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createUpload(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createVectorStore(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createVectorStoreFile(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createVectorStoreFileBatch(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createVoice(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createVoiceConsent(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deactivateCertificates(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deactivateProjectCertificates(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteAdminApiKey(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteCertificate(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteChatCompletion(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteChatKitThread(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteContainer(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteContainerFile(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteConversation(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteConversationItem(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteFile(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteFineTuningCheckpointPermission(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null) Deprecated upstream.
 * @method ResponseInterface|null deleteGroup(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteInvite(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteModel(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteOrganizationRole(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteOrganizationSpendAlert(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteProjectApiKey(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteProjectModelPermissions(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteProjectRole(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteProjectServiceAccount(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteProjectSpendAlert(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteProjectUser(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteResponse(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteSkill(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteSkillVersion(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteUser(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteVectorStore(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteVectorStoreFile(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteVoiceConsent(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getAudioSpeechesUsage(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getAudioTranscriptionsUsage(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getCertificate(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getChatCompletion(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getChatMessages(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getCodeInterpreterSessionsUsage(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getCompletionsUsage(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getCosts(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getEmbeddingsUsage(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getFileSearchCallsUsage(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getImagesUsage(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getModerationsUsage(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getResponse(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getVectorStoresUsage(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getWebSearchCallsUsage(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null hangupRealtimeCall(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listAdminApiKeys(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listAuditLogs(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listBatches(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listCertificates(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listChatCompletions(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listChatKitThreadItems(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listChatKitThreads(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listContainerFiles(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listContainers(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listConversationItems(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listFiles(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listFineTuningCheckpointPermissions(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null) Deprecated upstream.
 * @method ResponseInterface|null listFineTuningCheckpoints(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null) Deprecated upstream.
 * @method ResponseInterface|null listFineTuningEvents(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null) Deprecated upstream.
 * @method ResponseInterface|null listFineTuningJobs(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null) Deprecated upstream.
 * @method ResponseInterface|null listGroupRoles(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listGroupUsers(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listGroups(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listInputItems(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listInvites(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listModels(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listOrganizationRoles(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listOrganizationSpendAlerts(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listProjectApiKeys(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listProjectCertificates(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listProjectGroupRoles(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listProjectGroups(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listProjectRateLimits(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listProjectRoles(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listProjectServiceAccounts(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listProjectSpendAlerts(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listProjectUserRoles(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listProjectUsers(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listProjects(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listSkillVersions(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listSkills(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listUserRoles(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listUsers(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listVectorStoreFiles(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listVectorStoreFilesInBatch(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listVectorStores(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listVoiceConsents(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null modifyCertificate(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null modifyProject(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null modifyProjectHostedToolPermissions(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null modifyProjectModelPermissions(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null modifyProjectRateLimit(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null modifyProjectUser(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null modifyUser(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null modifyVectorStore(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null pauseFineTuning(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null) Deprecated upstream.
 * @method ResponseInterface|null referRealtimeCall(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null rejectRealtimeCall(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null removeGroupUser(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null removeProjectGroup(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null resumeFineTuning(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null) Deprecated upstream.
 * @method ResponseInterface|null retrieveAdminApiKey(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveBatch(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveChatKitThread(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveContainer(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveContainerFile(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveContainerFileContent(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveConversation(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveConversationItem(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveFile(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveFileContent(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveFineTuningJob(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null) Deprecated upstream.
 * @method ResponseInterface|null retrieveGroup(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveGroupRole(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveGroupUser(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveInvite(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveModel(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveOrganizationDataRetention(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveOrganizationRole(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveOrganizationSpendAlert(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveProject(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveProjectApiKey(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveProjectDataRetention(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveProjectGroup(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveProjectGroupRole(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveProjectHostedToolPermissions(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveProjectModelPermissions(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveProjectRole(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveProjectServiceAccount(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveProjectSpendAlert(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveProjectUser(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveProjectUserRole(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveSkill(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveSkillContent(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveSkillVersion(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveSkillVersionContent(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveUser(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveUserRole(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveVectorStore(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveVectorStoreFile(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveVectorStoreFileBatch(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveVectorStoreFileContent(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveVoiceConsent(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null runFineTuningGrader(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null) Deprecated upstream.
 * @method ResponseInterface|null searchVectorStore(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null unassignGroupRole(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null unassignProjectGroupRole(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null unassignProjectUserRole(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null unassignUserRole(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateChatCompletion(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateConversation(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateGroup(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateOrganizationDataRetention(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateOrganizationRole(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateOrganizationSpendAlert(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateProjectDataRetention(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateProjectRole(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateProjectServiceAccount(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateProjectSpendAlert(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateSkill(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateVectorStoreFileAttributes(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateVoiceConsent(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null uploadCertificate(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null uploadFile(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null validateFineTuningGrader(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null) Deprecated upstream.
 * API METHODS END
 */
class OpenAI
{
    /** @var list<string> */
    private const DEFAULT_FILE_FIELDS = [
        'audio_sample',
        'data',
        'file',
        'files',
        'image',
        'mask',
        'recording',
    ];

    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly UriFactoryInterface $uriFactory,
        private readonly ClientInterface $httpClient,
        #[SensitiveParameter]
        private readonly string $apiKey,
        private readonly string $organization = '',
        private readonly string $origin = '',
        private readonly string $basePath = '',
        private readonly string $project = '',
    ) {}

    /**
     * Calls a registered endpoint by its SDK method name.
     *
     * @param array<int, mixed> $args
     *
     * @throws OpenAIException If the API returns an error.
     * @throws InvalidArgumentException If the endpoint or its arguments are invalid.
     * @throws RandomException If multipart boundary generation fails.
     * @throws Throwable If request body construction or streaming fails.
     */
    public function __call(string $key, array $args): ?ResponseInterface
    {
        $endpoint = OpenAIURLBuilder::getEndpoint($key);
        [$parameters, $body, $streamCallback, $customHeaders] = $this->extractCallArguments($args, $endpoint);

        return $this->request($key, $parameters, $body, $streamCallback, $customHeaders);
    }

    /**
     * Sends a request using a registered endpoint name.
     *
     * @param array<string, mixed> $parameters Path and query parameters.
     * @param array<string, mixed> $body JSON or multipart body fields.
     * @param callable(array<string, mixed>):void|null $streamCallback SSE event callback.
     * @param array<string, string|string[]> $customHeaders Additional request headers.
     *
     * @throws OpenAIException If the API returns an error.
     * @throws InvalidArgumentException If the endpoint or its parameters are invalid.
     * @throws RandomException If multipart boundary generation fails.
     * @throws Throwable If request body construction or streaming fails.
     */
    public function request(
        string $key,
        array $parameters = [],
        array $body = [],
        ?callable $streamCallback = null,
        array $customHeaders = [],
    ): ?ResponseInterface {
        $endpoint = OpenAIURLBuilder::getEndpoint($key);
        $pathParameters = $this->getPathParameters($endpoint['path']);
        $pathKeys = \array_flip($pathParameters);
        $pathValues = \array_intersect_key($parameters, $pathKeys);
        $query = \array_diff_key($parameters, $pathKeys);
        $uri = OpenAIURLBuilder::createUrl(
            $this->uriFactory,
            $key,
            $pathValues,
            $this->origin,
            $this->basePath,
        );

        if ($query !== []) {
            $uri = $uri->withQuery(\http_build_query($query, '', '&', PHP_QUERY_RFC3986));
        }

        return $this->sendRequest(
            $uri,
            $endpoint['method'],
            $endpoint,
            $body,
            $streamCallback,
            $customHeaders,
        );
    }

    /**
     * Normalizes the legacy two-array convention and the canonical body-first convention.
     *
     * @param array<int, mixed> $args
     * @param array{method:string,path:string,body?:string,fileFields?:list<string>}|null $endpoint
     *
     * @return array{array<string,mixed>,array<string,mixed>,callable|null,array<string,string|string[]>}
     *
     * @throws InvalidArgumentException If the endpoint arguments are invalid.
     */
    private function extractCallArguments(array $args, ?array $endpoint = null): array
    {
        if (\count($args) > 3) {
            throw new InvalidArgumentException('Endpoint calls accept at most three arguments.');
        }

        if (isset($args[0]) && !\is_array($args[0])) {
            throw new InvalidArgumentException('First argument must be an array of parameters.');
        }

        $first = $args[0] ?? [];
        $second = [];
        $hasSecondArray = isset($args[1]) && \is_array($args[1]);
        $streamCallback = null;

        if ($hasSecondArray) {
            $second = $args[1];
        } elseif (isset($args[1])) {
            if (!\is_callable($args[1])) {
                throw new InvalidArgumentException('Second argument must be an array or callable.');
            }

            $streamCallback = $args[1];
        }

        if (isset($args[2])) {
            if (!$hasSecondArray || !\is_callable($args[2])) {
                throw new InvalidArgumentException('Third argument must be a stream callback.');
            }

            $streamCallback = $args[2];
        }

        $customHeaders = $this->extractCustomHeaders($first, $second);

        // Keep the old helper shape usable by reflective consumers.
        if ($endpoint === null) {
            return [$first, $second, $streamCallback, $customHeaders];
        }

        $bodyType = $endpoint['body'] ?? $this->inferBodyType($endpoint['method'], $endpoint['path']);

        if ($bodyType === 'none') {
            return [$first + $second, [], $streamCallback, $customHeaders];
        }

        // Existing 3.x calls pass path/query parameters first and the body second.
        if ($hasSecondArray) {
            return [$first, $second, $streamCallback, $customHeaders];
        }

        $pathParameters = $this->getPathParameters($endpoint['path']);

        if ($pathParameters === []) {
            return [[], $first, $streamCallback, $customHeaders];
        }

        $pathKeys = \array_flip($pathParameters);

        return [
            \array_intersect_key($first, $pathKeys),
            \array_diff_key($first, $pathKeys),
            $streamCallback,
            $customHeaders,
        ];
    }

    /**
     * @param array<string, mixed> $first
     * @param array<string, mixed> $second
     *
     * @return array<string, string|string[]>
     *
     * @throws InvalidArgumentException If customHeaders is not an array.
     */
    private function extractCustomHeaders(array &$first, array &$second): array
    {
        $headers = [];

        foreach ([$first, $second] as $values) {
            if (!isset($values['customHeaders'])) {
                continue;
            }

            if (!\is_array($values['customHeaders'])) {
                throw new InvalidArgumentException('customHeaders must be an array.');
            }

            foreach ($values['customHeaders'] as $name => $value) {
                $headers[$name] = $value;
            }
        }

        unset($first['customHeaders'], $second['customHeaders']);

        return $headers;
    }

    /**
     * @return list<string>
     */
    private function getPathParameters(string $path): array
    {
        \preg_match_all('/\{([A-Za-z_]\w*)}/', $path, $matches);

        return $matches[1];
    }

    private function inferBodyType(string $method, string $path): string
    {
        if (\in_array($method, ['GET', 'DELETE'], true)) {
            return 'none';
        }

        if (\in_array($path, [
            '/audio/transcriptions',
            '/audio/translations',
            '/audio/voices',
            '/files',
            '/images/edits',
            '/uploads/{upload_id}/parts',
            '/audio/voice_consents',
            '/containers/{container_id}/files',
        ], true)) {
            return 'multipart';
        }

        return 'json';
    }

    /**
     * @param array{path?:string,body?:string,fileFields?:list<string>} $endpoint
     * @param array<string, mixed> $body
     * @param array<string, string|string[]> $customHeaders
     *
     * @throws OpenAIException If the API returns an error.
     * @throws RandomException If multipart boundary generation fails.
     * @throws Throwable If request body construction or streaming fails.
     */
    private function sendRequest(
        UriInterface $uri,
        string $method,
        array $endpoint,
        array $body,
        ?callable $streamCallback,
        array $customHeaders,
    ): ?ResponseInterface {
        $bodyType = $endpoint['body'] ?? $this->inferBodyType($method, $endpoint['path'] ?? $uri->getPath());
        $boundary = $body !== [] && $bodyType === 'multipart' ? $this->generateMultipartBoundary() : null;
        $requestBody = $this->createRequestBody($bodyType, $body, $boundary, $endpoint['fileFields'] ?? []);
        $contentType = null;

        if ($requestBody !== null) {
            $contentType = $bodyType === 'multipart'
                ? "multipart/form-data; boundary={$boundary}"
                : 'application/json';
        }

        $request = $this->requestFactory->createRequest($method, $uri);
        $request = $this->applyHeaders($request, $this->createHeaders($contentType, null, $customHeaders));

        if ($requestBody !== null) {
            $request = $request->withBody($requestBody);
        }

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $exception) {
            throw new OpenAIException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }

        $this->throwForErrorResponse($response);

        $isEventStream = \str_contains(\strtolower($response->getHeaderLine('Content-Type')), 'text/event-stream');
        $streamRequested = ($body['stream'] ?? false) === true || ($body['stream_format'] ?? null) === 'sse';

        if ($streamCallback !== null && ($streamRequested || $isEventStream)) {
            (new ServerSentEventDecoder())->decode($response->getBody(), $streamCallback);

            return null;
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $body
     * @param list<string> $fileFields
     *
     * @throws OpenAIException If JSON encoding fails.
     * @throws RandomException Retained for compatibility with the 3.x exception contract.
     * @throws Throwable If multipart body construction fails.
     */
    private function createRequestBody(
        string $bodyType,
        array $body,
        ?string $boundary,
        array $fileFields,
    ): ?StreamInterface {
        if ($body === [] || $bodyType === 'none') {
            return null;
        }

        if ($bodyType === 'multipart') {
            return $this->createMultipartStream($body, (string) $boundary, $fileFields);
        }

        return $this->streamFactory->createStream($this->createJsonBody($body));
    }

    /**
     * @throws OpenAIException If the API returns an error response.
     */
    private function throwForErrorResponse(ResponseInterface $response): void
    {
        if ($response->getStatusCode() < 400) {
            return;
        }

        throw new OpenAIException(
            $response->getBody()->getContents(),
            $response->getStatusCode(),
            null,
            $response->getHeaderLine('x-request-id'),
            $response->getHeaders(),
        );
    }

    /**
     * @throws Exception If the operating system cannot provide random bytes.
     * @throws RandomException Retained for compatibility with the 3.x exception contract.
     */
    private function generateMultipartBoundary(): string
    {
        return '----OpenAI' . \bin2hex(\random_bytes(16));
    }

    /**
     * The bool form is retained for backwards compatibility with reflective test helpers.
     *
     * @param bool|string|null $contentType
     * @param string|null $boundary
     * @param array<string, string|string[]> $customHeaders
     * @return array<string, string|string[]>
     */
    private function createHeaders(
        bool|string|null $contentType,
        ?string $boundary = null,
        array $customHeaders = [],
    ): array {
        if (\is_bool($contentType)) {
            $contentType = $contentType
                ? "multipart/form-data; boundary={$boundary}"
                : 'application/json';
        }

        $headers = ['Authorization' => 'Bearer ' . $this->apiKey];

        if ($this->organization !== '') {
            $headers['OpenAI-Organization'] = $this->organization;
        }

        if ($this->project !== '') {
            $headers['OpenAI-Project'] = $this->project;
        }

        if ($contentType !== null) {
            $headers['Content-Type'] = $contentType;
        }

        return \array_replace($headers, $customHeaders);
    }

    /**
     * @param array<string, string|string[]> $headers
     */
    private function applyHeaders(RequestInterface $request, array $headers): RequestInterface
    {
        foreach ($headers as $key => $value) {
            $request = $request->withHeader($key, $value);
        }

        return $request;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @throws OpenAIException If JSON encoding fails.
     */
    private function createJsonBody(array $params): string
    {
        try {
            return \json_encode($params, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new OpenAIException('JSON encode error: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @param array<string, mixed> $params
     * @param list<string> $fileFields
     *
     * @throws RandomException Retained for compatibility with the 3.x exception contract.
     * @throws Throwable If multipart body construction fails.
     */
    private function createMultipartStream(array $params, string $boundary, array $fileFields = []): StreamInterface
    {
        if ($fileFields === []) {
            $fileFields = self::DEFAULT_FILE_FIELDS;
        }

        return (new MultipartBodyBuilder($this->streamFactory))->build($params, $boundary, $fileFields);
    }
}
