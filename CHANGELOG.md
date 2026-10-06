# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 4.2.0 - 2026-10-06

### Added

- Added Decisions, Safety Cases, webhook endpoint and event-type APIs, organization external-storage management, Agents
  session traces and turn items, and workload identity token exchange endpoints.
- Added runnable examples for every new SDK method, including mTLS certificate configuration for X.509 workload
  identity federation.

### Changed

- Corrected `cancelEvalRun` to use `POST /evals/{eval_id}/runs/{run_id}/cancel`.
- Updated the README endpoint catalog and development dependencies for the current OpenAI API reference and PHP
  toolchain.
- Replaced the automatic commit-message release workflow with a manually dispatched, version-validated release that
  reuses the complete validation suite and refuses duplicate tags or missing changelog entries.
- Validation now tests PHP 8.1 with the lowest supported dependencies and PHP 8.5 with current dependencies. PHP 8.6
  remains a non-blocking experimental job while it is under development.
- Validation now enforces Composer metadata, dependency auditing, coding style, static analysis, and 100% statement,
  method, and element coverage before a release can be created.
- Removed the static Composer package version so releases derive their version exclusively from immutable Git tags.
- Updated the README status badge to report the actively used release workflow.
- Updated the README Responses and Agents examples for the current GPT-6 model family and made raw Responses output
  handling robust when non-message output items precede generated text.

### Removed

- Removed the Videos API methods, examples, and fixture after OpenAI shut down the API on September 24, 2026. This is
  a breaking change for consumers that called `createVideo`, `createVideoCharacter`, `retrieveVideoCharacter`,
  `listVideos`, `retrieveVideo`, `deleteVideo`, `downloadVideoContent`, `createVideoEdit`, `createVideoExtension`, or
  `createVideoRemix`.

## [4.1.0] - 2026-09-12

### Added

- Added 88 current API routes for Agents, Evals, Live sessions, Videos, beta Responses, content provenance checks, safety alerts,
  Realtime call creation, organization and project spend limits, and project service-account API keys.
- Added automatic endpoint headers, including `OpenAI-Beta: agents=v1` for the beta Agents API.
- Added endpoint-declared streaming so Agents session event streams use `StreamingClientInterface` without a request
  body flag.
- Added one runnable PHP example for every new method, grouped by API resource, plus valid SDP and MP4 fixtures for
  Realtime, Live, and Video upload examples.

### Changed

- Updated the README endpoint catalog, feature documentation, examples, and IDE method annotations for the current
  OpenAI API reference.
- Updated the PHP CS Fixer, Guzzle, PHPStan, PHPUnit, and dotenv development constraints while preserving dependency
  resolution on PHP 8.1 through PHP 8.5.
- Multipart endpoint metadata can now explicitly identify that all fields are scalar protocol data rather than local
  file paths, as required by Realtime SDP calls.
- Corrected the Agents environment-template, environment-file, and vault-rotation examples, the administration
  spend-limit enum values, and the Realtime/Live SDP fixture based on live API verification.

### Compatibility

- Version 4.1 is backward compatible with the public 4.0 API. Classic Completions, legacy Assistants routes, and
  DALL-E 2 image variations remain intentionally absent. The nonfunctional legacy Realtime session-token routes remain
  absent; use the Realtime client-secret endpoints instead.

## [4.0.2] - 2026-07-14

### Added

- Added `StreamingClientInterface` for PSR-18 transports that can expose an HTTP response body before the complete
  response has been received.

### Changed

- Streaming endpoint calls now use `StreamingClientInterface::sendStreamingRequest()` when the configured transport
  supports it. Existing `ClientInterface` implementations continue to use `sendRequest()` unchanged.
- Improved SSE decoding for progressively delivered responses. The decoder consumes bytes already available from the
  stream and otherwise waits for the next byte, preventing small SSE frames from being delayed until a larger buffer
  is filled.

## [4.0.0] - 2026-07-10

Version 4 realigns the SDK with the current OpenAI API reference and intentionally removes APIs that are legacy,
already unavailable, or scheduled for shutdown. The endpoint catalog contains 223 supported routes.

### Added

