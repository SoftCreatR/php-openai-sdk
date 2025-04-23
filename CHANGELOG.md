# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
