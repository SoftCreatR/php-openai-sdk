# OpenAI API Wrapper for PHP

[![Build](https://img.shields.io/github/actions/workflow/status/SoftCreatR/php-openai-sdk/.github/workflows/create-release.yml?branch=main)](https://github.com/SoftCreatR/php-openai-sdk/actions/workflows/create-release.yml) [![Latest Release](https://img.shields.io/packagist/v/SoftCreatR/php-openai-sdk?color=blue&label=Latest%20Release)](https://packagist.org/packages/softcreatr/php-openai-sdk) [![ISC licensed](https://img.shields.io/badge/license-ISC-blue.svg)](./LICENSE.md) [![Plant Tree](https://img.shields.io/badge/dynamic/json?color=brightgreen&label=Plant%20Tree&query=%24.total&url=https%3A%2F%2Fpublic.offset.earth%2Fusers%2Fsoftcreatr%2Ftrees)](https://ecologi.com/softcreatr?r=61212ab3fc69b8eb8a2014f4) [![Codecov branch](https://img.shields.io/codecov/c/github/SoftCreatR/php-openai-sdk)](https://codecov.io/gh/SoftCreatR/php-openai-sdk) [![Code Climate maintainability](https://img.shields.io/codeclimate/maintainability-percentage/SoftCreatR/php-openai-sdk)](https://codeclimate.com/github/SoftCreatR/php-openai-sdk)

This PHP library provides a simple wrapper for the OpenAI API, allowing you to easily integrate the OpenAI API into your PHP projects.

## Features

- Easy integration with OpenAI API
- Supports all OpenAI API endpoints
- Streaming support for real-time responses in chat completions
- Utilizes PSR-17 and PSR-18 compliant HTTP clients and factories for making API requests

## Requirements

- PHP 8.1 or higher
- A PSR-17 HTTP Factory implementation (e.g., [guzzle/psr7](https://github.com/guzzle/psr7) or [nyholm/psr7](https://github.com/Nyholm/psr7))
- A PSR-18 HTTP Client implementation (e.g., [guzzlehttp/guzzle](https://github.com/guzzle/guzzle) or [symfony/http-client](https://github.com/symfony/http-client))

## Installation

You can install the library via [Composer](https://getcomposer.org/):

```bash
composer require softcreatr/php-openai-sdk
```

## Usage

First, include the library in your project:

```php
<?php

require_once 'vendor/autoload.php';
```

Then, create an instance of the `OpenAI` class with your API key, organization (optional), an HTTP client, an HTTP request factory, and an HTTP stream factory:

```php
use SoftCreatR\OpenAI\OpenAI;

$apiKey = 'your_api_key';
$organization = 'your_organization_id'; // optional

// Replace these lines with your chosen PSR-17 and PSR-18 compatible HTTP client and factories
$httpClient = new YourChosenHttpClient();
$requestFactory = new YourChosenRequestFactory();
$streamFactory = new YourChosenStreamFactory();
$uriFactory = new YourChosenUriFactory();

$openAI = new OpenAI($requestFactory, $streamFactory, $uriFactory, $httpClient, $apiKey, $organization);
```

Now you can call any supported OpenAI API endpoint using the magic method `__call`:

```php
$response = $openAI->createChatCompletion([
    'model' => 'gpt-4',
    'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
        ['role' => 'user', 'content' => 'Hello!'],
    ],
]);

// Process the API response
if ($response->getStatusCode() === 200) {
    $responseObj = json_decode($response->getBody()->getContents(), true);

    print_r($responseObj);
} else {
    echo "Error: " . $response->getStatusCode();
}
```

### Streaming Example

You can enable real-time streaming for chat completions:

```php
$streamCallback = static function ($data) {
    if (isset($data['choices'][0]['delta']['content'])) {
        echo $data['choices'][0]['delta']['content'];
    }
};

$openAI->createChatCompletion(
    [
        'model' => 'gpt-4',
        'messages' => [
            [
                'role' => 'user',
                'content' => 'Tell me a story about a brave knight.',
            ],
        ],
        'stream' => true,
    ],
    $streamCallback
);
```

For more details on how to use each endpoint, refer to the [OpenAI API documentation](https://platform.openai.com/docs/api-reference), and the [examples](https://github.com/SoftCreatR/php-openai-sdk/tree/main/examples) provided in the repository.

## Supported Methods

Below is a list of supported methods organized by category. Each method links to its corresponding OpenAI API documentation and includes a link to an example in this repository.

### Audio

- **Create Transcription** - [API Reference](https://platform.openai.com/docs/api-reference/audio/createTranscription) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/audio/createTranscription.php)
    - `createTranscription(array $options = [])`
- **Create Translation** - [API Reference](https://platform.openai.com/docs/api-reference/audio/createTranslation) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/audio/createTranslation.php)
    - `createTranslation(array $options = [])`
- **Create Speech** - [API Reference](https://platform.openai.com/docs/api-reference/audio/createSpeech) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/audio/createSpeech.php)
    - `createSpeech(array $options = [])`

### Chat

- **Create Chat Completion** - [API Reference](https://platform.openai.com/docs/api-reference/chat/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/chat/createChatCompletion.php)
    - `createChatCompletion(array $options = [])`

### Embeddings

- **Create Embedding** - [API Reference](https://platform.openai.com/docs/api-reference/embeddings/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/embeddings/createEmbedding.php)
    - `createEmbedding(array $options = [])`

### Fine-tuning

- **Create Fine-tuning Job** - [API Reference](https://platform.openai.com/docs/api-reference/fine-tuning/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/fine-tuning/createFineTuningJob.php)
    - `createFineTuningJob(array $options = [])`
- **List Fine-tuning Jobs** - [API Reference](https://platform.openai.com/docs/api-reference/fine-tuning/list) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/fine-tuning/listFineTuningJobs.php)
    - `listFineTuningJobs()`
- **Retrieve Fine-tuning Job** - [API Reference](https://platform.openai.com/docs/api-reference/fine-tuning/retrieve) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/fine-tuning/retrieveFineTuningJob.php)
    - `retrieveFineTuningJob(array $parameters)`
- **Cancel Fine-tuning Job** - [API Reference](https://platform.openai.com/docs/api-reference/fine-tuning/cancel) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/fine-tuning/cancelFineTuning.php)
    - `cancelFineTuning(array $parameters)`
- **List Fine-tuning Events** - [API Reference](https://platform.openai.com/docs/api-reference/fine-tuning/events) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/fine-tuning/listFineTuningEvents.php)
    - `listFineTuningEvents(array $parameters, array $options = [])`

### Batch

- **Create Batch** - [API Reference](https://platform.openai.com/docs/api-reference/batch/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/batch/createBatch.php)
    - `createBatch(array $options = [])`
- **Retrieve Batch** - [API Reference](https://platform.openai.com/docs/api-reference/batch/retrieve) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/batch/retrieveBatch.php)
    - `retrieveBatch(array $parameters)`
- **Cancel Batch** - [API Reference](https://platform.openai.com/docs/api-reference/batch/cancel) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/batch/cancelBatch.php)
    - `cancelBatch(array $parameters)`
- **List Batches** - [API Reference](https://platform.openai.com/docs/api-reference/batch/list) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/batch/listBatch.php)
    - `listBatches()`

### Files

- **List Files** - [API Reference](https://platform.openai.com/docs/api-reference/files/list) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/files/listFiles.php)
    - `listFiles()`
- **Upload File** - [API Reference](https://platform.openai.com/docs/api-reference/files/upload) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/files/uploadFile.php)
    - `uploadFile(array $options = [])`
- **Delete File** - [API Reference](https://platform.openai.com/docs/api-reference/files/delete) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/files/deleteFile.php)
    - `deleteFile(array $parameters)`
- **Retrieve File** - [API Reference](https://platform.openai.com/docs/api-reference/files/retrieve) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/files/retrieveFile.php)
    - `retrieveFile(array $parameters)`
- **Download File** - [API Reference](https://platform.openai.com/docs/api-reference/files/retrieve-content) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/files/retrieveFileContent.php)
    - `retrieveFileContent(array $parameters)`

### Uploads

- **Create Upload** - [API Reference](https://platform.openai.com/docs/api-reference/uploads/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/uploads/createUpload.php)
    - `createUpload(array $options = [])`
- **Add Upload Part** - [API Reference](https://platform.openai.com/docs/api-reference/uploads/add-part) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/uploads/addUploadPart.php)
    - `addUploadPart(array $parameters, array $options = [])`
- **Complete Upload** - [API Reference](https://platform.openai.com/docs/api-reference/uploads/complete) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/uploads/completeUpload.php)
    - `completeUpload(array $parameters)`
- **Cancel Upload** - [API Reference](https://platform.openai.com/docs/api-reference/uploads/cancel) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/uploads/cancelUpload.php)
    - `cancelUpload(array $parameters)`

### Images

- **Create Image** - [API Reference](https://platform.openai.com/docs/api-reference/images/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/images/createImage.php)
    - `createImage(array $options = [])`
- **Create Image Edit** - [API Reference](https://platform.openai.com/docs/api-reference/images/create-edit) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/images/createImageEdit.php)
    - `createImageEdit(array $options = [])`
- **Create Image Variation** - [API Reference](https://platform.openai.com/docs/api-reference/images/create-variation) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/images/createImageVariation.php)
    - `createImageVariation(array $options = [])`

### Models

- **List Models** - [API Reference](https://platform.openai.com/docs/api-reference/models/list) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/models/listModels.php)
    - `listModels()`
- **Retrieve Model** - [API Reference](https://platform.openai.com/docs/api-reference/models/retrieve) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/models/retrieveModel.php)
    - `retrieveModel(array $parameters)`
- **Delete Model** - [API Reference](https://platform.openai.com/docs/api-reference/models/delete) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/models/deleteModel.php)
    - `deleteModel(array $parameters)`

### Moderations

- **Create Moderation** - [API Reference](https://platform.openai.com/docs/api-reference/moderations/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/moderations/createModeration.php)
    - `createModeration(array $options = [])`

### Assistants

#### Assistants

- **Create Assistant** - [API Reference](https://platform.openai.com/docs/api-reference/assistants/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/assistants/createAssistant.php)
    - `createAssistant(array $options = [])`
- **List Assistants** - [API Reference](https://platform.openai.com/docs/api-reference/assistants/list) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/assistants/listAssistants.php)
    - `listAssistants()`
- **Retrieve Assistant** - [API Reference](https://platform.openai.com/docs/api-reference/assistants/retrieve) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/assistants/retrieveAssistant.php)
    - `retrieveAssistant(array $parameters)`
- **Modify Assistant** - [API Reference](https://platform.openai.com/docs/api-reference/assistants/modify) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/assistants/modifyAssistant.php)
    - `modifyAssistant(array $parameters, array $options = [])`
- **Delete Assistant** - [API Reference](https://platform.openai.com/docs/api-reference/assistants/delete) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/assistants/deleteAssistant.php)
    - `deleteAssistant(array $parameters)`

#### Threads

- **Create Thread** - [API Reference](https://platform.openai.com/docs/api-reference/threads/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/threads/createThread.php)
    - `createThread(array $options = [])`
- **Retrieve Thread** - [API Reference](https://platform.openai.com/docs/api-reference/threads/retrieve) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/threads/retrieveThread.php)
    - `retrieveThread(array $parameters)`
- **Modify Thread** - [API Reference](https://platform.openai.com/docs/api-reference/threads/modify) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/threads/modifyThread.php)
    - `modifyThread(array $parameters, array $options = [])`
- **Delete Thread** - [API Reference](https://platform.openai.com/docs/api-reference/threads/delete) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/threads/deleteThread.php)
    - `deleteThread(array $parameters)`

#### Messages

- **Create Message** - [API Reference](https://platform.openai.com/docs/api-reference/messages/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/messages/createMessage.php)
    - `createMessage(array $parameters, array $options = [])`
- **List Messages** - [API Reference](https://platform.openai.com/docs/api-reference/messages/list) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/messages/listMessages.php)
    - `listMessages(array $parameters)`
- **Retrieve Message** - [API Reference](https://platform.openai.com/docs/api-reference/messages/retrieve) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/messages/retrieveMessage.php)
    - `retrieveMessage(array $parameters)`
- **Modify Message** - [API Reference](https://platform.openai.com/docs/api-reference/messages/modify) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/messages/modifyMessage.php)
    - `modifyMessage(array $parameters, array $options = [])`
- **Delete Message** - [API Reference](https://platform.openai.com/docs/api-reference/messages/delete) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/messages/deleteMessage.php)
    - `deleteMessage(array $parameters)`

#### Runs

- **Create Run** - [API Reference](https://platform.openai.com/docs/api-reference/runs/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/runs/createRun.php)
    - `createRun(array $parameters, array $options = [])`
- **Create Thread and Run** - [API Reference](https://platform.openai.com/docs/api-reference/runs/create-thread-and-run) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/runs/createThreadAndRun.php)
    - `createThreadAndRun(array $options = [])`
- **List Runs** - [API Reference](https://platform.openai.com/docs/api-reference/runs/list) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/runs/listRuns.php)
    - `listRuns(array $parameters)`
- **Retrieve Run** - [API Reference](https://platform.openai.com/docs/api-reference/runs/retrieve) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/runs/retrieveRun.php)
    - `retrieveRun(array $parameters)`
- **Modify Run** - [API Reference](https://platform.openai.com/docs/api-reference/runs/modify) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/runs/modifyRun.php)
    - `modifyRun(array $parameters, array $options = [])`
- **Submit Tool Outputs to Run** - [API Reference](https://platform.openai.com/docs/api-reference/runs/submit-tool-outputs) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/runs/submitToolOutputsToRun.php)
    - `submitToolOutputsToRun(array $parameters, array $options = [])`
- **Cancel Run** - [API Reference](https://platform.openai.com/docs/api-reference/runs/cancel) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/runs/cancelRun.php)
    - `cancelRun(array $parameters)`

#### Run Steps

- **List Run Steps** - [API Reference](https://platform.openai.com/docs/api-reference/run-steps/list) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/run-steps/listRunSteps.php)
    - `listRunSteps(array $parameters)`
- **Retrieve Run Step** - [API Reference](https://platform.openai.com/docs/api-reference/run-steps/retrieve) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/run-steps/retrieveRunStep.php)
    - `retrieveRunStep(array $parameters)`

#### Vector Stores

- **Create Vector Store** - [API Reference](https://platform.openai.com/docs/api-reference/vector-stores/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/vector-stores/createVectorStore.php)
    - `createVectorStore(array $options = [])`
- **List Vector Stores** - [API Reference](https://platform.openai.com/docs/api-reference/vector-stores/list) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/vector-stores/listVectorStores.php)
    - `listVectorStores()`
- **Retrieve Vector Store** - [API Reference](https://platform.openai.com/docs/api-reference/vector-stores/retrieve) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/vector-stores/retrieveVectorStore.php)
    - `retrieveVectorStore(array $parameters)`
- **Modify Vector Store** - [API Reference](https://platform.openai.com/docs/api-reference/vector-stores/modify) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/vector-stores/modifyVectorStore.php)
    - `modifyVectorStore(array $parameters, array $options = [])`
- **Delete Vector Store** - [API Reference](https://platform.openai.com/docs/api-reference/vector-stores/delete) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/vector-stores/deleteVectorStore.php)
    - `deleteVectorStore(array $parameters)`

#### Vector Store Files

- **Create Vector Store File** - [API Reference](https://platform.openai.com/docs/api-reference/vector-store-files/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/vector-store-files/createVectorStoreFile.php)
    - `createVectorStoreFile(array $parameters, array $options = [])`
- **List Vector Store Files** - [API Reference](https://platform.openai.com/docs/api-reference/vector-store-files/list) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/vector-store-files/listVectorStoreFiles.php)
    - `listVectorStoreFiles(array $parameters)`
- **Retrieve Vector Store File** - [API Reference](https://platform.openai.com/docs/api-reference/vector-store-files/retrieve) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/vector-store-files/retrieveVectorStoreFile.php)
    - `retrieveVectorStoreFile(array $parameters)`
- **Delete Vector Store File** - [API Reference](https://platform.openai.com/docs/api-reference/vector-store-files/delete) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/vector-store-files/deleteVectorStoreFile.php)
    - `deleteVectorStoreFile(array $parameters)`

#### Vector Store File Batches

- **Create Vector Store File Batch** - [API Reference](https://platform.openai.com/docs/api-reference/vector-store-file-batches/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/vector-store-file-batches/createVectorStoreFileBatch.php)
    - `createVectorStoreFileBatch(array $parameters, array $options = [])`
- **Retrieve Vector Store File Batch** - [API Reference](https://platform.openai.com/docs/api-reference/vector-store-file-batches/retrieve) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/vector-store-file-batches/retrieveVectorStoreFileBatch.php)
    - `retrieveVectorStoreFileBatch(array $parameters)`
- **Cancel Vector Store File Batch** - [API Reference](https://platform.openai.com/docs/api-reference/vector-store-file-batches/cancel) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/vector-store-file-batches/cancelVectorStoreFileBatch.php)
    - `cancelVectorStoreFileBatch(array $parameters)`
- **List Vector Store Files in Batch** - [API Reference](https://platform.openai.com/docs/api-reference/vector-store-file-batches/list-files) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/assistants/vector-store-file-batches/listVectorStoreFilesInBatch.php)
    - `listVectorStoreFilesInBatch(array $parameters)`

### Administration

#### Invites

- **List Invites** - [API Reference](https://platform.openai.com/docs/api-reference/invites/list) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/invites/listInvites.php)
    - `listInvites()`
- **Create Invite** - [API Reference](https://platform.openai.com/docs/api-reference/invites/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/invites/createInvite.php)
    - `createInvite(array $options = [])`
- **Retrieve Invite** - [API Reference](https://platform.openai.com/docs/api-reference/invites/retrieve) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/invites/retrieveInvite.php)
    - `retrieveInvite(array $parameters)`
- **Delete Invite** - [API Reference](https://platform.openai.com/docs/api-reference/invites/delete) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/invites/deleteInvite.php)
    - `deleteInvite(array $parameters)`

#### Users

- **List Users** - [API Reference](https://platform.openai.com/docs/api-reference/org-users/list) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/org-users/listUsers.php)
    - `listUsers()`
- **Modify User** - [API Reference](https://platform.openai.com/docs/api-reference/org-users/modify) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/org-users/modifyUser.php)
    - `modifyUser(array $parameters, array $options = [])`
- **Retrieve User** - [API Reference](https://platform.openai.com/docs/api-reference/org-users/retrieve) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/org-users/retrieveUser.php)
    - `retrieveUser(array $parameters)`
- **Delete User** - [API Reference](https://platform.openai.com/docs/api-reference/org-users/delete) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/org-users/deleteUser.php)
    - `deleteUser(array $parameters)`

#### Projects

- **List Projects** - [API Reference](https://platform.openai.com/docs/api-reference/projects/list) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/projects/listProjects.php)
    - `listProjects()`
- **Create Project** - [API Reference](https://platform.openai.com/docs/api-reference/projects/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/projects/createProject.php)
    - `createProject(array $options = [])`
- **Retrieve Project** - [API Reference](https://platform.openai.com/docs/api-reference/projects/retrieve) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/projects/retrieveProject.php)
    - `retrieveProject(array $parameters)`
- **Modify Project** - [API Reference](https://platform.openai.com/docs/api-reference/projects/modify) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/projects/modifyProject.php)
    - `modifyProject(array $parameters, array $options = [])`
- **Archive Project** - [API Reference](https://platform.openai.com/docs/api-reference/projects/archive) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/projects/archiveProject.php)
    - `archiveProject(array $parameters)`

#### Project Users

- **List Project Users** - [API Reference](https://platform.openai.com/docs/api-reference/project-users/list) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/project-users/listProjectUsers.php)
    - `listProjectUsers(array $parameters)`
- **Create Project User** - [API Reference](https://platform.openai.com/docs/api-reference/project-users/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/project-users/createProjectUser.php)
    - `createProjectUser(array $parameters, array $options = [])`
- **Retrieve Project User** - [API Reference](https://platform.openai.com/docs/api-reference/project-users/retrieve) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/project-users/retrieveProjectUser.php)
    - `retrieveProjectUser(array $parameters)`
- **Modify Project User** - [API Reference](https://platform.openai.com/docs/api-reference/project-users/modify) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/project-users/modifyProjectUser.php)
    - `modifyProjectUser(array $parameters, array $options = [])`
- **Delete Project User** - [API Reference](https://platform.openai.com/docs/api-reference/project-users/delete) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/project-users/deleteProjectUser.php)
    - `deleteProjectUser(array $parameters)`

#### Project Service Accounts

- **List Project Service Accounts** - [API Reference](https://platform.openai.com/docs/api-reference/project-service-accounts/list) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/project-service-accounts/listProjectServiceAccounts.php)
    - `listProjectServiceAccounts(array $parameters)`
- **Create Project Service Account** - [API Reference](https://platform.openai.com/docs/api-reference/project-service-accounts/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/project-service-accounts/createProjectServiceAccount.php)
    - `createProjectServiceAccount(array $parameters, array $options = [])`
- **Retrieve Project Service Account** - [API Reference](https://platform.openai.com/docs/api-reference/project-service-accounts/retrieve) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/project-service-accounts/retrieveProjectServiceAccount.php)
    - `retrieveProjectServiceAccount(array $parameters)`
- **Delete Project Service Account** - [API Reference](https://platform.openai.com/docs/api-reference/project-service-accounts/delete) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/project-service-accounts/deleteProjectServiceAccount.php)
    - `deleteProjectServiceAccount(array $parameters)`

#### Project API Keys

- **List Project API Keys** - [API Reference](https://platform.openai.com/docs/api-reference/project-api-keys/list) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/project-api-keys/listProjectApiKeys.php)
    - `listProjectApiKeys(array $parameters)`
- **Retrieve Project API Key** - [API Reference](https://platform.openai.com/docs/api-reference/project-api-keys/retrieve) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/project-api-keys/retrieveProjectApiKey.php)
    - `retrieveProjectApiKey(array $parameters)`
- **Delete Project API Key** - [API Reference](https://platform.openai.com/docs/api-reference/project-api-keys/delete) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/project-api-keys/deleteProjectApiKey.php)
    - `deleteProjectApiKey(array $parameters)`

#### Audit Logs

- **List Audit Logs** - [API Reference](https://platform.openai.com/docs/api-reference/audit-logs/list) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/administration/audit-logs/listAuditLogs.php)
    - `listAuditLogs(array $options = [])`

## Changelog

For a detailed list of changes and updates, please refer to the [CHANGELOG.md](https://github.com/SoftCreatR/php-openai-sdk/blob/main/CHANGELOG.md) file. We adhere to [Semantic Versioning](https://semver.org/spec/v2.0.0.html) and document notable changes for each release.

## Known Problems and Limitations

### Streaming Support

Streaming is now supported for real-time token generation in chat completions. Please make sure you are handling streams correctly using a callback, as demonstrated in the examples.

## License

This library is licensed under the ISC License. See the [LICENSE](https://github.com/SoftCreatR/php-openai-sdk/blob/main/LICENSE.md) file for more information.

## Maintainers 🛠️

<table>
<tr>
    <td style="text-align:center;word-wrap:break-word;width:150px;height: 150px">
        <a href=https://github.com/SoftCreatR>
            <img src=https://avatars.githubusercontent.com/u/81188?v=4 width="100;" alt="Sascha Greuel"/>
            <br />
            <sub style="font-size:14px"><b>Sascha Greuel</b></sub>
        </a>
    </td>
</tr>
</table>

## Contributors ✨

<table>
<tr>
</tr>
</table>