- Added 119 SDK methods for the current Responses, Conversations, ChatKit, Containers, Skills, Realtime REST control,
  custom voice, voice consent, fine-tuning transition, and organization/project administration APIs.
- Added one PHP example for every registered SDK method and a complete [`README.md`](README.md#supported-methods)
  catalog containing the HTTP verb, route, request encoding, deprecation state, and example link.
- Added `OpenAI::request()` for runtime-selected endpoint names and explicit custom headers.
- Added `OpenAI-Project` request scoping through the optional final `$project` constructor argument.
- Added `WebhookVerifier`, `WebhookException`, and `InvalidWebhookSignatureException` for Standard Webhooks signature
  verification, replay protection, secret rotation, and verified JSON decoding.
- Added structured `OpenAIException` metadata through `getError()`, `getRequestId()`, `getResponseBody()`, and
  `getResponseHeaders()`.
- Added full absolute base-URL support while retaining hostname and `basePath` configuration.
- Added magic-method annotations so IDEs and static analyzers discover all registered endpoint methods.
- Added CI coverage for PHP 8.1, 8.2, 8.3, 8.4, and 8.5, plus PHPStan and dependency auditing.

### Changed

- Set the package version to `4.0.0` and require PHP 8.1 or newer.
- Fixed the documented body-first calling convention. Calls such as `createResponse($body)` now send the array as the
  JSON body. The v3 two-array form `method($pathOrQuery, $body)` remains supported.
- Path-and-body calls may now use one combined array; fields matching route placeholders are split into the URL.
- URL path values are encoded with `rawurlencode()` and query strings use RFC 3986 encoding.
- Corrected `modifyProjectRateLimit` from `GET` to `POST`.
- Corrected the required path keys from `cert_id` to `certificate_id` for `getCertificate`, and from `key_id` to
  `api_key_id` for `retrieveProjectApiKey` and `deleteProjectApiKey`.
- Replaced the line-oriented streaming parser with a chunk-safe SSE decoder. A callback no longer discards an ordinary
  JSON response when streaming was not requested.
- Replaced in-memory/base64 multipart assembly with raw, streamed file copies and endpoint-specific upload fields.
- Updated examples to current model families such as `gpt-5.4-mini`, `gpt-image-2`, and
  `text-embedding-3-small`.
- Updated PSR, Guzzle, PHPUnit, PHPStan, PHP CS Fixer, and dotenv dependency constraints to maintained versions.
- Moved vector-store examples from `examples/assistants/` to their top-level endpoint directories.

### Deprecated

- The 13 self-serve fine-tuning methods remain registered for eligible existing customers but are marked deprecated.
  OpenAI has announced staged access restrictions and an end to new fine-tuning job creation for active existing
  customers on January 6, 2027.

### Removed

- Removed `createImageVariation`; the endpoint depended on DALL-E 2, which OpenAI removed on May 12, 2026.
- Removed these Assistants API methods in favor of Responses and Conversations:
  `createAssistant`, `listAssistants`, `retrieveAssistant`, `modifyAssistant`, `deleteAssistant`, `createThread`,
  `retrieveThread`, `modifyThread`, `deleteThread`, `createMessage`, `listMessages`, `retrieveMessage`,
  `modifyMessage`, `deleteMessage`, `createRun`, `createThreadAndRun`, `listRuns`, `retrieveRun`, `modifyRun`,
  `submitToolOutputsToRun`, `cancelRun`, `listRunSteps`, and `retrieveRunStep`.
- Deliberately omitted classic Completions, Realtime Beta session creation, Evals, Videos, and other endpoints already
  classified as legacy or scheduled for shutdown by OpenAI.
- Removed all `OpenAI-Beta` headers from examples.

### Migration

- Replace Assistants/Threads/Runs/Messages workflows with `createResponse` and the Conversations methods.
- Replace `createImageVariation` with `createImage` or `createImageEdit` using `gpt-image-2`.
- Rename the three corrected path parameters described above.
- Keep existing two-array endpoint calls unchanged, or adopt the preferred body-first/combined-array forms documented
  in [`README.md`](README.md).
- Review the complete method list in [`README.md`](README.md#supported-methods) and OpenAI's
  [deprecation schedule](https://developers.openai.com/api/docs/deprecations).

## [3.1.0] - 2025-04-17

### Added

- **New Endpoints**:
    - **Responses**:
        - `createResponse`: Creates a response object.
        - `getResponse`: Retrieves a response by its ID.
        - `deleteResponse`: Deletes a response.
        - `listInputItems`: Lists input items for a response.
    - **Chat Completions**:
        - `getChatCompletion`: Retrieves a chat completion by its ID.
        - `getChatMessages`: Retrieves messages for a chat completion.
        - `listChatCompletions`: Lists chat completions.
        - `updateChatCompletion`: Updates a chat completion.
        - `deleteChatCompletion`: Deletes a chat completion.
    - **Admin API Keys**:
        - `listAdminApiKeys`: Lists all admin API keys.
        - `createAdminApiKey`: Creates an admin API key.
        - `retrieveAdminApiKey`: Retrieves an admin API key.
        - `deleteAdminApiKey`: Deletes an admin API key.
    - **Certificates**:
        - `uploadCertificate`: Uploads a certificate.
        - `listCertificates`: Lists certificates in the organization.
        - `getCertificate`: Retrieves a certificate by ID.
        - `modifyCertificate`: Modifies a certificate.
        - `deleteCertificate`: Deletes a certificate.
        - `listProjectCertificates`: Lists certificates within a project.
        - `activateCertificates`: Activates certificates.
        - `deactivateCertificates`: Deactivates certificates.
        - `activateProjectCertificates`: Activates certificates for a project.
        - `deactivateProjectCertificates`: Deactivates certificates for a project.
    - **Rate Limits**:
        - `listProjectRateLimits`: Lists rate limits for a project.
        - `modifyProjectRateLimit`: Modifies a project's rate limit.
    - **Usage**:
        - `getCompletionsUsage`: Retrieves usage metrics for completions.
        - `getEmbeddingsUsage`: Retrieves usage metrics for embeddings.
        - `getModerationsUsage`: Retrieves usage metrics for moderations.
        - `getImagesUsage`: Retrieves usage metrics for images.
        - `getAudioSpeechesUsage`: Retrieves usage metrics for audio speeches.
        - `getVectorStoresUsage`: Retrieves usage metrics for vector stores.
        - `getCosts`: Retrieves cost data for the organization.
  - **Vector Stores**:
      - `searchVectorStore`: Search a vector store for relevant chunks based on a query and file attributes filter.
  - **Vector Store Files**:
      - `retrieveVectorStoreFileContent`: Retrieve the parsed contents of a vector store file.
      - `updateVectorStoreFileContent`: Update attributes on a vector store file.

- **New Examples**:
    - **Responses**:
        - `examples/responses/createResponse.php`
        - `examples/responses/getResponse.php`
        - `examples/responses/deleteResponse.php`
        - `examples/responses/listInputItems.php`
    - **Chat Completions**:
        - `examples/chat/getChatCompletion.php`
        - `examples/chat/getChatMessages.php`
        - `examples/chat/listChatCompletions.php`
        - `examples/chat/updateChatCompletion.php`
        - `examples/chat/deleteChatCompletion.php`
    - **Admin API Keys**:
        - `examples/administration/admin-api-keys/listAdminApiKeys.php`
        - `examples/administration/admin-api-keys/createAdminApiKey.php`
        - `examples/administration/admin-api-keys/retrieveAdminApiKey.php`
        - `examples/administration/admin-api-keys/deleteAdminApiKey.php`
    - **Certificates**:
        - `examples/administration/certificates/uploadCertificate.php`
        - `examples/administration/certificates/listCertificates.php`
        - `examples/administration/certificates/getCertificate.php`
        - `examples/administration/certificates/modifyCertificate.php`
        - `examples/administration/certificates/deleteCertificate.php`
        - `examples/administration/certificates/listProjectCertificates.php`
        - `examples/administration/certificates/activateCertificates.php`
        - `examples/administration/certificates/deactivateCertificates.php`
        - `examples/administration/certificates/activateProjectCertificates.php`
        - `examples/administration/certificates/deactivateProjectCertificates.php`
    - **Rate Limits**:
        - `examples/administration/rate-limits/listProjectRateLimits.php`
        - `examples/administration/rate-limits/modifyProjectRateLimit.php`
    - **Usage**:
        - `examples/administration/usage/getCompletionsUsage.php`
        - `examples/administration/usage/getEmbeddingsUsage.php`
        - `examples/administration/usage/getModerationsUsage.php`
        - `examples/administration/usage/getImagesUsage.php`
        - `examples/administration/usage/getAudioSpeechesUsage.php`
        - `examples/administration/usage/getVectorStoresUsage.php`
        - `examples/administration/usage/getCosts.php`
    - **Vector Stores**:
        - `examples/assistants/vector-stores/searchVectorStore.php`
    - **Vector Store Files**:
        - `examples/assistants/vector-store-files/retrieveVectorStoreFileContent.php`
        - `examples/assistants/vector-store-files/updateVectorStoreFileAttributes.php`

### Changed

- Renamed `listBatch` to `listBatches`
- Updated `OpenAIFactory` to read configuration from .env file, instead of requiring to modify the file to run examples
- Fixed `OpenAI::callAPI()` to turn GET options into query strings, split path vs. query parameters, and clear body opts

## [3.0.0] - 2024-10-10

### Removed

- Dropped support for PHP 7.4. **PHP 8.1 or higher is now required**.
- Parameter `apiVersion` has been removed in favor of `basePath` in `OpenAIUrlBuilder::create()`.

### Added

- **Streaming Support**:
    - Added support for streaming responses in the `OpenAI` class, allowing real-time token generation for the `createChatCompletion` method and other applicable endpoints.
    - Implemented a callback mechanism for handling streamed data in real time.

- **New Endpoints**:
    - **Models**:
        - `retrieveModel`: Retrieve information about a specific model by its ID.
        - `deleteModel`: Delete a fine-tuned model by its ID.
    - **Files**:
        - `uploadFile`: Upload a file that can be used across various endpoints.
        - `listFiles`: Retrieve a list of all uploaded files.
        - `retrieveFile`: Retrieve details of a specific file by its ID.
        - `deleteFile`: Delete a file by its ID.
        - `retrieveFileContent`: Retrieve the contents of a specific file.
    - **Fine-Tuning Jobs**:
        - `listFineTuningJobs`: Get a list of all fine-tuning jobs.
        - `retrieveFineTuningJob`: Retrieve details of a specific fine-tuning job by its ID.
        - `cancelFineTuningJob`: Cancel a fine-tuning job.
        - `createFineTuningJob`: Create a new fine-tuning job.
    - **Vector Stores**:
        - `createVectorStore`: Create a vector store.
        - `listVectorStores`: List all vector stores.
        - `retrieveVectorStore`: Retrieve a vector store by ID.
        - `modifyVectorStore`: Modify a vector store.
        - `deleteVectorStore`: Delete a vector store.
        - `createVectorStoreFile`: Create a vector store file by attaching a file to a vector store.
        - `listVectorStoreFiles`: List all vector store files.
        - `retrieveVectorStoreFile`: Retrieve a vector store file by ID.
        - `deleteVectorStoreFile`: Delete a vector store file by ID.
        - `createVectorStoreFileBatch`: Create a vector store file batch.
        - `retrieveVectorStoreFileBatch`: Retrieve a vector store file batch by ID.
        - `cancelVectorStoreFileBatch`: Cancel a vector store file batch.
        - `listVectorStoreFilesInBatch`: List all vector store files in a batch.
    - **Assistants**:
        - `createAssistant`: Create an assistant with a model and instructions.
        - `listAssistants`: List all assistants.
        - `retrieveAssistant`: Retrieve a specific assistant by ID.
        - `modifyAssistant`: Modify an existing assistant.
        - `deleteAssistant`: Delete an assistant by ID.
    - **Threads**:
        - `createThread`: Create a thread.
        - `retrieveThread`: Retrieve a thread by ID.
        - `modifyThread`: Modify a thread.
        - `deleteThread`: Delete a thread.
    - **Messages**:
        - `createMessage`: Create a message within a thread.
        - `listMessages`: List all messages within a thread.
        - `retrieveMessage`: Retrieve a specific message by ID.
        - `modifyMessage`: Modify a message.
        - `deleteMessage`: Delete a message by ID.
    - **Runs**:
        - `createRun`: Create a run.
        - `createThreadAndRun`: Create a thread and run it in one request.
        - `listRuns`: List all runs within a thread.
        - `retrieveRun`: Retrieve a specific run by ID.
        - `modifyRun`: Modify a run.
        - `submitToolOutputsToRun`: Submit tool outputs to a run.
        - `cancelRun`: Cancel a run in progress.
    - **Run Steps**:
        - `listRunSteps`: List all run steps within a run.
        - `retrieveRunStep`: Retrieve a specific run step by ID.
    - **API Keys**:
        - `listProjectApiKeys`: List all API keys within a project.
        - `retrieveProjectApiKey`: Retrieve a specific API key by ID.
        - `deleteProjectApiKey`: Delete an API key by ID.
    - **Service Accounts**:
        - `listProjectServiceAccounts`: List all service accounts within a project.
        - `createProjectServiceAccount`: Create a new service account within a project.
        - `retrieveProjectServiceAccount`: Retrieve a specific service account by ID.
        - `deleteProjectServiceAccount`: Delete a service account by ID.
    - **Users and Invites**:
        - `listUsers`: List all users in the organization.
        - `modifyUser`: Modify a user's role in the organization.
        - `retrieveUser`: Retrieve a user by ID.
        - `deleteUser`: Delete a user from the organization.
        - `listInvites`: List all invites in the organization.
        - `createInvite`: Create an invite for a user to the organization.
        - `retrieveInvite`: Retrieve a specific invite by ID.
        - `deleteInvite`: Delete an invite by ID.
    - **Projects**:
        - `listProjects`: List all projects in the organization.
        - `createProject`: Create a new project in the organization.
        - `retrieveProject`: Retrieve a specific project by ID.
        - `modifyProject`: Modify a project in the organization.
        - `archiveProject`: Archive a project in the organization.
    - **Audit Logs**:
        - `listAuditLogs`: List user actions and configuration changes within the organization.

- **New Examples**:
    - Created new example files to showcase API usage and functionality:
        - **Chat Completion with Streaming**: `examples/assistants/chat/createChatCompletion.php`
            - Demonstrates how to create a chat completion with the `gpt-4` model, featuring real-time response streaming.
        - **Retrieve Model**: `examples/models/retrieveModel.php`
            - Demonstrates how to retrieve detailed information about a specific model using the `retrieveModel` endpoint.
        - **Delete Model**: `examples/models/deleteModel.php`
            - Shows how to delete a specific model using the `deleteModel` endpoint.
        - **Archive/Unarchive Model**:
            - `examples/models/archiveModel.php`
            - `examples/models/unarchiveModel.php`
            - These examples show how to archive and unarchive models respectively.
        - **Files Management**:
            - **Upload File**: `examples/assistants/vector-store-files/uploadFile.php`
            - **List Files**: `examples/assistants/vector-store-files/listFiles.php`
            - **Retrieve File**: `examples/assistants/vector-store-files/retrieveFile.php`
            - **Delete File**: `examples/assistants/vector-store-files/deleteFile.php`
        - **Fine-Tuning Jobs**:
            - **List Fine-Tuning Jobs**: `examples/assistants/fine-tuning/listFineTuningJobs.php`
            - **Retrieve Fine-Tuning Job**: `examples/assistants/fine-tuning/retrieveFineTuningJob.php`
            - **Cancel Fine-Tuning**: `examples/assistants/fine-tuning/cancelFineTuning.php`
            - **Create Fine-Tuning Job**: `examples/assistants/fine-tuning/createFineTuningJob.php`
        - **Vector Stores**:
            - **Create Vector Store**: `examples/assistants/vector-stores/createVectorStore.php`
            - **List Vector Stores**: `examples/assistants/vector-stores/listVectorStores.php`
            - **Retrieve Vector Store**: `examples/assistants/vector-stores/retrieveVectorStore.php`
            - **Modify Vector Store**: `examples/assistants/vector-stores/modifyVectorStore.php`
            - **Delete Vector Store**: `examples/assistants/vector-stores/deleteVectorStore.php`
            - **Create Vector Store File**: `examples/assistants/vector-store-files/createVectorStoreFile.php`
            - **List Vector Store Files**: `examples/assistants/vector-store-files/listVectorStoreFiles.php`
            - **Retrieve Vector Store File**: `examples/assistants/vector-store-files/retrieveVectorStoreFile.php`
            - **Delete Vector Store File**: `examples/assistants/vector-store-files/deleteVectorStoreFile.php`
            - **Create Vector Store File Batch**: `examples/assistants/vector-store-file-batches/createVectorStoreFileBatch.php`
            - **Retrieve Vector Store File Batch**: `examples/assistants/vector-store-file-batches/retrieveVectorStoreFileBatch.php`
            - **Cancel Vector Store File Batch**: `examples/assistants/vector-store-file-batches/cancelVectorStoreFileBatch.php`
            - **List Vector Store Files in Batch**: `examples/assistants/vector-store-file-batches/listVectorStoreFilesInBatch.php`
        - **Assistants Management**:
            - **Create Assistant**: `examples/assistants/assistants/createAssistant.php`
            - **List Assistants**: `examples/assistants/assistants/listAssistants.php`
            - **Retrieve Assistant**: `examples/assistants/assistants/retrieveAssistant.php`
            - **Modify Assistant**: `examples/assistants/assistants/modifyAssistant.php`
            - **Delete Assistant**: `examples/assistants/assistants/deleteAssistant.php`
        - **Threads Management**:
            - **Create Thread**: `examples/assistants/threads/createThread.php`
            - **Retrieve Thread**: `examples/assistants/threads/retrieveThread.php`
            - **Modify Thread**: `examples/assistants/threads/modifyThread.php`
            - **Delete Thread**: `examples/assistants/threads/deleteThread.php`
        - **Messages Management**:
            - **Create Message**: `examples/assistants/messages/createMessage.php`
            - **List Messages**: `examples/assistants/messages/listMessages.php`
            - **Retrieve Message**: `examples/assistants/messages/retrieveMessage.php`
            - **Modify Message**: `examples/assistants/messages/modifyMessage.php`
            - **Delete Message**: `examples/assistants/messages/deleteMessage.php`
        - **Runs Management**:
            - **Create Run**: `examples/assistants/runs/createRun.php`
            - **Create Thread and Run**: `examples/assistants/runs/createThreadAndRun.php`
            - **List Runs**: `examples/assistants/runs/listRuns.php`
            - **Retrieve Run**: `examples/assistants/runs/retrieveRun.php`
            - **Modify Run**: `examples/assistants/runs/modifyRun.php`
            - **Submit Tool Outputs to Run**: `examples/assistants/runs/submitToolOutputsToRun.php`
            - **Cancel Run**: `examples/assistants/runs/cancelRun.php`
        - **Run Steps Management**:
            - **List Run Steps**: `examples/assistants/run-steps/listRunSteps.php`
            - **Retrieve Run Step**: `examples/assistants/run-steps/retrieveRunStep.php`
        - **Project Management**:
            - **List Projects**: `examples/administration/projects/listProjects.php`
            - **Create Project**: `examples/administration/projects/createProject.php`
            - **Retrieve Project**: `examples/administration/projects/retrieveProject.php`
            - **Modify Project**: `examples/administration/projects/modifyProject.php`
            - **Archive Project**: `examples/administration/projects/archiveProject.php`
        - **Project Users Management**:
            - **List Project Users**: `examples/administration/project-users/listProjectUsers.php`
            - **Create Project User**: `examples/administration/project-users/createProjectUser.php`
            - **Retrieve Project User**: `examples/administration/project-users/retrieveProjectUser.php`
            - **Modify Project User**: `examples/administration/project-users/modifyProjectUser.php`
            - **Delete Project User**: `examples/administration/project-users/deleteProjectUser.php`
        - **Project Service Accounts Management**:
            - **List Project Service Accounts**: `examples/administration/project-service-accounts/listProjectServiceAccounts.php`
            - **Create Project Service Account**: `examples/administration/project-service-accounts/createProjectServiceAccount.php`
            - **Retrieve Project Service Account**: `examples/administration/project-service-accounts/retrieveProjectServiceAccount.php`
            - **Delete Project Service Account**: `examples/administration/project-service-accounts/deleteProjectServiceAccount.php`
        - **Project API Keys Management**:
            - **List Project API Keys**: `examples/administration/project-api-keys/listProjectApiKeys.php`
            - **Retrieve Project API Key**: `examples/administration/project-api-keys/retrieveProjectApiKey.php`
            - **Delete Project API Key**: `examples/administration/project-api-keys/deleteProjectApiKey.php`
        - **Invites Management**:
            - **List Invites**: `examples/administration/invites/listInvites.php`
            - **Create Invite**: `examples/administration/invites/createInvite.php`
            - **Retrieve Invite**: `examples/administration/invites/retrieveInvite.php`
            - **Delete Invite**: `examples/administration/invites/deleteInvite.php`
        - **Audit Logs**:
            - **List Audit Logs**: `examples/administration/audit-logs/listAuditLogs.php`

- **Factory Updates**:
    - Added real-time processing of streamed content in the `OpenAIFactory::request` method.

### Updated

- `OpenAIUrlBuilder` class to support the new `basePath` parameter, which provides a more flexible way to set the base URL for API requests.

## [2.2.0] - 2024-02-04

### Added

- Removed `completions` endpoint(s)
- Removed `edits` endpoint(s)
- Removed `fine-tunes` endpoint(s)
- Moved `deleteModel` to model endpoints
- Updated examples. and tests
- Allow override of `OpenAIUrlBuilder::$apiVersion`

## [2.1.1] - 2024-01-11

### Added

- Added missing properties to OpenAI class (apiKey, organization, origin)
- Fixed spelling of "organization"
- Sensitive Parameter value redaction (API Key)

## [2.1.0] - 2023-11-06

### Added

- Added support for the new createSpeech-endpoint (https://platform.openai.com/docs/api-reference/audio/createSpeech)
- Added support for the new fine-tuning-endpoints (https://platform.openai.com/docs/api-reference/fine-tuning)
- Marked fine-tunes-endpoints as deprecated (soft)
- Updated existing examples

## [2.0.0] - 2023-03-28

### Added

- Added support for any PSR-17 and PSR-18 compatible HTTP client and factory.
- Refactored all examples to use the updated `OpenAI` class.
- Added `$origin` parameter to allow overriding the default origin (api.openai.com) if necessary.

### Changed

- Removed Guzzle dependency from the project (it's still there, but just as dev-dependency for the examples, and for the unit tests)
- Removed the singleton pattern from the `OpenAI` class.
- Major refactoring of the `OpenAI` class to support any PSR-17 and PSR-18 compatible HTTP client and factory.
- Refactored and optimized the test cases in `OpenAITest`.
- Optimized the `OpenAIException` class.
- Updated the README.md to reflect changes in the project structure and requirements.

### Removed

The individual methods `createChatCompletion` and `createCompletion` have been eliminated to decrease the overall complexity.
Although these methods can still be invoked, it is now necessary to explicitly set the `method` option.

## [1.1.0] - 2023-03-17

### Added

- `setProxy()` method added to the `OpenAI` class, allowing users to set a custom proxy for the underlying HTTP client.
- New `TestHelper` class to simplify test code and improve readability. Includes methods to work with private properties and methods using Reflection, as well as a method to load response files for testing purposes.
- Unit tests and optimizations for the new features in `OpenAITest.php`.

### Changed

- Optimized several test methods in `OpenAITest.php` by leveraging the newly created `TestHelper` class.

## [1.0.0] - 2023-03-16

### Added

- Initial release of the OpenAI PHP library.
- Basic implementation for making API calls to the OpenAI API.
- Unit tests for the initial implementation.
