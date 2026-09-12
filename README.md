# OpenAI API SDK for PHP

[![Tests](https://img.shields.io/github/actions/workflow/status/SoftCreatR/php-openai-sdk/.github/workflows/validate-pr.yml?branch=main&label=tests)](https://github.com/SoftCreatR/php-openai-sdk/actions/workflows/validate-pr.yml)
[![Latest Release](https://img.shields.io/packagist/v/softcreatr/php-openai-sdk)](https://packagist.org/packages/softcreatr/php-openai-sdk)
[![PHP](https://img.shields.io/packagist/dependency-v/softcreatr/php-openai-sdk/php)](composer.json)
[![License](https://img.shields.io/badge/license-ISC-blue.svg)](LICENSE.md)

A lightweight, underrated PSR-17/PSR-18 client for the current [OpenAI API](https://developers.openai.com/api/reference), with
examples for every exposed SDK method, SSE streaming, streamed multipart uploads, structured errors, project scoping,
endpoint-specific beta headers, and webhook signature verification.

## Requirements

- PHP 8.1 or newer. CI tests PHP 8.1, 8.2, 8.3, 8.4, and 8.5.
- A PSR-17 request, stream, and URI factory.
- A PSR-18 HTTP client.
- The JSON extension.

Guzzle is used below because it provides both the PSR-17 factories and PSR-18 client implementation. The SDK itself
depends only on the PSR interfaces, so other compliant implementations remain supported.

## Installation

```bash
composer require softcreatr/php-openai-sdk guzzlehttp/guzzle
```

## Client Setup

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use SoftCreatR\OpenAI\OpenAI;

$factory = new HttpFactory();
$openAI = new OpenAI(
    requestFactory: $factory,
    streamFactory: $factory,
    uriFactory: $factory,
    httpClient: new Client(['stream' => true]),
    apiKey: (string) getenv('OPENAI_API_KEY'),
    organization: (string) getenv('OPENAI_ORGANIZATION_ID'),
    project: (string) getenv('OPENAI_PROJECT_ID'),
);
```

Keep API keys on the server and out of source control. Organization and project IDs are optional.

## Responses API

The Responses API is the recommended interface for new text and agentic integrations.

```php
use const JSON_THROW_ON_ERROR;

$response = $openAI->createResponse([
    'model' => 'gpt-5.4-mini',
    'input' => 'Give me a one-sentence summary of PSR-18.',
]);

$result = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
echo $result['output'][0]['content'][0]['text'];
```

Endpoint methods return a PSR-7 `ResponseInterface`, including calls that deliver SSE events to a callback.

## Agents API

The beta Agents API provides reusable agents, managed sessions, hosted environments, artifacts, subagents, and vaults.
The SDK applies the required `OpenAI-Beta: agents=v1` header automatically.

```php
$agent = $openAI->createAgent([
    'name' => 'Documentation assistant',
    'model' => 'gpt-5.4-mini',
    'instructions' => 'Answer questions using the available project files.',
]);
```

## Arguments

Body-only endpoints use a single body array:

```php
$openAI->createEmbedding([
    'model' => 'text-embedding-3-small',
    'input' => 'A sentence to embed.',
]);
```

For endpoints with path parameters, a single combined array is the preferred v4 form. Path fields are separated from
the request body using the endpoint template:

```php
$openAI->updateConversation([
    'conversation_id' => 'conv_abc123',
    'metadata' => ['customer' => 'acme'],
]);
```

The v3 two-array form remains supported for existing integrations:

```php
$openAI->updateConversation(
    ['conversation_id' => 'conv_abc123'],
    ['metadata' => ['customer' => 'acme']],
);
```

For `GET` and `DELETE` methods, non-path values become RFC 3986 query parameters. Path parameter values are URL
encoded. The explicit form is available when method names are determined at runtime:

```php
$response = $openAI->request(
    'listFiles',
    ['limit' => 20, 'after' => 'file_abc123'],
    customHeaders: ['X-Trace-Id' => 'trace_abc123'],
);
```

## Streaming

Set `stream` to `true` and pass a callback. The decoder supports arbitrarily split chunks, CRLF and LF delimiters,
comments, multiline data fields, final unterminated frames, and `[DONE]`.

```php
$openAI->createResponse(
    [
        'model' => 'gpt-5.4-mini',
        'input' => 'Write a short haiku about PHP.',
        'stream' => true,
    ],
    static function (array $event): void {
        if ($event['type'] === 'response.output_text.delta') {
            echo $event['delta'];
        }
    },
);
```

An ordinary JSON response is still returned when a callback is supplied without requesting an SSE stream. Endpoints
that are inherently streaming, such as `streamAgentSessionEvents`, select a `StreamingClientInterface` transport
without requiring a body flag.

## File Uploads

Multipart endpoints accept readable local file paths. Files are copied as raw bytes into a temporary stream rather
than being base64 encoded or assembled in one large PHP string.

```php
$response = $openAI->uploadFile([
    'file' => __DIR__ . '/data.jsonl',
    'purpose' => 'user_data',
]);
```

Repeated upload fields such as image inputs and skill files accept arrays of paths. Nested non-file values are encoded
using bracket notation. Multipart fields that are protocol payloads rather than files, such as Realtime SDP, remain
ordinary strings.

## Errors

4xx and 5xx responses throw `OpenAIException`. The exception keeps the parsed API error, raw response body, response
headers, status code, and `x-request-id` for diagnostics.

```php
use SoftCreatR\OpenAI\Exception\OpenAIException;

try {
    $openAI->retrieveModel(['model' => 'missing-model']);
} catch (OpenAIException $exception) {
    error_log(sprintf(
        'OpenAI request %s failed (%d): %s',
        $exception->getRequestId() ?? 'unknown',
        $exception->getCode(),
        $exception->getMessage(),
    ));
}
```

PSR-18 transport failures are wrapped in `OpenAIException` and retain the original exception as `getPrevious()`.

## Webhooks

Verify the signature against the exact raw request body before decoding or changing it. The verifier follows OpenAI's
[webhook guide](https://developers.openai.com/api/docs/guides/webhooks) and supports multiple `v1` signatures during
secret rotation.

```php
use SoftCreatR\OpenAI\Webhook\WebhookVerifier;

$rawBody = (string) $request->getBody();
$event = (new WebhookVerifier((string) getenv('OPENAI_WEBHOOK_SECRET')))
    ->unwrap($rawBody, $request->getHeaders());

if ($event['type'] === 'response.completed') {
    // Handle the completed response idempotently.
}
```

The default replay tolerance is 300 seconds. Invalid signatures throw `InvalidWebhookSignatureException`; valid
signatures with invalid JSON throw `WebhookException`.

## Examples

Examples load the ignored project-level `.env` through `examples/OpenAIFactory.php`:

```bash
cp .env.example .env
php examples/responses/createResponse.php
```

Set `OPENAI_ADMIN_KEY` only when running examples under `examples/administration`. The Agents vault credential examples
also read `MCP_BEARER_TOKEN`; keep that secret in `.env` and never commit it.

## Supported Methods

The catalog follows the current [OpenAI API reference](https://developers.openai.com/api/reference). Fine-tuning is
retained for transitional compatibility and is marked deprecated. APIs that OpenAI classifies as legacy are
intentionally absent. Distinct beta routes are registered separately from their stable counterparts.

### Administration

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `activateCertificates` | `POST /organization/certificates/activate` | `json` | [PHP](examples/administration/certificates/activateCertificates.php) |
| `activateProjectCertificates` | `POST /organization/projects/{project_id}/certificates/activate` | `json` | [PHP](examples/administration/certificates/activateProjectCertificates.php) |
| `addGroupUser` | `POST /organization/groups/{group_id}/users` | `json` | [PHP](examples/administration/org-groups/addGroupUser.php) |
| `addProjectGroup` | `POST /organization/projects/{project_id}/groups` | `json` | [PHP](examples/administration/project-groups/addProjectGroup.php) |
| `archiveProject` | `POST /organization/projects/{project_id}/archive` | `none` | [PHP](examples/administration/projects/archiveProject.php) |
| `assignGroupRole` | `POST /organization/groups/{group_id}/roles` | `json` | [PHP](examples/administration/org-groups/assignGroupRole.php) |
| `assignProjectGroupRole` | `POST /projects/{project_id}/groups/{group_id}/roles` | `json` | [PHP](examples/administration/project-groups/assignProjectGroupRole.php) |
| `assignProjectUserRole` | `POST /projects/{project_id}/users/{user_id}/roles` | `json` | [PHP](examples/administration/project-users/assignProjectUserRole.php) |
| `assignUserRole` | `POST /organization/users/{user_id}/roles` | `json` | [PHP](examples/administration/org-users/assignUserRole.php) |
| `createAdminApiKey` | `POST /organization/admin_api_keys` | `json` | [PHP](examples/administration/admin-api-keys/createAdminApiKey.php) |
| `createGroup` | `POST /organization/groups` | `json` | [PHP](examples/administration/org-groups/createGroup.php) |
| `createInvite` | `POST /organization/invites` | `json` | [PHP](examples/administration/invites/createInvite.php) |
| `createOrganizationRole` | `POST /organization/roles` | `json` | [PHP](examples/administration/org-roles/createOrganizationRole.php) |
| `createOrganizationSpendAlert` | `POST /organization/spend_alerts` | `json` | [PHP](examples/administration/org-spend-alerts/createOrganizationSpendAlert.php) |
| `createProject` | `POST /organization/projects` | `json` | [PHP](examples/administration/projects/createProject.php) |
| `createProjectRole` | `POST /projects/{project_id}/roles` | `json` | [PHP](examples/administration/project-roles/createProjectRole.php) |
| `createProjectServiceAccount` | `POST /organization/projects/{project_id}/service_accounts` | `json` | [PHP](examples/administration/project-service-accounts/createProjectServiceAccount.php) |
| `createProjectServiceAccountApiKey` | `POST /organization/projects/{project_id}/service_accounts/{service_account_id}/api_keys` | `json` | [PHP](examples/administration/project-service-accounts/createProjectServiceAccountApiKey.php) |
| `createProjectSpendAlert` | `POST /organization/projects/{project_id}/spend_alerts` | `json` | [PHP](examples/administration/project-spend-alerts/createProjectSpendAlert.php) |
| `createProjectUser` | `POST /organization/projects/{project_id}/users` | `json` | [PHP](examples/administration/project-users/createProjectUser.php) |
| `deactivateCertificates` | `POST /organization/certificates/deactivate` | `json` | [PHP](examples/administration/certificates/deactivateCertificates.php) |
| `deactivateProjectCertificates` | `POST /organization/projects/{project_id}/certificates/deactivate` | `json` | [PHP](examples/administration/certificates/deactivateProjectCertificates.php) |
| `deleteAdminApiKey` | `DELETE /organization/admin_api_keys/{key_id}` | `none` | [PHP](examples/administration/admin-api-keys/deleteAdminApiKey.php) |
| `deleteCertificate` | `DELETE /organization/certificates/{certificate_id}` | `none` | [PHP](examples/administration/certificates/deleteCertificate.php) |
| `deleteGroup` | `DELETE /organization/groups/{group_id}` | `none` | [PHP](examples/administration/org-groups/deleteGroup.php) |
| `deleteInvite` | `DELETE /organization/invites/{invite_id}` | `none` | [PHP](examples/administration/invites/deleteInvite.php) |
| `deleteOrganizationRole` | `DELETE /organization/roles/{role_id}` | `none` | [PHP](examples/administration/org-roles/deleteOrganizationRole.php) |
| `deleteOrganizationSpendAlert` | `DELETE /organization/spend_alerts/{alert_id}` | `none` | [PHP](examples/administration/org-spend-alerts/deleteOrganizationSpendAlert.php) |
| `deleteOrganizationSpendLimit` | `DELETE /organization/spend_limit` | `none` | [PHP](examples/administration/org-spend-limits/deleteOrganizationSpendLimit.php) |
| `deleteProjectApiKey` | `DELETE /organization/projects/{project_id}/api_keys/{api_key_id}` | `none` | [PHP](examples/administration/project-api-keys/deleteProjectApiKey.php) |
| `deleteProjectModelPermissions` | `DELETE /organization/projects/{project_id}/model_permissions` | `none` | [PHP](examples/administration/project-model-permissions/deleteProjectModelPermissions.php) |
| `deleteProjectRole` | `DELETE /projects/{project_id}/roles/{role_id}` | `none` | [PHP](examples/administration/project-roles/deleteProjectRole.php) |
| `deleteProjectServiceAccount` | `DELETE /organization/projects/{project_id}/service_accounts/{service_account_id}` | `none` | [PHP](examples/administration/project-service-accounts/deleteProjectServiceAccount.php) |
| `deleteProjectSpendAlert` | `DELETE /organization/projects/{project_id}/spend_alerts/{alert_id}` | `none` | [PHP](examples/administration/project-spend-alerts/deleteProjectSpendAlert.php) |
| `deleteProjectSpendLimit` | `DELETE /organization/projects/{project_id}/spend_limit` | `none` | [PHP](examples/administration/project-spend-limits/deleteProjectSpendLimit.php) |
| `deleteProjectUser` | `DELETE /organization/projects/{project_id}/users/{user_id}` | `none` | [PHP](examples/administration/project-users/deleteProjectUser.php) |
| `deleteUser` | `DELETE /organization/users/{user_id}` | `none` | [PHP](examples/administration/org-users/deleteUser.php) |
| `getAudioSpeechesUsage` | `GET /organization/usage/audio_speeches` | `none` | [PHP](examples/administration/usage/getAudioSpeechesUsage.php) |
| `getAudioTranscriptionsUsage` | `GET /organization/usage/audio_transcriptions` | `none` | [PHP](examples/administration/usage/getAudioTranscriptionsUsage.php) |
| `getCertificate` | `GET /organization/certificates/{certificate_id}` | `none` | [PHP](examples/administration/certificates/getCertificate.php) |
| `getCodeInterpreterSessionsUsage` | `GET /organization/usage/code_interpreter_sessions` | `none` | [PHP](examples/administration/usage/getCodeInterpreterSessionsUsage.php) |
| `getCompletionsUsage` | `GET /organization/usage/completions` | `none` | [PHP](examples/administration/usage/getCompletionsUsage.php) |
| `getCosts` | `GET /organization/costs` | `none` | [PHP](examples/administration/usage/getCosts.php) |
| `getEmbeddingsUsage` | `GET /organization/usage/embeddings` | `none` | [PHP](examples/administration/usage/getEmbeddingsUsage.php) |
| `getFileSearchCallsUsage` | `GET /organization/usage/file_search_calls` | `none` | [PHP](examples/administration/usage/getFileSearchCallsUsage.php) |
| `getImagesUsage` | `GET /organization/usage/images` | `none` | [PHP](examples/administration/usage/getImagesUsage.php) |
| `getModerationsUsage` | `GET /organization/usage/moderations` | `none` | [PHP](examples/administration/usage/getModerationsUsage.php) |
| `getVectorStoresUsage` | `GET /organization/usage/vector_stores` | `none` | [PHP](examples/administration/usage/getVectorStoresUsage.php) |
| `getWebSearchCallsUsage` | `GET /organization/usage/web_search_calls` | `none` | [PHP](examples/administration/usage/getWebSearchCallsUsage.php) |
| `listAdminApiKeys` | `GET /organization/admin_api_keys` | `none` | [PHP](examples/administration/admin-api-keys/listAdminApiKeys.php) |
| `listAuditLogs` | `GET /organization/audit_logs` | `none` | [PHP](examples/administration/audit-logs/listAuditLogs.php) |
| `listCertificates` | `GET /organization/certificates` | `none` | [PHP](examples/administration/certificates/listCertificates.php) |
| `listGroupRoles` | `GET /organization/groups/{group_id}/roles` | `none` | [PHP](examples/administration/org-groups/listGroupRoles.php) |
| `listGroupUsers` | `GET /organization/groups/{group_id}/users` | `none` | [PHP](examples/administration/org-groups/listGroupUsers.php) |
| `listGroups` | `GET /organization/groups` | `none` | [PHP](examples/administration/org-groups/listGroups.php) |
| `listInvites` | `GET /organization/invites` | `none` | [PHP](examples/administration/invites/listInvites.php) |
| `listOrganizationRoles` | `GET /organization/roles` | `none` | [PHP](examples/administration/org-roles/listOrganizationRoles.php) |
| `listOrganizationSpendAlerts` | `GET /organization/spend_alerts` | `none` | [PHP](examples/administration/org-spend-alerts/listOrganizationSpendAlerts.php) |
| `listProjectApiKeys` | `GET /organization/projects/{project_id}/api_keys` | `none` | [PHP](examples/administration/project-api-keys/listProjectApiKeys.php) |
| `listProjectCertificates` | `GET /organization/projects/{project_id}/certificates` | `none` | [PHP](examples/administration/certificates/listProjectCertificates.php) |
| `listProjectGroupRoles` | `GET /projects/{project_id}/groups/{group_id}/roles` | `none` | [PHP](examples/administration/project-groups/listProjectGroupRoles.php) |
| `listProjectGroups` | `GET /organization/projects/{project_id}/groups` | `none` | [PHP](examples/administration/project-groups/listProjectGroups.php) |
| `listProjectRateLimits` | `GET /organization/projects/{project_id}/rate_limits` | `none` | [PHP](examples/administration/rate-limits/listProjectRateLimits.php) |
| `listProjectRoles` | `GET /projects/{project_id}/roles` | `none` | [PHP](examples/administration/project-roles/listProjectRoles.php) |
| `listProjectServiceAccounts` | `GET /organization/projects/{project_id}/service_accounts` | `none` | [PHP](examples/administration/project-service-accounts/listProjectServiceAccounts.php) |
| `listProjectSpendAlerts` | `GET /organization/projects/{project_id}/spend_alerts` | `none` | [PHP](examples/administration/project-spend-alerts/listProjectSpendAlerts.php) |
| `listProjectUserRoles` | `GET /projects/{project_id}/users/{user_id}/roles` | `none` | [PHP](examples/administration/project-users/listProjectUserRoles.php) |
| `listProjectUsers` | `GET /organization/projects/{project_id}/users` | `none` | [PHP](examples/administration/project-users/listProjectUsers.php) |
| `listProjects` | `GET /organization/projects` | `none` | [PHP](examples/administration/projects/listProjects.php) |
| `listUserRoles` | `GET /organization/users/{user_id}/roles` | `none` | [PHP](examples/administration/org-users/listUserRoles.php) |
| `listUsers` | `GET /organization/users` | `none` | [PHP](examples/administration/org-users/listUsers.php) |
| `modifyCertificate` | `POST /organization/certificates/{certificate_id}` | `json` | [PHP](examples/administration/certificates/modifyCertificate.php) |
| `modifyProject` | `POST /organization/projects/{project_id}` | `json` | [PHP](examples/administration/projects/modifyProject.php) |
| `modifyProjectHostedToolPermissions` | `POST /organization/projects/{project_id}/hosted_tool_permissions` | `json` | [PHP](examples/administration/project-hosted-tool-permissions/modifyProjectHostedToolPermissions.php) |
| `modifyProjectModelPermissions` | `POST /organization/projects/{project_id}/model_permissions` | `json` | [PHP](examples/administration/project-model-permissions/modifyProjectModelPermissions.php) |
| `modifyProjectRateLimit` | `POST /organization/projects/{project_id}/rate_limits/{rate_limit_id}` | `json` | [PHP](examples/administration/rate-limits/modifyProjectRateLimit.php) |
| `modifyProjectUser` | `POST /organization/projects/{project_id}/users/{user_id}` | `json` | [PHP](examples/administration/project-users/modifyProjectUser.php) |
| `modifyUser` | `POST /organization/users/{user_id}` | `json` | [PHP](examples/administration/org-users/modifyUser.php) |
| `removeGroupUser` | `DELETE /organization/groups/{group_id}/users/{user_id}` | `none` | [PHP](examples/administration/org-groups/removeGroupUser.php) |
| `removeProjectGroup` | `DELETE /organization/projects/{project_id}/groups/{group_id}` | `none` | [PHP](examples/administration/project-groups/removeProjectGroup.php) |
| `retrieveAdminApiKey` | `GET /organization/admin_api_keys/{key_id}` | `none` | [PHP](examples/administration/admin-api-keys/retrieveAdminApiKey.php) |
| `retrieveGroup` | `GET /organization/groups/{group_id}` | `none` | [PHP](examples/administration/org-groups/retrieveGroup.php) |
| `retrieveGroupRole` | `GET /organization/groups/{group_id}/roles/{role_id}` | `none` | [PHP](examples/administration/org-groups/retrieveGroupRole.php) |
| `retrieveGroupUser` | `GET /organization/groups/{group_id}/users/{user_id}` | `none` | [PHP](examples/administration/org-groups/retrieveGroupUser.php) |
| `retrieveInvite` | `GET /organization/invites/{invite_id}` | `none` | [PHP](examples/administration/invites/retrieveInvite.php) |
| `retrieveOrganizationDataRetention` | `GET /organization/data_retention` | `none` | [PHP](examples/administration/org-data-retention/retrieveOrganizationDataRetention.php) |
| `retrieveOrganizationRole` | `GET /organization/roles/{role_id}` | `none` | [PHP](examples/administration/org-roles/retrieveOrganizationRole.php) |
| `retrieveOrganizationSpendAlert` | `GET /organization/spend_alerts/{alert_id}` | `none` | [PHP](examples/administration/org-spend-alerts/retrieveOrganizationSpendAlert.php) |
| `retrieveOrganizationSpendLimit` | `GET /organization/spend_limit` | `none` | [PHP](examples/administration/org-spend-limits/retrieveOrganizationSpendLimit.php) |
| `retrieveProject` | `GET /organization/projects/{project_id}` | `none` | [PHP](examples/administration/projects/retrieveProject.php) |
| `retrieveProjectApiKey` | `GET /organization/projects/{project_id}/api_keys/{api_key_id}` | `none` | [PHP](examples/administration/project-api-keys/retrieveProjectApiKey.php) |
| `retrieveProjectDataRetention` | `GET /organization/projects/{project_id}/data_retention` | `none` | [PHP](examples/administration/project-data-retention/retrieveProjectDataRetention.php) |
| `retrieveProjectGroup` | `GET /organization/projects/{project_id}/groups/{group_id}` | `none` | [PHP](examples/administration/project-groups/retrieveProjectGroup.php) |
| `retrieveProjectGroupRole` | `GET /projects/{project_id}/groups/{group_id}/roles/{role_id}` | `none` | [PHP](examples/administration/project-groups/retrieveProjectGroupRole.php) |
| `retrieveProjectHostedToolPermissions` | `GET /organization/projects/{project_id}/hosted_tool_permissions` | `none` | [PHP](examples/administration/project-hosted-tool-permissions/retrieveProjectHostedToolPermissions.php) |
| `retrieveProjectModelPermissions` | `GET /organization/projects/{project_id}/model_permissions` | `none` | [PHP](examples/administration/project-model-permissions/retrieveProjectModelPermissions.php) |
| `retrieveProjectRole` | `GET /projects/{project_id}/roles/{role_id}` | `none` | [PHP](examples/administration/project-roles/retrieveProjectRole.php) |
| `retrieveProjectServiceAccount` | `GET /organization/projects/{project_id}/service_accounts/{service_account_id}` | `none` | [PHP](examples/administration/project-service-accounts/retrieveProjectServiceAccount.php) |
| `retrieveProjectSpendAlert` | `GET /organization/projects/{project_id}/spend_alerts/{alert_id}` | `none` | [PHP](examples/administration/project-spend-alerts/retrieveProjectSpendAlert.php) |
| `retrieveProjectSpendLimit` | `GET /organization/projects/{project_id}/spend_limit` | `none` | [PHP](examples/administration/project-spend-limits/retrieveProjectSpendLimit.php) |
| `retrieveProjectUser` | `GET /organization/projects/{project_id}/users/{user_id}` | `none` | [PHP](examples/administration/project-users/retrieveProjectUser.php) |
| `retrieveProjectUserRole` | `GET /projects/{project_id}/users/{user_id}/roles/{role_id}` | `none` | [PHP](examples/administration/project-users/retrieveProjectUserRole.php) |
| `retrieveUser` | `GET /organization/users/{user_id}` | `none` | [PHP](examples/administration/org-users/retrieveUser.php) |
| `retrieveUserRole` | `GET /organization/users/{user_id}/roles/{role_id}` | `none` | [PHP](examples/administration/org-users/retrieveUserRole.php) |
| `unassignGroupRole` | `DELETE /organization/groups/{group_id}/roles/{role_id}` | `none` | [PHP](examples/administration/org-groups/unassignGroupRole.php) |
| `unassignProjectGroupRole` | `DELETE /projects/{project_id}/groups/{group_id}/roles/{role_id}` | `none` | [PHP](examples/administration/project-groups/unassignProjectGroupRole.php) |
| `unassignProjectUserRole` | `DELETE /projects/{project_id}/users/{user_id}/roles/{role_id}` | `none` | [PHP](examples/administration/project-users/unassignProjectUserRole.php) |
| `unassignUserRole` | `DELETE /organization/users/{user_id}/roles/{role_id}` | `none` | [PHP](examples/administration/org-users/unassignUserRole.php) |
| `updateGroup` | `POST /organization/groups/{group_id}` | `json` | [PHP](examples/administration/org-groups/updateGroup.php) |
| `updateOrganizationDataRetention` | `POST /organization/data_retention` | `json` | [PHP](examples/administration/org-data-retention/updateOrganizationDataRetention.php) |
| `updateOrganizationRole` | `POST /organization/roles/{role_id}` | `json` | [PHP](examples/administration/org-roles/updateOrganizationRole.php) |
| `updateOrganizationSpendAlert` | `POST /organization/spend_alerts/{alert_id}` | `json` | [PHP](examples/administration/org-spend-alerts/updateOrganizationSpendAlert.php) |
| `updateOrganizationSpendLimit` | `POST /organization/spend_limit` | `json` | [PHP](examples/administration/org-spend-limits/updateOrganizationSpendLimit.php) |
| `updateProjectDataRetention` | `POST /organization/projects/{project_id}/data_retention` | `json` | [PHP](examples/administration/project-data-retention/updateProjectDataRetention.php) |
| `updateProjectRole` | `POST /projects/{project_id}/roles/{role_id}` | `json` | [PHP](examples/administration/project-roles/updateProjectRole.php) |
| `updateProjectServiceAccount` | `POST /organization/projects/{project_id}/service_accounts/{service_account_id}` | `json` | [PHP](examples/administration/project-service-accounts/updateProjectServiceAccount.php) |
| `updateProjectSpendAlert` | `POST /organization/projects/{project_id}/spend_alerts/{alert_id}` | `json` | [PHP](examples/administration/project-spend-alerts/updateProjectSpendAlert.php) |
| `updateProjectSpendLimit` | `POST /organization/projects/{project_id}/spend_limit` | `json` | [PHP](examples/administration/project-spend-limits/updateProjectSpendLimit.php) |
| `uploadCertificate` | `POST /organization/certificates` | `json` | [PHP](examples/administration/certificates/uploadCertificate.php) |

### Agents (Beta)

The SDK automatically sends the required `OpenAI-Beta: agents=v1` header for these endpoints.

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createAgent` | `POST /agents` | `json` | [PHP](examples/agents/definitions/createAgent.php) |
| `createAgentEnvironmentFile` | `POST /agents/environments/{environment_id}/files` | `json` | [PHP](examples/agents/environments/createAgentEnvironmentFile.php) |
| `createAgentEnvironmentTemplate` | `POST /agents/environments/templates` | `json` | [PHP](examples/agents/environment-templates/createAgentEnvironmentTemplate.php) |
| `createAgentSession` | `POST /agents/sessions` | `json` | [PHP](examples/agents/sessions/createAgentSession.php) |
| `createAgentSessionEvents` | `POST /agents/sessions/{session_id}/events` | `json` | [PHP](examples/agents/sessions/createAgentSessionEvents.php) |
| `createVault` | `POST /vaults` | `json` | [PHP](examples/agents/vaults/createVault.php) |
| `createVaultCredential` | `POST /vaults/{vault_id}/credentials` | `json` | [PHP](examples/agents/vaults/createVaultCredential.php) |
| `deleteAgent` | `DELETE /agents/{agent_id}` | `none` | [PHP](examples/agents/definitions/deleteAgent.php) |
| `deleteAgentEnvironmentTemplate` | `DELETE /agents/environments/templates/{environment_template_id}` | `none` | [PHP](examples/agents/environment-templates/deleteAgentEnvironmentTemplate.php) |
| `deleteAgentSession` | `DELETE /agents/sessions/{session_id}` | `none` | [PHP](examples/agents/sessions/deleteAgentSession.php) |
| `deleteAgentSessionArtifact` | `DELETE /agents/sessions/{session_id}/artifacts/{artifact_id}` | `none` | [PHP](examples/agents/artifacts/deleteAgentSessionArtifact.php) |
| `deleteVault` | `DELETE /vaults/{vault_id}` | `none` | [PHP](examples/agents/vaults/deleteVault.php) |
| `deleteVaultCredential` | `DELETE /vaults/{vault_id}/credentials/{credential_id}` | `none` | [PHP](examples/agents/vaults/deleteVaultCredential.php) |
| `listAgentEnvironmentFiles` | `GET /agents/environments/{environment_id}/files` | `none` | [PHP](examples/agents/environments/listAgentEnvironmentFiles.php) |
| `listAgentEnvironmentTemplates` | `GET /agents/environments/templates` | `none` | [PHP](examples/agents/environment-templates/listAgentEnvironmentTemplates.php) |
| `listAgentSessionArtifacts` | `GET /agents/sessions/{session_id}/artifacts` | `none` | [PHP](examples/agents/artifacts/listAgentSessionArtifacts.php) |
| `listAgentSessionItems` | `GET /agents/sessions/{session_id}/items` | `none` | [PHP](examples/agents/sessions/listAgentSessionItems.php) |
| `listAgentSessionSubagentItems` | `GET /agents/sessions/{session_id}/subagents/{subagent_id}/items` | `none` | [PHP](examples/agents/subagents/listAgentSessionSubagentItems.php) |
| `listAgentSessionSubagentTurnItems` | `GET /agents/sessions/{session_id}/subagents/{subagent_id}/turns/{turn_id}/items` | `none` | [PHP](examples/agents/subagents/listAgentSessionSubagentTurnItems.php) |
| `listAgentSessionSubagentTurns` | `GET /agents/sessions/{session_id}/subagents/{subagent_id}/turns` | `none` | [PHP](examples/agents/subagents/listAgentSessionSubagentTurns.php) |
| `listAgentSessionSubagents` | `GET /agents/sessions/{session_id}/subagents` | `none` | [PHP](examples/agents/subagents/listAgentSessionSubagents.php) |
| `listAgentSessionTurns` | `GET /agents/sessions/{session_id}/turns` | `none` | [PHP](examples/agents/sessions/listAgentSessionTurns.php) |
| `listAgentSessions` | `GET /agents/sessions` | `none` | [PHP](examples/agents/sessions/listAgentSessions.php) |
| `listAgents` | `GET /agents` | `none` | [PHP](examples/agents/definitions/listAgents.php) |
| `listVaultCredentials` | `GET /vaults/{vault_id}/credentials` | `none` | [PHP](examples/agents/vaults/listVaultCredentials.php) |
| `listVaults` | `GET /vaults` | `none` | [PHP](examples/agents/vaults/listVaults.php) |
| `retrieveAgent` | `GET /agents/{agent_id}` | `none` | [PHP](examples/agents/definitions/retrieveAgent.php) |
| `retrieveAgentEnvironment` | `GET /agents/environments/{environment_id}` | `none` | [PHP](examples/agents/environments/retrieveAgentEnvironment.php) |
| `retrieveAgentEnvironmentTemplate` | `GET /agents/environments/templates/{environment_template_id}` | `none` | [PHP](examples/agents/environment-templates/retrieveAgentEnvironmentTemplate.php) |
| `retrieveAgentSession` | `GET /agents/sessions/{session_id}` | `none` | [PHP](examples/agents/sessions/retrieveAgentSession.php) |
| `retrieveAgentSessionArtifact` | `GET /agents/sessions/{session_id}/artifacts/{artifact_id}` | `none` | [PHP](examples/agents/artifacts/retrieveAgentSessionArtifact.php) |
| `retrieveAgentSessionArtifactContent` | `GET /agents/sessions/{session_id}/artifacts/{artifact_id}/content` | `none` | [PHP](examples/agents/artifacts/retrieveAgentSessionArtifactContent.php) |
| `retrieveAgentSessionSubagent` | `GET /agents/sessions/{session_id}/subagents/{subagent_id}` | `none` | [PHP](examples/agents/subagents/retrieveAgentSessionSubagent.php) |
| `retrieveAgentSessionSubagentTurn` | `GET /agents/sessions/{session_id}/subagents/{subagent_id}/turns/{turn_id}` | `none` | [PHP](examples/agents/subagents/retrieveAgentSessionSubagentTurn.php) |
| `retrieveAgentSessionTurn` | `GET /agents/sessions/{session_id}/turns/{turn_id}` | `none` | [PHP](examples/agents/sessions/retrieveAgentSessionTurn.php) |
| `retrieveVault` | `GET /vaults/{vault_id}` | `none` | [PHP](examples/agents/vaults/retrieveVault.php) |
| `retrieveVaultCredential` | `GET /vaults/{vault_id}/credentials/{credential_id}` | `none` | [PHP](examples/agents/vaults/retrieveVaultCredential.php) |
| `rotateVaultCredential` | `POST /vaults/{vault_id}/credentials/{credential_id}` | `json` | [PHP](examples/agents/vaults/rotateVaultCredential.php) |
| `streamAgentSessionEvents` | `GET /agents/sessions/{session_id}/events` | `none` | [PHP](examples/agents/sessions/streamAgentSessionEvents.php) |
| `updateAgent` | `POST /agents/{agent_id}` | `json` | [PHP](examples/agents/definitions/updateAgent.php) |
| `updateAgentEnvironmentTemplate` | `POST /agents/environments/templates/{environment_template_id}` | `json` | [PHP](examples/agents/environment-templates/updateAgentEnvironmentTemplate.php) |
| `updateAgentSession` | `POST /agents/sessions/{session_id}` | `json` | [PHP](examples/agents/sessions/updateAgentSession.php) |

### Audio

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createSpeech` | `POST /audio/speech` | `json` | [PHP](examples/audio/createSpeech.php) |
| `createTranscription` | `POST /audio/transcriptions` | `multipart` | [PHP](examples/audio/createTranscription.php) |
| `createTranslation` | `POST /audio/translations` | `multipart` | [PHP](examples/audio/createTranslation.php) |
| `createVoice` | `POST /audio/voices` | `multipart` | [PHP](examples/audio/createVoice.php) |
| `createVoiceConsent` | `POST /audio/voice_consents` | `multipart` | [PHP](examples/audio/createVoiceConsent.php) |
| `deleteVoiceConsent` | `DELETE /audio/voice_consents/{consent_id}` | `none` | [PHP](examples/audio/deleteVoiceConsent.php) |
| `listVoiceConsents` | `GET /audio/voice_consents` | `none` | [PHP](examples/audio/listVoiceConsents.php) |
| `retrieveVoiceConsent` | `GET /audio/voice_consents/{consent_id}` | `none` | [PHP](examples/audio/retrieveVoiceConsent.php) |
| `updateVoiceConsent` | `POST /audio/voice_consents/{consent_id}` | `json` | [PHP](examples/audio/updateVoiceConsent.php) |

### Batches

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `cancelBatch` | `POST /batches/{batch_id}/cancel` | `none` | [PHP](examples/batch/cancelBatch.php) |
| `createBatch` | `POST /batches` | `json` | [PHP](examples/batch/createBatch.php) |
| `listBatches` | `GET /batches` | `none` | [PHP](examples/batch/listBatches.php) |
| `retrieveBatch` | `GET /batches/{batch_id}` | `none` | [PHP](examples/batch/retrieveBatch.php) |

### Chat Completions

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createChatCompletion` | `POST /chat/completions` | `json` | [PHP](examples/chat/createChatCompletion.php) |
| `deleteChatCompletion` | `DELETE /chat/completions/{completion_id}` | `none` | [PHP](examples/chat/deleteChatCompletion.php) |
| `getChatCompletion` | `GET /chat/completions/{completion_id}` | `none` | [PHP](examples/chat/getChatCompletion.php) |
| `getChatMessages` | `GET /chat/completions/{completion_id}/messages` | `none` | [PHP](examples/chat/getChatMessages.php) |
| `listChatCompletions` | `GET /chat/completions` | `none` | [PHP](examples/chat/listChatCompletions.php) |
| `updateChatCompletion` | `POST /chat/completions/{completion_id}` | `json` | [PHP](examples/chat/updateChatCompletion.php) |

### ChatKit

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `cancelChatKitSession` | `POST /chatkit/sessions/{session_id}/cancel` | `none` | [PHP](examples/chatkit/cancelChatKitSession.php) |
| `createChatKitSession` | `POST /chatkit/sessions` | `json` | [PHP](examples/chatkit/createChatKitSession.php) |
| `deleteChatKitThread` | `DELETE /chatkit/threads/{thread_id}` | `none` | [PHP](examples/chatkit/deleteChatKitThread.php) |
| `listChatKitThreadItems` | `GET /chatkit/threads/{thread_id}/items` | `none` | [PHP](examples/chatkit/listChatKitThreadItems.php) |
| `listChatKitThreads` | `GET /chatkit/threads` | `none` | [PHP](examples/chatkit/listChatKitThreads.php) |
| `retrieveChatKitThread` | `GET /chatkit/threads/{thread_id}` | `none` | [PHP](examples/chatkit/retrieveChatKitThread.php) |

### Containers

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createContainer` | `POST /containers` | `json` | [PHP](examples/containers/createContainer.php) |
| `createContainerFile` | `POST /containers/{container_id}/files` | `multipart` | [PHP](examples/containers/createContainerFile.php) |
| `deleteContainer` | `DELETE /containers/{container_id}` | `none` | [PHP](examples/containers/deleteContainer.php) |
| `deleteContainerFile` | `DELETE /containers/{container_id}/files/{file_id}` | `none` | [PHP](examples/containers/deleteContainerFile.php) |
| `listContainerFiles` | `GET /containers/{container_id}/files` | `none` | [PHP](examples/containers/listContainerFiles.php) |
| `listContainers` | `GET /containers` | `none` | [PHP](examples/containers/listContainers.php) |
| `retrieveContainer` | `GET /containers/{container_id}` | `none` | [PHP](examples/containers/retrieveContainer.php) |
| `retrieveContainerFile` | `GET /containers/{container_id}/files/{file_id}` | `none` | [PHP](examples/containers/retrieveContainerFile.php) |
| `retrieveContainerFileContent` | `GET /containers/{container_id}/files/{file_id}/content` | `none` | [PHP](examples/containers/retrieveContainerFileContent.php) |

### Content Provenance

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createContentProvenanceCheck` | `POST /content_provenance_checks` | `multipart` | [PHP](examples/content-provenance/createContentProvenanceCheck.php) |

### Conversations

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createConversation` | `POST /conversations` | `json` | [PHP](examples/conversations/createConversation.php) |
| `createConversationItems` | `POST /conversations/{conversation_id}/items` | `json` | [PHP](examples/conversations/createConversationItems.php) |
| `deleteConversation` | `DELETE /conversations/{conversation_id}` | `none` | [PHP](examples/conversations/deleteConversation.php) |
| `deleteConversationItem` | `DELETE /conversations/{conversation_id}/items/{item_id}` | `none` | [PHP](examples/conversations/deleteConversationItem.php) |
| `listConversationItems` | `GET /conversations/{conversation_id}/items` | `none` | [PHP](examples/conversations/listConversationItems.php) |
| `retrieveConversation` | `GET /conversations/{conversation_id}` | `none` | [PHP](examples/conversations/retrieveConversation.php) |
| `retrieveConversationItem` | `GET /conversations/{conversation_id}/items/{item_id}` | `none` | [PHP](examples/conversations/retrieveConversationItem.php) |
| `updateConversation` | `POST /conversations/{conversation_id}` | `json` | [PHP](examples/conversations/updateConversation.php) |

### Embeddings

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createEmbedding` | `POST /embeddings` | `json` | [PHP](examples/embeddings/createEmbedding.php) |

### Evals

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `cancelEvalRun` | `POST /evals/{eval_id}/runs/{run_id}` | `none` | [PHP](examples/evals/runs/cancelEvalRun.php) |
| `createEval` | `POST /evals` | `json` | [PHP](examples/evals/createEval.php) |
| `createEvalRun` | `POST /evals/{eval_id}/runs` | `json` | [PHP](examples/evals/runs/createEvalRun.php) |
| `deleteEval` | `DELETE /evals/{eval_id}` | `none` | [PHP](examples/evals/deleteEval.php) |
| `deleteEvalRun` | `DELETE /evals/{eval_id}/runs/{run_id}` | `none` | [PHP](examples/evals/runs/deleteEvalRun.php) |
| `listEvalRunOutputItems` | `GET /evals/{eval_id}/runs/{run_id}/output_items` | `none` | [PHP](examples/evals/runs/listEvalRunOutputItems.php) |
| `listEvalRuns` | `GET /evals/{eval_id}/runs` | `none` | [PHP](examples/evals/runs/listEvalRuns.php) |
| `listEvals` | `GET /evals` | `none` | [PHP](examples/evals/listEvals.php) |
| `retrieveEval` | `GET /evals/{eval_id}` | `none` | [PHP](examples/evals/retrieveEval.php) |
| `retrieveEvalRun` | `GET /evals/{eval_id}/runs/{run_id}` | `none` | [PHP](examples/evals/runs/retrieveEvalRun.php) |
| `retrieveEvalRunOutputItem` | `GET /evals/{eval_id}/runs/{run_id}/output_items/{output_item_id}` | `none` | [PHP](examples/evals/runs/retrieveEvalRunOutputItem.php) |
| `updateEval` | `POST /evals/{eval_id}` | `json` | [PHP](examples/evals/updateEval.php) |

### Files

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `deleteFile` | `DELETE /files/{file_id}` | `none` | [PHP](examples/files/deleteFile.php) |
| `listFiles` | `GET /files` | `none` | [PHP](examples/files/listFiles.php) |
| `retrieveFile` | `GET /files/{file_id}` | `none` | [PHP](examples/files/retrieveFile.php) |
| `retrieveFileContent` | `GET /files/{file_id}/content` | `none` | [PHP](examples/files/retrieveFileContent.php) |
| `uploadFile` | `POST /files` | `multipart` | [PHP](examples/files/uploadFile.php) |

### Fine-Tuning (Deprecated)

These routes remain available for transitional compatibility and are deprecated upstream.

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `cancelFineTuning` (deprecated) | `POST /fine_tuning/jobs/{fine_tuning_job_id}/cancel` | `none` | [PHP](examples/fine-tuning/cancelFineTuning.php) |
| `createFineTuningCheckpointPermission` (deprecated) | `POST /fine_tuning/checkpoints/{fine_tuned_model_checkpoint}/permissions` | `json` | [PHP](examples/fine-tuning/createFineTuningCheckpointPermission.php) |
| `createFineTuningJob` (deprecated) | `POST /fine_tuning/jobs` | `json` | [PHP](examples/fine-tuning/createFineTuningJob.php) |
| `deleteFineTuningCheckpointPermission` (deprecated) | `DELETE /fine_tuning/checkpoints/{fine_tuned_model_checkpoint}/permissions/{permission_id}` | `none` | [PHP](examples/fine-tuning/deleteFineTuningCheckpointPermission.php) |
| `listFineTuningCheckpointPermissions` (deprecated) | `GET /fine_tuning/checkpoints/{fine_tuned_model_checkpoint}/permissions` | `none` | [PHP](examples/fine-tuning/listFineTuningCheckpointPermissions.php) |
| `listFineTuningCheckpoints` (deprecated) | `GET /fine_tuning/jobs/{fine_tuning_job_id}/checkpoints` | `none` | [PHP](examples/fine-tuning/listFineTuningCheckpoints.php) |
| `listFineTuningEvents` (deprecated) | `GET /fine_tuning/jobs/{fine_tuning_job_id}/events` | `none` | [PHP](examples/fine-tuning/listFineTuningEvents.php) |
| `listFineTuningJobs` (deprecated) | `GET /fine_tuning/jobs` | `none` | [PHP](examples/fine-tuning/listFineTuningJobs.php) |
| `pauseFineTuning` (deprecated) | `POST /fine_tuning/jobs/{fine_tuning_job_id}/pause` | `none` | [PHP](examples/fine-tuning/pauseFineTuning.php) |
| `resumeFineTuning` (deprecated) | `POST /fine_tuning/jobs/{fine_tuning_job_id}/resume` | `none` | [PHP](examples/fine-tuning/resumeFineTuning.php) |
| `retrieveFineTuningJob` (deprecated) | `GET /fine_tuning/jobs/{fine_tuning_job_id}` | `none` | [PHP](examples/fine-tuning/retrieveFineTuningJob.php) |
| `runFineTuningGrader` (deprecated) | `POST /fine_tuning/alpha/graders/run` | `json` | [PHP](examples/fine-tuning/runFineTuningGrader.php) |
| `validateFineTuningGrader` (deprecated) | `POST /fine_tuning/alpha/graders/validate` | `json` | [PHP](examples/fine-tuning/validateFineTuningGrader.php) |

### Images

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createImage` | `POST /images/generations` | `json` | [PHP](examples/images/createImage.php) |
| `createImageEdit` | `POST /images/edits` | `multipart` | [PHP](examples/images/createImageEdit.php) |

### Live

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `acceptLiveSession` | `POST /live/sessions/{session_id}/accept` | `json` | [PHP](examples/live/acceptLiveSession.php) |
| `createLiveSession` | `POST /live/sessions` | `json` | [PHP](examples/live/createLiveSession.php) |
| `downloadLiveSessionRecording` | `GET /live/sessions/{session_id}/content` | `none` | [PHP](examples/live/downloadLiveSessionRecording.php) |
| `forkLiveSession` | `POST /live/sessions/{session_id}/fork` | `json` | [PHP](examples/live/forkLiveSession.php) |
| `hangupLiveSession` | `POST /live/sessions/{session_id}/hangup` | `none` | [PHP](examples/live/hangupLiveSession.php) |
| `referLiveSession` | `POST /live/sessions/{session_id}/refer` | `json` | [PHP](examples/live/referLiveSession.php) |
| `rejectLiveSession` | `POST /live/sessions/{session_id}/reject` | `json` | [PHP](examples/live/rejectLiveSession.php) |

### Models

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `deleteModel` | `DELETE /models/{model}` | `none` | [PHP](examples/models/deleteModel.php) |
| `listModels` | `GET /models` | `none` | [PHP](examples/models/listModels.php) |
| `retrieveModel` | `GET /models/{model}` | `none` | [PHP](examples/models/retrieveModel.php) |

### Moderations

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createModeration` | `POST /moderations` | `json` | [PHP](examples/moderations/createModeration.php) |

### Realtime

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `acceptRealtimeCall` | `POST /realtime/calls/{call_id}/accept` | `json` | [PHP](examples/realtime/acceptRealtimeCall.php) |
| `createRealtimeCall` | `POST /realtime/calls` | `multipart` | [PHP](examples/realtime/createRealtimeCall.php) |
| `createRealtimeClientSecret` | `POST /realtime/client_secrets` | `json` | [PHP](examples/realtime/createRealtimeClientSecret.php) |
| `createRealtimeTranslationClientSecret` | `POST /realtime/translations/client_secrets` | `json` | [PHP](examples/realtime/createRealtimeTranslationClientSecret.php) |
| `hangupRealtimeCall` | `POST /realtime/calls/{call_id}/hangup` | `none` | [PHP](examples/realtime/hangupRealtimeCall.php) |
| `referRealtimeCall` | `POST /realtime/calls/{call_id}/refer` | `json` | [PHP](examples/realtime/referRealtimeCall.php) |
| `rejectRealtimeCall` | `POST /realtime/calls/{call_id}/reject` | `json` | [PHP](examples/realtime/rejectRealtimeCall.php) |

### Responses

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `cancelResponse` | `POST /responses/{response_id}/cancel` | `none` | [PHP](examples/responses/cancelResponse.php) |
| `compactResponse` | `POST /responses/compact` | `json` | [PHP](examples/responses/compactResponse.php) |
| `countResponseInputTokens` | `POST /responses/input_tokens` | `json` | [PHP](examples/responses/countResponseInputTokens.php) |
| `createResponse` | `POST /responses` | `json` | [PHP](examples/responses/createResponse.php) |
| `deleteResponse` | `DELETE /responses/{response_id}` | `none` | [PHP](examples/responses/deleteResponse.php) |
| `getResponse` | `GET /responses/{response_id}` | `none` | [PHP](examples/responses/getResponse.php) |
| `listInputItems` | `GET /responses/{response_id}/input_items` | `none` | [PHP](examples/responses/listInputItems.php) |

### Responses (Beta Schema)

These methods select the distinct beta schema by adding `?beta=true`; stable Responses methods are unchanged.

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `cancelBetaResponse` | `POST /responses/{response_id}/cancel?beta=true` | `none` | [PHP](examples/responses/beta/cancelBetaResponse.php) |
| `compactBetaResponse` | `POST /responses/compact?beta=true` | `json` | [PHP](examples/responses/beta/compactBetaResponse.php) |
| `countBetaResponseInputTokens` | `POST /responses/input_tokens?beta=true` | `json` | [PHP](examples/responses/beta/countBetaResponseInputTokens.php) |
| `createBetaResponse` | `POST /responses?beta=true` | `json` | [PHP](examples/responses/beta/createBetaResponse.php) |
| `deleteBetaResponse` | `DELETE /responses/{response_id}?beta=true` | `none` | [PHP](examples/responses/beta/deleteBetaResponse.php) |
| `getBetaResponse` | `GET /responses/{response_id}?beta=true` | `none` | [PHP](examples/responses/beta/getBetaResponse.php) |
| `listBetaResponseInputItems` | `GET /responses/{response_id}/input_items?beta=true` | `none` | [PHP](examples/responses/beta/listBetaResponseInputItems.php) |

### Safety

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `retrieveSafetyAlert` | `GET /safety/alerts/{id}` | `none` | [PHP](examples/safety/retrieveSafetyAlert.php) |

### Skills

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createSkill` | `POST /skills` | `multipart` | [PHP](examples/skills/createSkill.php) |
| `createSkillVersion` | `POST /skills/{skill_id}/versions` | `multipart` | [PHP](examples/skills/createSkillVersion.php) |
| `deleteSkill` | `DELETE /skills/{skill_id}` | `none` | [PHP](examples/skills/deleteSkill.php) |
| `deleteSkillVersion` | `DELETE /skills/{skill_id}/versions/{version}` | `none` | [PHP](examples/skills/deleteSkillVersion.php) |
| `listSkillVersions` | `GET /skills/{skill_id}/versions` | `none` | [PHP](examples/skills/listSkillVersions.php) |
| `listSkills` | `GET /skills` | `none` | [PHP](examples/skills/listSkills.php) |
| `retrieveSkill` | `GET /skills/{skill_id}` | `none` | [PHP](examples/skills/retrieveSkill.php) |
| `retrieveSkillContent` | `GET /skills/{skill_id}/content` | `none` | [PHP](examples/skills/retrieveSkillContent.php) |
| `retrieveSkillVersion` | `GET /skills/{skill_id}/versions/{version}` | `none` | [PHP](examples/skills/retrieveSkillVersion.php) |
| `retrieveSkillVersionContent` | `GET /skills/{skill_id}/versions/{version}/content` | `none` | [PHP](examples/skills/retrieveSkillVersionContent.php) |
| `updateSkill` | `POST /skills/{skill_id}` | `json` | [PHP](examples/skills/updateSkill.php) |

### Uploads

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `addUploadPart` | `POST /uploads/{upload_id}/parts` | `multipart` | [PHP](examples/uploads/addUploadPart.php) |
| `cancelUpload` | `POST /uploads/{upload_id}/cancel` | `none` | [PHP](examples/uploads/cancelUpload.php) |
| `completeUpload` | `POST /uploads/{upload_id}/complete` | `json` | [PHP](examples/uploads/completeUpload.php) |
| `createUpload` | `POST /uploads` | `json` | [PHP](examples/uploads/createUpload.php) |

### Vector Stores

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `cancelVectorStoreFileBatch` | `POST /vector_stores/{vector_store_id}/file_batches/{batch_id}/cancel` | `none` | [PHP](examples/vector-store-file-batches/cancelVectorStoreFileBatch.php) |
| `createVectorStore` | `POST /vector_stores` | `json` | [PHP](examples/vector-stores/createVectorStore.php) |
| `createVectorStoreFile` | `POST /vector_stores/{vector_store_id}/files` | `json` | [PHP](examples/vector-store-files/createVectorStoreFile.php) |
| `createVectorStoreFileBatch` | `POST /vector_stores/{vector_store_id}/file_batches` | `json` | [PHP](examples/vector-store-file-batches/createVectorStoreFileBatch.php) |
| `deleteVectorStore` | `DELETE /vector_stores/{vector_store_id}` | `none` | [PHP](examples/vector-stores/deleteVectorStore.php) |
| `deleteVectorStoreFile` | `DELETE /vector_stores/{vector_store_id}/files/{file_id}` | `none` | [PHP](examples/vector-store-files/deleteVectorStoreFile.php) |
| `listVectorStoreFiles` | `GET /vector_stores/{vector_store_id}/files` | `none` | [PHP](examples/vector-store-files/listVectorStoreFiles.php) |
| `listVectorStoreFilesInBatch` | `GET /vector_stores/{vector_store_id}/file_batches/{batch_id}/files` | `none` | [PHP](examples/vector-store-file-batches/listVectorStoreFilesInBatch.php) |
| `listVectorStores` | `GET /vector_stores` | `none` | [PHP](examples/vector-stores/listVectorStores.php) |
| `modifyVectorStore` | `POST /vector_stores/{vector_store_id}` | `json` | [PHP](examples/vector-stores/modifyVectorStore.php) |
| `retrieveVectorStore` | `GET /vector_stores/{vector_store_id}` | `none` | [PHP](examples/vector-stores/retrieveVectorStore.php) |
| `retrieveVectorStoreFile` | `GET /vector_stores/{vector_store_id}/files/{file_id}` | `none` | [PHP](examples/vector-store-files/retrieveVectorStoreFile.php) |
| `retrieveVectorStoreFileBatch` | `GET /vector_stores/{vector_store_id}/file_batches/{batch_id}` | `none` | [PHP](examples/vector-store-file-batches/retrieveVectorStoreFileBatch.php) |
| `retrieveVectorStoreFileContent` | `GET /vector_stores/{vector_store_id}/files/{file_id}/content` | `none` | [PHP](examples/vector-store-files/retrieveVectorStoreFileContent.php) |
| `searchVectorStore` | `POST /vector_stores/{vector_store_id}/search` | `json` | [PHP](examples/vector-stores/searchVectorStore.php) |
| `updateVectorStoreFileAttributes` | `POST /vector_stores/{vector_store_id}/files/{file_id}` | `json` | [PHP](examples/vector-store-files/updateVectorStoreFileAttributes.php) |

### Videos

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createVideo` | `POST /videos` | `json` | [PHP](examples/videos/createVideo.php) |
| `createVideoCharacter` | `POST /videos/characters` | `multipart` | [PHP](examples/videos/createVideoCharacter.php) |
| `createVideoEdit` | `POST /videos/edits` | `json` | [PHP](examples/videos/createVideoEdit.php) |
| `createVideoExtension` | `POST /videos/extensions` | `json` | [PHP](examples/videos/createVideoExtension.php) |
| `createVideoRemix` | `POST /videos/{video_id}/remix` | `json` | [PHP](examples/videos/createVideoRemix.php) |
| `deleteVideo` | `DELETE /videos/{video_id}` | `none` | [PHP](examples/videos/deleteVideo.php) |
| `downloadVideoContent` | `GET /videos/{video_id}/content` | `none` | [PHP](examples/videos/downloadVideoContent.php) |
| `listVideos` | `GET /videos` | `none` | [PHP](examples/videos/listVideos.php) |
| `retrieveVideo` | `GET /videos/{video_id}` | `none` | [PHP](examples/videos/retrieveVideo.php) |
| `retrieveVideoCharacter` | `GET /videos/characters/{character_id}` | `none` | [PHP](examples/videos/retrieveVideoCharacter.php) |

## Custom API Origin

`origin` accepts either a hostname or an absolute base URL. A hostname uses `/v1`; an absolute URL keeps its path unless
`basePath` is supplied explicitly.

```php
$openAI = new OpenAI(
    requestFactory: $factory,
    streamFactory: $factory,
    uriFactory: $factory,
    httpClient: new Client(['stream' => true]),
    apiKey: (string) getenv('OPENAI_API_KEY'),
    origin: 'https://gateway.example/openai/v1',
);
```

## Deprecated And Removed APIs

Version 4 deliberately does not expose APIs that OpenAI classifies as legacy or has removed: classic Completions,
Assistants/Threads/Runs/Messages, deprecated Realtime session-token routes, and DALL-E image variations. Use Responses
and Conversations instead of Assistants, and Realtime client-secret methods instead of the old session-token routes.

The self-serve fine-tuning routes remain temporarily available and are marked deprecated because eligible existing
customers can still use them during OpenAI's transition. See OpenAI's
[deprecation schedule](https://developers.openai.com/api/docs/deprecations) and the [v4 migration notes](CHANGELOG.md)
before upgrading.

## Development

```bash
composer test
composer analyse
vendor/bin/php-cs-fixer fix --dry-run --diff
composer audit
```

## License

[ISC](LICENSE.md)
